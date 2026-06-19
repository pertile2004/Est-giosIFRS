<?php
require_once __DIR__ . '/../includes/auth.php';
requireEmpresa();

$db = getDB();
$empresaId = $_SESSION['perfil_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remover_logo'])) {
        $stmt = $db->prepare("SELECT logo FROM empresas WHERE id = ?");
        $stmt->execute([$empresaId]);
        $caminho = $stmt->fetchColumn();
        if ($caminho && !preg_match('#^https?://#', $caminho)) {
            $arq = __DIR__ . '/../' . ltrim($caminho, '/');
            if (is_file($arq)) @unlink($arq);
        }
        $db->prepare("UPDATE empresas SET logo = NULL WHERE id = ?")->execute([$empresaId]);
        $_SESSION['logo'] = '';
        header('Location: /teste/dashboard/empresa.php?saved=1');
        exit;
    }

    $nomeEmpresa = trim($_POST['nome_empresa'] ?? '');
    $setor = trim($_POST['setor'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $site = trim($_POST['site'] ?? '');

    $db->prepare("UPDATE empresas SET nome_empresa=?,setor=?,descricao=?,cidade=?,site=? WHERE id=?")
       ->execute([$nomeEmpresa, $setor, $descricao, $cidade, $site, $empresaId]);
    $_SESSION['nome_empresa'] = $nomeEmpresa;

    if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $caminho = salvarLogoEmpresa($empresaId, $_FILES['logo']);
        if ($caminho) {
            $_SESSION['logo'] = $caminho;
        }
    }
}

header('Location: /teste/dashboard/empresa.php?saved=1');
exit;
