<?php
$pageTitle = 'Admin · Vagas';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vagaId = (int)($_POST['vaga_id'] ?? 0);
    $acao = $_POST['acao'] ?? '';
    if ($vagaId) {
        if ($acao === 'pausar') {
            $db->prepare("UPDATE vagas SET ativa=0 WHERE id=?")->execute([$vagaId]);
        } elseif ($acao === 'ativar') {
            $db->prepare("UPDATE vagas SET ativa=1 WHERE id=?")->execute([$vagaId]);
        } elseif ($acao === 'excluir') {
            $db->prepare("DELETE FROM vagas WHERE id=?")->execute([$vagaId]);
        }
    }
    header('Location: /teste/admin/vagas.php');
    exit;
}

$filtro = $_GET['filtro'] ?? 'todas'; // todas | ativas | pausadas
$where = '';
if ($filtro === 'ativas')   $where = 'WHERE v.ativa = 1';
if ($filtro === 'pausadas') $where = 'WHERE v.ativa = 0';

$vagas = $db->query("
    SELECT v.id, v.titulo, v.area, v.cidade, v.estado, v.modalidade, v.bolsa, v.ativa, v.views, v.criado_em,
           e.nome_empresa,
           (SELECT COUNT(*) FROM candidaturas WHERE vaga_id = v.id) AS total_cand
      FROM vagas v
      JOIN empresas e ON v.empresa_id = e.id
      $where
     ORDER BY v.criado_em DESC
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:1200px;padding:32px 24px;">
  <a href="/teste/admin/" class="btn btn-ghost btn-sm">← Voltar ao painel</a>
  <h1 style="margin-top:12px;">Vagas cadastradas</h1>

  <div style="display:flex;gap:8px;margin:16px 0;">
    <a href="?filtro=todas" class="btn btn-<?= $filtro === 'todas' ? 'primary' : 'ghost' ?> btn-sm">Todas</a>
    <a href="?filtro=ativas" class="btn btn-<?= $filtro === 'ativas' ? 'primary' : 'ghost' ?> btn-sm">Ativas</a>
    <a href="?filtro=pausadas" class="btn btn-<?= $filtro === 'pausadas' ? 'primary' : 'ghost' ?> btn-sm">Pausadas</a>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Vaga</th>
          <th>Empresa</th>
          <th>Localização</th>
          <th>Modalidade</th>
          <th>Views</th>
          <th>Cand.</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($vagas as $v): ?>
          <tr>
            <td>
              <a href="/teste/vaga.php?id=<?= (int)$v['id'] ?>" target="_blank" class="td-title" style="color:var(--primary);text-decoration:none;">
                <?= htmlspecialchars($v['titulo']) ?>
              </a>
              <div class="text-muted text-sm"><?= htmlspecialchars($v['area']) ?></div>
            </td>
            <td><?= htmlspecialchars($v['nome_empresa']) ?></td>
            <td><?= htmlspecialchars($v['cidade']) ?>/<?= $v['estado'] ?></td>
            <td><?= ucfirst($v['modalidade']) ?></td>
            <td><?= (int)($v['views'] ?? 0) ?></td>
            <td><?= (int)$v['total_cand'] ?></td>
            <td>
              <?php if ($v['ativa']): ?>
                <span class="badge badge-green">Ativa</span>
              <?php else: ?>
                <span class="badge badge-gray">Pausada</span>
              <?php endif; ?>
            </td>
            <td>
              <form method="POST" style="display:flex;gap:6px;">
                <input type="hidden" name="vaga_id" value="<?= (int)$v['id'] ?>">
                <?php if ($v['ativa']): ?>
                  <button name="acao" value="pausar" class="btn btn-ghost btn-sm">Pausar</button>
                <?php else: ?>
                  <button name="acao" value="ativar" class="btn btn-ghost btn-sm">Ativar</button>
                <?php endif; ?>
                <button name="acao" value="excluir" class="btn btn-ghost btn-sm" style="color:var(--danger);" onclick="return confirm('Excluir esta vaga definitivamente?')">Excluir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
