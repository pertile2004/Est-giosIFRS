<?php
$pageTitle = 'Painel da Empresa';
require_once __DIR__ . '/../includes/auth.php';
requireEmpresa();

$db = getDB();
$empresaId = $_SESSION['perfil_id'];

$vagas = $db->prepare("SELECT v.*, (SELECT COUNT(*) FROM candidaturas WHERE vaga_id=v.id) AS total_cand FROM vagas v WHERE empresa_id=? ORDER BY criado_em DESC");
$vagas->execute([$empresaId]);
$vagas = $vagas->fetchAll();

$totalVagas = count($vagas);
$totalCand = array_sum(array_column($vagas, 'total_cand'));

include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <h1>Painel da Empresa</h1>

  <div class="kpi" style="margin-top:24px;">
    <div class="card"><div class="num"><?= $totalVagas ?></div>Vagas Publicadas</div>
    <div class="card"><div class="num"><?= $totalCand ?></div>Candidaturas Recebidas</div>
  </div>

  <div class="card">
    <h2>Minhas vagas</h2>
    <?php if (empty($vagas)): ?>
      <p style="margin-top:12px;">Nenhuma vaga publicada ainda.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Título</th><th>Modalidade</th><th>Bolsa</th><th>Candidaturas</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($vagas as $v): ?>
            <tr>
              <td><?= htmlspecialchars($v['titulo']) ?></td>
              <td><?= ucfirst($v['modalidade']) ?></td>
              <td>R$ <?= number_format($v['bolsa'], 0, ',', '.') ?></td>
              <td><?= $v['total_cand'] ?></td>
              <td><span class="badge badge-<?= $v['ativa']?'green':'yellow' ?>"><?= $v['ativa']?'Ativa':'Pausada' ?></span></td>
              <td>
                <?php if ($v['total_cand'] > 0): ?>
                  <a class="btn" href="/teste/empresa/candidatos.php?vaga_id=<?= (int)$v['id'] ?>" style="padding:4px 10px;font-size:.85rem;">Ver candidatos</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
