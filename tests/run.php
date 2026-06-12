<?php
/**
 * Smoke tests para o InternSHIP Conect.
 * Roda checagens basicas sem PHPUnit ou outras dependencias.
 *
 * Uso:
 *   php tests/run.php
 *
 * Em CI, as credenciais do banco vem das variaveis de ambiente DB_HOST,
 * DB_USER, DB_PASS, DB_NAME. Localmente, usa o que esta em config/database.php.
 */

// Permite sobrescrever as constantes do banco com env vars (para CI).
foreach (['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME'] as $k) {
    $v = getenv($k);
    if ($v !== false && !defined($k)) {
        define($k, $v);
    }
}

require_once __DIR__ . '/../includes/auth.php';

$pass = 0;
$fail = 0;
$failures = [];

function check($name, $cond, &$pass, &$fail, &$failures, $extra = '') {
    if ($cond) {
        echo "  OK  $name\n";
        $pass++;
    } else {
        echo "  FAIL $name" . ($extra ? " — $extra" : '') . "\n";
        $fail++;
        $failures[] = $name;
    }
}

echo "=== Smoke tests: InternSHIP Conect ===\n\n";

// 1. Conexao com banco
echo "[1] Conexao com o banco\n";
try {
    $db = getDB();
    check('getDB() retorna instancia PDO', $db instanceof PDO, $pass, $fail, $failures);
} catch (Throwable $e) {
    check('getDB() conecta sem excecao', false, $pass, $fail, $failures, $e->getMessage());
    echo "\nFalha critica de conexao. Abortando.\n";
    exit(1);
}

// 2. Tabelas esperadas existem
echo "\n[2] Tabelas do schema\n";
$tabelas = ['usuarios', 'alunos', 'empresas', 'vagas', 'candidaturas',
            'password_resets', 'oauth_accounts', 'vagas_favoritas'];
foreach ($tabelas as $t) {
    $stmt = $db->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$t]);
    check("tabela '$t' existe", (bool)$stmt->fetchColumn(), $pass, $fail, $failures);
}

// 3. Funcoes de autenticacao
echo "\n[3] Funcoes de autenticacao\n";
check('isLoggedIn() callable',  function_exists('isLoggedIn'),  $pass, $fail, $failures);
check('isAluno() callable',     function_exists('isAluno'),     $pass, $fail, $failures);
check('isEmpresa() callable',   function_exists('isEmpresa'),   $pass, $fail, $failures);
check('isAdmin() callable',     function_exists('isAdmin'),     $pass, $fail, $failures);
check('login() callable',       function_exists('login'),       $pass, $fail, $failures);
check('loginPorId() callable',  function_exists('loginPorId'),  $pass, $fail, $failures);
check('registrarAluno() callable',   function_exists('registrarAluno'),   $pass, $fail, $failures);
check('registrarEmpresa() callable', function_exists('registrarEmpresa'), $pass, $fail, $failures);

// 4. Hash de senha
echo "\n[4] Hash e verificacao de senha\n";
$hash = password_hash('teste123', PASSWORD_DEFAULT);
check('password_hash gera hash bcrypt', str_starts_with($hash, '$2y$'), $pass, $fail, $failures);
check('password_verify confirma a senha', password_verify('teste123', $hash), $pass, $fail, $failures);
check('password_verify rejeita senha errada', !password_verify('errada', $hash), $pass, $fail, $failures);

// 5. Helpers de CNPJ
echo "\n[5] Helpers de CNPJ\n";
check('formatarCNPJ() callable', function_exists('formatarCNPJ'), $pass, $fail, $failures);
check('formatarCNPJ formata corretamente',
    formatarCNPJ('11444777000161') === '11.444.777/0001-61',
    $pass, $fail, $failures);
check('consultarCNPJ() callable', function_exists('consultarCNPJ'), $pass, $fail, $failures);

// 6. Helpers de favoritos
echo "\n[6] Helpers de favoritos\n";
check('toggleFavorito() callable',     function_exists('toggleFavorito'),     $pass, $fail, $failures);
check('isVagaFavorita() callable',     function_exists('isVagaFavorita'),     $pass, $fail, $failures);
check('getIdsVagasFavoritas() callable', function_exists('getIdsVagasFavoritas'), $pass, $fail, $failures);

// 7. Helpers de reset de senha
echo "\n[7] Helpers de reset de senha\n";
check('gerarTokenResetSenha() callable', function_exists('gerarTokenResetSenha'), $pass, $fail, $failures);
check('validarTokenReset() callable',    function_exists('validarTokenReset'),    $pass, $fail, $failures);
check('redefinirSenha() callable',       function_exists('redefinirSenha'),       $pass, $fail, $failures);

// 8. Fluxo end-to-end basico (cadastro -> login -> candidatura)
echo "\n[8] Fluxo cadastro -> login -> candidatura\n";
$email = 'smoke_' . bin2hex(random_bytes(4)) . '@test.local';
$senha = 'senha123';
$cadastroOk = registrarAluno('Aluno Teste', $email, $senha, 'TI', 'IFRS', 5);
check('cadastro de aluno funciona', $cadastroOk, $pass, $fail, $failures);

if ($cadastroOk) {
    // Reset sessao para login limpo
    $_SESSION = [];
    $loginOk = login($email, $senha);
    check('login com cadastro recem-criado funciona', $loginOk, $pass, $fail, $failures);
    check('sessao com perfil_id apos login', !empty($_SESSION['perfil_id']), $pass, $fail, $failures);

    if ($loginOk) {
        // Pega qualquer vaga ativa
        $vagaId = $db->query("SELECT id FROM vagas WHERE ativa=1 LIMIT 1")->fetchColumn();
        if ($vagaId) {
            $alunoId = $_SESSION['perfil_id'];
            $db->prepare("INSERT INTO candidaturas (aluno_id, vaga_id, carta) VALUES (?, ?, ?)")
               ->execute([$alunoId, $vagaId, 'Carta de teste smoke']);
            $candId = $db->lastInsertId();
            check('candidatura criada', (bool)$candId, $pass, $fail, $failures);

            // Cleanup
            $db->prepare("DELETE FROM candidaturas WHERE id = ?")->execute([$candId]);
        }

        // Cleanup do usuario de teste
        $db->prepare("DELETE FROM usuarios WHERE email = ?")->execute([$email]);
    }
}

// Resultado
echo "\n=================================\n";
echo "Resultado: $pass OK, $fail FAIL\n";
if ($fail > 0) {
    echo "\nFalhas:\n";
    foreach ($failures as $f) echo "  - $f\n";
    exit(1);
}
echo "Tudo passou!\n";
exit(0);
