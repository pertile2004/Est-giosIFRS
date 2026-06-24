<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

function isAluno() {
    return isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'aluno';
}

function isEmpresa() {
    return isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'empresa';
}

function isAdmin() {
    return !empty($_SESSION['is_admin']);
}

function isCoordenacao() {
    return isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'coordenacao';
}

function requireCoordenacao() {
    requireLogin();
    if (!isCoordenacao() && !isAdmin()) {
        http_response_code(403);
        die('Acesso restrito à coordenação.');
    }
}

/** Quantidade de mensagens de contato ainda não lidas (status 'nova'). */
function contarMensagensNovas() {
    try {
        return (int) getDB()->query("SELECT COUNT(*) FROM mensagens_contato WHERE status='nova'")->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        die('Acesso restrito a administradores.');
    }
}

function requireLogin($redirect = '/teste/login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

function requireAluno() {
    requireLogin();
    if (!isAluno()) {
        header("Location: /teste/dashboard/empresa.php");
        exit;
    }
}

function requireEmpresa() {
    requireLogin();
    if (!isEmpresa()) {
        header("Location: /teste/dashboard/aluno.php");
        exit;
    }
}

function login($email, $senha) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha'])) {
        if (isset($user['ativo']) && !$user['ativo']) return false;
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nome'] = $user['nome'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['tipo'] = $user['tipo'];
        $_SESSION['is_admin'] = !empty($user['is_admin']);

        if ($user['tipo'] === 'aluno') {
            $stmt2 = $db->prepare("SELECT id FROM alunos WHERE usuario_id = ?");
            $stmt2->execute([$user['id']]);
            $aluno = $stmt2->fetch();
            $_SESSION['perfil_id'] = $aluno['id'] ?? null;
        } elseif ($user['tipo'] === 'empresa') {
            $stmt2 = $db->prepare("SELECT id, nome_empresa, logo FROM empresas WHERE usuario_id = ?");
            $stmt2->execute([$user['id']]);
            $empresa = $stmt2->fetch();
            $_SESSION['perfil_id'] = $empresa['id'] ?? null;
            $_SESSION['nome_empresa'] = $empresa['nome_empresa'] ?? '';
            $_SESSION['logo'] = $empresa['logo'] ?? '';
        } else {
            // coordenação: sem perfil associado
            $_SESSION['perfil_id'] = null;
        }
        return true;
    }
    return false;
}

/**
 * Consulta dados publicos de um CNPJ na BrasilAPI.
 * Retorna ['razao_social','nome_fantasia','municipio','uf','cnae','telefone','email']
 * ou null em caso de erro.
 */
function consultarCNPJ($cnpj) {
    $cnpjLimpo = preg_replace('/\D/', '', $cnpj);
    if (strlen($cnpjLimpo) !== 14) return null;

    $url = 'https://brasilapi.com.br/api/cnpj/v1/' . $cnpjLimpo;
    $ctx = stream_context_create(['http' => [
        'timeout' => 6,
        'header'  => "Accept: application/json\r\nUser-Agent: InternSHIP-Conect\r\n",
        'ignore_errors' => true,
    ]]);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) return null;

    $data = json_decode($resp, true);
    if (!is_array($data) || isset($data['type']) || empty($data['cnpj'])) return null;

    return [
        'razao_social'  => $data['razao_social']  ?? '',
        'nome_fantasia' => $data['nome_fantasia'] ?? '',
        'municipio'     => $data['municipio']     ?? '',
        'uf'            => $data['uf']            ?? '',
        'cnae'          => $data['cnae_fiscal_descricao'] ?? '',
        'telefone'      => $data['ddd_telefone_1'] ?? '',
        'email'         => $data['email'] ?? '',
    ];
}

/**
 * Formata um CNPJ no padrao 00.000.000/0000-00.
 */
function formatarCNPJ($cnpj) {
    $c = preg_replace('/\D/', '', $cnpj);
    if (strlen($c) !== 14) return $cnpj;
    return substr($c,0,2).'.'.substr($c,2,3).'.'.substr($c,5,3).'/'.substr($c,8,4).'-'.substr($c,12,2);
}

function registrarAluno($nome, $email, $senha, $curso, $universidade, $semestre) {
    $db = getDB();
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'aluno')");
        $stmt->execute([$nome, $email, $hash]);
        $userId = $db->lastInsertId();

        $stmt2 = $db->prepare("INSERT INTO alunos (usuario_id, curso, universidade, semestre) VALUES (?, ?, ?, ?)");
        $stmt2->execute([$userId, $curso, $universidade, $semestre]);
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

