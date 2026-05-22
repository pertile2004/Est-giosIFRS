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
