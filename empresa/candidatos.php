<?php
$pageTitle = 'Candidatos da Vaga';
require_once __DIR__ . '/../includes/auth.php';
requireEmpresa();

$db = getDB();
$empresaId = $_SESSION['perfil_id'];
$vagaId = (int)($_GET['vaga_id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM vagas WHERE id=? AND empresa_id=?");
$stmt->execute([$vagaId, $empresaId]);
$vaga = $stmt->fetch();
if (!$vaga) { header('Location: /teste/dashboard/empresa.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candId = (int)($_POST['candidatura_id'] ?? 0);
    $novoStatus = $_POST['status'] ?? '';
    if ($candId && in_array($novoStatus, ['visualizado','aprovado','recusado'], true)) {
        $st = $db->prepare("
            UPDATE candidaturas c
            JOIN vagas v ON c.vaga_id = v.id
            SET c.status = ?
            WHERE c.id = ? AND v.empresa_id = ?
        ");
        $st->execute([$novoStatus, $candId, $empresaId]);
    }
    header('Location: /teste/empresa/candidatos.php?vaga_id=' . $vagaId);
    exit;
}

$db->prepare("UPDATE candidaturas SET status='visualizado' WHERE vaga_id=? AND status='pendente'")->execute([$vagaId]);

$stmt = $db->prepare("
    SELECT c.id, c.status, c.criado_em,
           u.nome AS nome_aluno, u.email,
           a.curso, a.universidade, a.semestre, a.cidade, a.estado
    FROM candidaturas c
    JOIN alunos a ON c.aluno_id = a.id
    JOIN usuarios u ON a.usuario_id = u.id
    WHERE c.vaga_id = ?
    ORDER BY c.criado_em DESC
");
$stmt->execute([$vagaId]);
$candidatos = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <a href="/teste/dashboard/empresa.php">← Voltar ao painel</a>
  <h1 style="margin-top:12px;">Candidatos: <?= htmlspecialchars($vaga['titulo']) ?></h1>

  <div class="card" style="margin-top:24px;">
    <?php if (empty($candidatos)): ?>
      <p>Nenhum candidato ainda.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Nome</th><th>Curso / Universidade</th><th>Localização</th><th>Status</th><th>Ações</th></tr></thead>
        <tbody>
          <?php foreach ($candidatos as $c): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($c['nome_aluno']) ?></strong><br>
                <small style="color:#666;"><?= htmlspecialchars($c['email']) ?></small>
              </td>
              <td>
                <?= htmlspecialchars($c['curso'] ?? '—') ?><br>
                <small style="color:#666;"><?= htmlspecialchars($c['universidade'] ?? '') ?><?= $c['semestre'] ? ' · ' . (int)$c['semestre'] . 'º sem' : '' ?></small>
              </td>
              <td><?= htmlspecialchars(($c['cidade'] ?? '') . ($c['estado'] ? '/' . $c['estado'] : '')) ?: '—' ?></td>
              <td>
                <?php
                  $cor = ['pendente'=>'yellow','visualizado'=>'blue','aprovado'=>'green','recusado'=>'red'][$c['status']] ?? '';
                ?>
                <span class="badge <?= $cor ? 'badge-' . $cor : '' ?>"><?= ucfirst($c['status']) ?></span>
              </td>
              <td>
                <?php if ($c['status'] !== 'aprovado' && $c['status'] !== 'recusado'): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="candidatura_id" value="<?= (int)$c['id'] ?>">
                    <input type="hidden" name="status" value="aprovado">
                    <button class="btn" type="submit" style="background:#10B981;padding:4px 10px;font-size:.85rem;">Aprovar</button>
                  </form>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="candidatura_id" value="<?= (int)$c['id'] ?>">
                    <input type="hidden" name="status" value="recusado">
                    <button class="btn" type="submit" style="background:#EF4444;padding:4px 10px;font-size:.85rem;">Recusar</button>
                  </form>
                <?php else: ?>
                  <small style="color:#666;">decidido</small>
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