function registrarEmpresa($nome, $email, $senha, $nomeEmpresa, $setor, $cidade, $estado) {
    $db = getDB();
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'empresa')");
        $stmt->execute([$nome, $email, $hash]);
        $userId = $db->lastInsertId();

        $stmt2 = $db->prepare("INSERT INTO empresas (usuario_id, nome_empresa, setor, cidade, estado) VALUES (?, ?, ?, ?, ?)");
        $stmt2->execute([$userId, $nomeEmpresa, $setor, $cidade, $estado]);
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

/**
 * Gera um token de reset de senha e armazena no banco.
 * Retorna o token (ou null se o e-mail nao existe).
 */
function gerarTokenResetSenha($email) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) return null;

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $db->prepare("INSERT INTO password_resets (usuario_id, token, expires_at) VALUES (?, ?, ?)")
       ->execute([$user['id'], $token, $expires]);

    return $token;
}

/**
 * Valida um token: retorna usuario_id se valido, false se invalido/expirado/usado.
 */
function validarTokenReset($token) {
    $db = getDB();
    $stmt = $db->prepare("SELECT usuario_id FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ? (int)$row['usuario_id'] : false;
}

/**
 * Define uma nova senha usando o token. Marca o token como usado.
 */
function redefinirSenha($token, $novaSenha) {
    $db = getDB();
    $userId = validarTokenReset($token);
    if (!$userId) return false;
    if (strlen($novaSenha) < 6) return false;

    $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
    $db->beginTransaction();
    try {
        $db->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")->execute([$hash, $userId]);
        $db->prepare("UPDATE password_resets SET used = 1 WHERE token = ?")->execute([$token]);
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

/**
 * Salva um arquivo de curriculo (PDF) para o aluno.
 * Retorna o caminho relativo gravado no banco, ou null em caso de erro.
 *
 * Validacoes: extensao .pdf, MIME application/pdf, tamanho max 3MB.
 */
function salvarCurriculoAluno($alunoId, $arquivo) {
    if (!$arquivo || $arquivo['error'] !== UPLOAD_ERR_OK) return null;
    if ($arquivo['size'] > 3 * 1024 * 1024) return null;

    $ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') return null;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);
    if ($mime !== 'application/pdf') return null;

    $destDir = __DIR__ . '/../uploads/curriculos';
    if (!is_dir($destDir)) @mkdir($destDir, 0775, true);

    $nome = 'cv_' . $alunoId . '_' . bin2hex(random_bytes(8)) . '.pdf';
    $destPath = $destDir . '/' . $nome;
    if (!move_uploaded_file($arquivo['tmp_name'], $destPath)) return null;

    $db = getDB();
    // Apaga curriculo antigo, se houver, para nao acumular lixo
    $stmt = $db->prepare("SELECT curriculo_path FROM alunos WHERE id = ?");
    $stmt->execute([$alunoId]);
    $antigo = $stmt->fetchColumn();
    if ($antigo) {
        $caminhoAntigo = __DIR__ . '/../' . ltrim($antigo, '/');
        if (is_file($caminhoAntigo)) @unlink($caminhoAntigo);
    }

    $relativo = 'uploads/curriculos/' . $nome;
    $db->prepare("UPDATE alunos SET curriculo_path = ? WHERE id = ?")
       ->execute([$relativo, $alunoId]);
    return $relativo;
}

/**
 * Helper interno que salva um arquivo de imagem.
 * Retorna o caminho relativo ou null em caso de erro.
 *
 * Validacoes: extensao jpg/jpeg/png/webp, MIME image/*, tamanho max 2MB.
 */
function _salvarImagem($arquivo, $subdir, $prefixo, $idEntidade) {
    if (!$arquivo || $arquivo['error'] !== UPLOAD_ERR_OK) return null;
    if ($arquivo['size'] > 2 * 1024 * 1024) return null;

    $ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    if ($ext === 'jpeg') $ext = 'jpg';
    $extsValidas = ['jpg','png','webp'];
    if (!in_array($ext, $extsValidas, true)) return null;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);
    if (!str_starts_with($mime, 'image/')) return null;

    $destDir = __DIR__ . '/../uploads/' . $subdir;
    if (!is_dir($destDir)) @mkdir($destDir, 0775, true);

    $nome = $prefixo . '_' . $idEntidade . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destPath = $destDir . '/' . $nome;
    if (!move_uploaded_file($arquivo['tmp_name'], $destPath)) return null;

    return 'uploads/' . $subdir . '/' . $nome;
}

/**
 * Salva a foto de perfil do aluno (jpg/png/webp, max 2MB).
 * Apaga a foto antiga se existir.
 */
function salvarFotoAluno($alunoId, $arquivo) {
    $db = getDB();
    $novo = _salvarImagem($arquivo, 'fotos', 'foto', $alunoId);
    if (!$novo) return null;

    $antigo = $db->prepare("SELECT foto FROM alunos WHERE id = ?");
    $antigo->execute([$alunoId]);
    $caminhoAntigo = $antigo->fetchColumn();
    if ($caminhoAntigo) {
        $f = __DIR__ . '/../' . ltrim($caminhoAntigo, '/');
        if (is_file($f)) @unlink($f);
    }

    $db->prepare("UPDATE alunos SET foto = ? WHERE id = ?")->execute([$novo, $alunoId]);
    return $novo;
}

/**
 * Salva o logo da empresa (jpg/png/webp, max 2MB).
 * Apaga o logo antigo se existir.
 */
function salvarLogoEmpresa($empresaId, $arquivo) {
    $db = getDB();
    $novo = _salvarImagem($arquivo, 'logos', 'logo', $empresaId);
    if (!$novo) return null;

    $antigo = $db->prepare("SELECT logo FROM empresas WHERE id = ?");
    $antigo->execute([$empresaId]);
    $caminhoAntigo = $antigo->fetchColumn();
    if ($caminhoAntigo && !preg_match('#^https?://#', $caminhoAntigo)) {
        $f = __DIR__ . '/../' . ltrim($caminhoAntigo, '/');
        if (is_file($f)) @unlink($f);
    }

    $db->prepare("UPDATE empresas SET logo = ? WHERE id = ?")->execute([$novo, $empresaId]);
    return $novo;
}

/**
 * Alterna o status de favorito de uma vaga para um aluno.
 * Retorna true se a vaga ficou favoritada, false se foi removida dos favoritos.
 */
function toggleFavorito($alunoId, $vagaId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM vagas_favoritas WHERE aluno_id = ? AND vaga_id = ?");
    $stmt->execute([$alunoId, $vagaId]);
    if ($stmt->fetch()) {
        $db->prepare("DELETE FROM vagas_favoritas WHERE aluno_id = ? AND vaga_id = ?")
           ->execute([$alunoId, $vagaId]);
        return false;
    }
    $db->prepare("INSERT INTO vagas_favoritas (aluno_id, vaga_id) VALUES (?, ?)")
       ->execute([$alunoId, $vagaId]);
    return true;
}

function isVagaFavorita($alunoId, $vagaId) {
    if (!$alunoId) return false;
    $db = getDB();
    $stmt = $db->prepare("SELECT 1 FROM vagas_favoritas WHERE aluno_id = ? AND vaga_id = ?");
    $stmt->execute([$alunoId, $vagaId]);
    return (bool)$stmt->fetchColumn();
}

function getIdsVagasFavoritas($alunoId) {
    if (!$alunoId) return [];
    $db = getDB();
    $stmt = $db->prepare("SELECT vaga_id FROM vagas_favoritas WHERE aluno_id = ?");
    $stmt->execute([$alunoId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Verifica se a empresa pode visualizar o perfil de um aluno
 * (so se o aluno se candidatou a alguma vaga dessa empresa).
 */
function empresaPodeVerAluno($empresaId, $alunoId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT 1
          FROM candidaturas c
          JOIN vagas v ON v.id = c.vaga_id
         WHERE c.aluno_id = ? AND v.empresa_id = ?
         LIMIT 1
    ");
    $stmt->execute([$alunoId, $empresaId]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Autentica diretamente um usuario pelo ID (usado apos OAuth).
 * Espelha o que login() faz, sem checar senha.
 */
function loginPorId($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) return false;

    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['nome'] = $user['nome'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['tipo'] = $user['tipo'];

    if ($user['tipo'] === 'aluno') {
        $stmt2 = $db->prepare("SELECT id FROM alunos WHERE usuario_id = ?");
        $stmt2->execute([$user['id']]);
        $_SESSION['perfil_id'] = $stmt2->fetchColumn() ?: null;
    } else {
        $stmt2 = $db->prepare("SELECT id, nome_empresa, logo FROM empresas WHERE usuario_id = ?");
        $stmt2->execute([$user['id']]);
        $empresa = $stmt2->fetch();
        $_SESSION['perfil_id'] = $empresa['id'] ?? null;
        $_SESSION['nome_empresa'] = $empresa['nome_empresa'] ?? '';
        $_SESSION['logo'] = $empresa['logo'] ?? '';
    }
    return true;
}
