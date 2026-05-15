<?php
$pageTitle = 'Painel do Estudante';
require_once __DIR__ . '/../includes/auth.php';
requireAluno();

$db = getDB();
$alunoId = $_SESSION['perfil_id'];

$candidaturas = $db->prepare("
    SELECT c.*, v.titulo, v.bolsa, e.nome_empresa
    FROM candidaturas c
    JOIN vagas v ON c.vaga_id=v.id
    JOIN empresas e ON v.empresa_id=e.id
    WHERE c.aluno_id=?
    ORDER BY c.criado_em DESC
");
$candidaturas->execute([$alunoId]);
$candidaturas = $candidaturas->fetchAll();

$total = count($candidaturas);
$aprovadas = count(array_filter($candidaturas, fn($c) => $c['status']==='aprovado'));
$pendentes = count(array_filter($candidaturas, fn($c) => $c['status']==='pendente'));

include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <h1>Olá, <?= htmlspecialchars($_SESSION['nome']) ?>!</h1>

  <div class="kpi" style="margin-top:24px;">
    <div class="card"><div class="num"><?= $total ?></div>Candidaturas</div>
    <div class="card"><div class="num"><?= $aprovadas ?></div>Aprovadas</div>
    <div class="card"><div class="num"><?= $pendentes ?></div>Pendentes</div>
  </div>

  <div class="card">
    <h2>Minhas candidaturas</h2>
    <?php if (empty($candidaturas)): ?>
      <p style="margin-top:12px;">Você ainda não se candidatou para nenhuma vaga.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Vaga</th><th>Empresa</th><th>Bolsa</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($candidaturas as $c): ?>
            <tr>
              <td><?= htmlspecialchars($c['titulo']) ?></td>
              <td><?= htmlspecialchars($c['nome_empresa']) ?></td>
              <td>R$ <?= number_format($c['bolsa'], 0, ',', '.') ?></td>
              <td><span class="badge badge-<?= $c['status']==='aprovado'?'green':'yellow' ?>"><?= ucfirst($c['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
