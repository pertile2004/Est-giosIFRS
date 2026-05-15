<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';

function isLoggedIn() { return isset($_SESSION['usuario_id']); }
function isAluno()    { return ($_SESSION['tipo'] ?? '') === 'aluno'; }
function isEmpresa()  { return ($_SESSION['tipo'] ?? '') === 'empresa'; }

function requireLogin() {
    if (!isLoggedIn()) { header('Location: /teste/login.php'); exit; }
}
function requireAluno() {
    requireLogin();
    if (!isAluno()) { header('Location: /teste/'); exit; }
}
function requireEmpresa() {
    requireLogin();
    if (!isEmpresa()) { header('Location: /teste/'); exit; }
}

function login($email, $senha) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nome']       = $user['nome'];
        $_SESSION['tipo']       = $user['tipo'];
        if ($user['tipo'] === 'aluno') {
            $st = $db->prepare("SELECT id FROM alunos WHERE usuario_id=?");
            $st->execute([$user['id']]);
            $_SESSION['perfil_id'] = $st->fetchColumn();
        } else {
            $st = $db->prepare("SELECT id FROM empresas WHERE usuario_id=?");
            $st->execute([$user['id']]);
            $_SESSION['perfil_id'] = $st->fetchColumn();
        }
        return true;
    }
    return false;
}

function registrarAluno($nome, $email, $senha) {
    $db = getDB();
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    try {
        $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'aluno')");
        $stmt->execute([$nome, $email, $hash]);
        $userId = $db->lastInsertId();
        $db->prepare("INSERT INTO alunos (usuario_id) VALUES (?)")->execute([$userId]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function registrarEmpresa($nome, $email, $senha, $nomeEmpresa) {
    $db = getDB();
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    try {
        $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'empresa')");
        $stmt->execute([$nome, $email, $hash]);
        $userId = $db->lastInsertId();
        $db->prepare("INSERT INTO empresas (usuario_id, nome_empresa) VALUES (?, ?)")->execute([$userId, $nomeEmpresa]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}
