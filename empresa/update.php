<?php
require_once __DIR__ . '/../includes/auth.php';
requireEmpresa();

$db = getDB();
$empresaId = $_SESSION['perfil_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomeEmpresa = trim($_POST['nome_empresa'] ?? '');
    $setor = trim($_POST['setor'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $site = trim($_POST['site'] ?? '');

    $db->prepare("UPDATE empresas SET nome_empresa=?,setor=?,descricao=?,cidade=?,site=? WHERE id=?")
       ->execute([$nomeEmpresa, $setor, $descricao, $cidade, $site, $empresaId]);
    $_SESSION['nome_empresa'] = $nomeEmpresa;
}

header('Location: /teste/dashboard/empresa.php?saved=1');
exit;
