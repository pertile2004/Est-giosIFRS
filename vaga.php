<?php
$pageTitle = 'IntenSHIP Conect — Vaga';
require_once __DIR__ . '/includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /estagios/etapas/01-base-e-vagas-publicas/vagas.php'); exit; }

$db = getDB();
$stmt = $db->prepare("SELECT v.*, e.nome_empresa, e.descricao AS empresa_desc FROM vagas v JOIN empresas e ON v.empresa_id=e.id WHERE v.id=? AND v.ativa=1");
$stmt->execute([$id]);
$vaga = $stmt->fetch();
if (!$vaga) { header('Location: /estagios/etapas/01-base-e-vagas-publicas/vagas.php'); exit; }

include __DIR__ . '/includes/header.php';
?>
<div class="container">
  <a href="/estagios/etapas/01-base-e-vagas-publicas/vagas.php">← Voltar</a>
  <div class="card" style="margin-top:16px;">
    <h1><?= htmlspecialchars($vaga['titulo']) ?></h1>
    <p style="color:#666;margin-top:6px;"><?= htmlspecialchars($vaga['nome_empresa']) ?> · <?= $vaga['cidade'] ?>/<?= $vaga['estado'] ?></p>
    <p style="margin-top:8px;"><span class="badge"><?= ucfirst($vaga['modalidade']) ?></span></p>
    <p style="font-size:1.4rem;font-weight:bold;margin-top:16px;color:#10B981;">R$ <?= number_format($vaga['bolsa'], 0, ',', '.') ?>/mês</p>
    <h3 style="margin-top:24px;">Descrição</h3>
    <p style="margin-top:8px;"><?= nl2br(htmlspecialchars($vaga['descricao'])) ?></p>
    <h3 style="margin-top:24px;">Sobre a empresa</h3>
    <p style="margin-top:8px;"><?= htmlspecialchars($vaga['empresa_desc'] ?? '—') ?></p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
