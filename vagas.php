<?php
$pageTitle = 'InternSHIP Conect — Vagas';
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
$q          = trim($_GET['q'] ?? '');
$modalidade = $_GET['modalidade'] ?? '';

$where = ['v.ativa = 1'];
$params = [];
if ($q) {
    $where[] = '(v.titulo LIKE ? OR v.descricao LIKE ?)';
    $params[] = "%$q%"; $params[] = "%$q%";
}
if ($modalidade) {
    $where[] = 'v.modalidade = ?';
    $params[] = $modalidade;
}
$sql = "SELECT v.*, e.nome_empresa FROM vagas v JOIN empresas e ON v.empresa_id=e.id WHERE " . implode(' AND ', $where) . " ORDER BY v.criado_em DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$vagas = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="container">
  <h2>Vagas disponíveis</h2>

  <form method="GET" class="filter" style="margin-top:16px;">
    <input type="text" name="q" placeholder="Buscar..." value="<?= htmlspecialchars($q) ?>">
    <select name="modalidade">
      <option value="">Qualquer modalidade</option>
      <option value="presencial" <?= $modalidade==='presencial'?'selected':'' ?>>Presencial</option>
      <option value="remoto"     <?= $modalidade==='remoto'?'selected':'' ?>>Remoto</option>
      <option value="hibrido"    <?= $modalidade==='hibrido'?'selected':'' ?>>Híbrido</option>
    </select>
    <button class="btn">Filtrar</button>
  </form>

  <?php if (empty($vagas)): ?>
    <p>Nenhuma vaga encontrada.</p>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($vagas as $v): ?>
        <div class="card">
          <h3><?= htmlspecialchars($v['titulo']) ?></h3>
          <p style="color:#666;margin:6px 0;"><?= htmlspecialchars($v['nome_empresa']) ?> · <?= $v['cidade'] ?>/<?= $v['estado'] ?></p>
          <span class="badge"><?= ucfirst($v['modalidade']) ?></span>
          <p style="margin:12px 0;font-weight:bold;color:#10B981;">R$ <?= number_format($v['bolsa'], 0, ',', '.') ?>/mês</p>
          <a class="btn" href="/teste/vaga.php?id=<?= $v['id'] ?>">Ver Vaga</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
