<?php
/**
 * Migração da Coordenação (executar UMA vez).
 *
 * Cria a tabela de mensagens de contato, adiciona o tipo de usuário
 * 'coordenacao' e cria a conta padrão da coordenação.
 *
 * Pode ser executado pela linha de comando:
 *   C:\xampp\php\php.exe migracao-coordenacao.php
 * ou aberto no navegador uma vez: http://localhost/teste/migracao-coordenacao.php
 *
 * Por segurança, depois de rodar você pode apagar este arquivo.
 */
require_once __DIR__ . '/config/database.php';

$db = getDB();
$log = [];

// 1. Tabela de mensagens de contato
$db->exec("
    CREATE TABLE IF NOT EXISTS mensagens_contato (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NULL,
        nome VARCHAR(150) NOT NULL,
        email VARCHAR(200) NOT NULL,
        assunto VARCHAR(200) NOT NULL,
        mensagem TEXT NOT NULL,
        status ENUM('nova','lida','resolvida') NOT NULL DEFAULT 'nova',
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_usuario (usuario_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
$log[] = 'OK: tabela mensagens_contato pronta.';

// 1.1 Se mensagens_contato existe mas nao tem usuario_id (install antigo), adiciona
$colsMc = $db->query("SHOW COLUMNS FROM mensagens_contato")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('usuario_id', $colsMc, true)) {
    $db->exec("ALTER TABLE mensagens_contato ADD COLUMN usuario_id INT NULL AFTER id");
    $db->exec("ALTER TABLE mensagens_contato ADD INDEX idx_usuario (usuario_id)");
    $log[] = 'OK: coluna mensagens_contato.usuario_id criada.';
}

// 1.2 Tabela de respostas (chat coordenacao <-> usuario)
$db->exec("
    CREATE TABLE IF NOT EXISTS respostas_contato (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mensagem_id INT NOT NULL,
        remetente_tipo ENUM('coordenacao','usuario') NOT NULL,
        conteudo TEXT NOT NULL,
        lida TINYINT(1) DEFAULT 0,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mensagem_id) REFERENCES mensagens_contato(id) ON DELETE CASCADE,
        INDEX idx_msg (mensagem_id, criado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
$log[] = 'OK: tabela respostas_contato pronta.';

// 2. Adiciona o tipo 'coordenacao' ao enum de usuarios (idempotente)
$col = $db->query("SHOW COLUMNS FROM usuarios LIKE 'tipo'")->fetch();
if ($col && strpos($col['Type'], 'coordenacao') === false) {
    $db->exec("ALTER TABLE usuarios MODIFY tipo ENUM('aluno','empresa','coordenacao') NOT NULL");
    $log[] = 'OK: tipo coordenacao adicionado ao enum.';
} else {
    $log[] = 'OK: enum ja contem coordenacao.';
}

// 3. Colunas de restrição de vagas (moderação da coordenação)
$colsVagas = $db->query("SHOW COLUMNS FROM vagas")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('restrita', $colsVagas, true)) {
    $db->exec("ALTER TABLE vagas ADD COLUMN restrita TINYINT(1) DEFAULT 0 AFTER ativa");
    $log[] = 'OK: coluna vagas.restrita criada.';
} else {
    $log[] = 'OK: coluna vagas.restrita ja existe.';
}
if (!in_array('motivo_restricao', $colsVagas, true)) {
    $db->exec("ALTER TABLE vagas ADD COLUMN motivo_restricao VARCHAR(255) DEFAULT NULL AFTER restrita");
    $log[] = 'OK: coluna vagas.motivo_restricao criada.';
} else {
    $log[] = 'OK: coluna vagas.motivo_restricao ja existe.';
}

// 4. Conta padrão da coordenação
$email = 'coordenacao@internshipconnect.com.br';
$existe = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
$existe->execute([$email]);
if (!$existe->fetch()) {
    $hash = password_hash('coord123', PASSWORD_DEFAULT);
    $db->prepare("INSERT INTO usuarios (nome, email, senha, tipo, is_admin) VALUES (?, ?, ?, 'coordenacao', 1)")
       ->execute(['Coordenação InternSHIP', $email, $hash]);
    $log[] = "OK: conta de coordenacao criada -> $email / senha: coord123";
} else {
    $log[] = "OK: conta de coordenacao ja existe ($email).";
}

$saida = "Migração da Coordenação concluída:\n - " . implode("\n - ", $log) . "\n";
if (PHP_SAPI === 'cli') {
    echo $saida;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $saida . "\nPode apagar este arquivo (migracao-coordenacao.php) agora.\n";
}
