<?php
$pageTitle = 'InternSHIP Conect — Início';
require_once __DIR__ . '/includes/auth.php';
$db = getDB();
$totalVagas = $db->query("SELECT COUNT(*) FROM vagas WHERE ativa=1")->fetchColumn();
include __DIR__ . '/includes/header.php';
?>
<div class="container">
  <h1>InternSHIP Conect</h1>
  <p style="margin-top:8px;">Plataforma de estágios. Atualmente <strong><?= $totalVagas ?></strong> vagas ativas.</p>
  <a href="/teste/vagas.php" class="btn" style="margin-top:16px;">Ver todas as vagas</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
