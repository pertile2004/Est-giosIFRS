<?php
require_once __DIR__ . '/includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /teste/vagas.php'); exit; }

$db = getDB();
$stmt = $db->prepare("SELECT v.*, e.nome_empresa, e.descricao AS empresa_desc FROM vagas v JOIN empresas e ON v.empresa_id=e.id WHERE v.id=? AND v.ativa=1");
$stmt->execute([$id]);
$vaga = $stmt->fetch();
if (!$vaga) { header('Location: /teste/vagas.php'); exit; }

$jaCandidatou = false;
if (isLoggedIn() && isAluno()) {
    $st = $db->prepare("SELECT id FROM candidaturas WHERE aluno_id=? AND vaga_id=?");
    $st->execute([$_SESSION['perfil_id'], $id]);
    $jaCandidatou = (bool)$st->fetch();
}

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidatar'])) {
    if (!isLoggedIn()) { header('Location: /teste/login.php'); exit; }
    if (isAluno() && !$jaCandidatou) {
        $st = $db->prepare("INSERT INTO candidaturas (aluno_id, vaga_id) VALUES (?, ?)");
        $st->execute([$_SESSION['perfil_id'], $id]);
        $jaCandidatou = true;
        $mensagem = 'Candidatura enviada!';
    }
}

$pageTitle = $vaga['titulo'];
include __DIR__ . '/includes/header.php';
?>
<div class="container">
  <a href="/teste/vagas.php">← Voltar</a>
  <div class="card" style="margin-top:16px;">
    <?php if ($mensagem): ?>
      <p style="background:#D1FAE5;color:#065F46;padding:10px;border-radius:6px;margin-bottom:12px;"><?= $mensagem ?></p>
    <?php endif; ?>
    <h1><?= htmlspecialchars($vaga['titulo']) ?></h1>
    <p style="color:#666;margin-top:6px;"><?= htmlspecialchars($vaga['nome_empresa']) ?> · <?= $vaga['cidade'] ?>/<?= $vaga['estado'] ?></p>
    <p style="margin-top:8px;"><span class="badge"><?= ucfirst($vaga['modalidade']) ?></span></p>
    <p style="font-size:1.4rem;font-weight:bold;margin-top:16px;color:#10B981;">R$ <?= number_format($vaga['bolsa'], 0, ',', '.') ?>/mês</p>

    <h3 style="margin-top:24px;">Descrição</h3>
    <p style="margin-top:8px;"><?= nl2br(htmlspecialchars($vaga['descricao'])) ?></p>

    <h3 style="margin-top:24px;">Sobre a empresa</h3>
    <p style="margin-top:8px;"><?= htmlspecialchars($vaga['empresa_desc'] ?? '—') ?></p>

    <div style="margin-top:24px;">
      <?php if ($jaCandidatou): ?>
        <p style="background:#D1FAE5;color:#065F46;padding:12px;border-radius:6px;">Você já se candidatou.</p>
      <?php elseif (isLoggedIn() && isAluno()): ?>
        <form method="POST"><button class="btn" name="candidatar" type="submit">Candidatar-se</button></form>
      <?php elseif (!isLoggedIn()): ?>
        <a class="btn" href="/teste/login.php">Entrar para se candidatar</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
