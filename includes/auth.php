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
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nome'] = $user['nome'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['tipo'] = $user['tipo'];

        if ($user['tipo'] === 'aluno') {
            $stmt2 = $db->prepare("SELECT id FROM alunos WHERE usuario_id = ?");
            $stmt2->execute([$user['id']]);
            $aluno = $stmt2->fetch();
            $_SESSION['perfil_id'] = $aluno['id'] ?? null;
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
    return false;
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
