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
        } elseif ($acao === 'restringir') {
            $db->prepare("UPDATE vagas SET restrita=1, motivo_restricao=? WHERE id=?")
               ->execute([mb_substr(trim($_POST['motivo'] ?? ''), 0, 255), $vagaId]);
        } elseif ($acao === 'liberar') {
            $db->prepare("UPDATE vagas SET restrita=0, motivo_restricao=NULL WHERE id=?")->execute([$vagaId]);
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
    SELECT v.id, v.titulo, v.area, v.cidade, v.estado, v.modalidade, v.bolsa, v.ativa, v.restrita, v.motivo_restricao, v.views, v.criado_em,
           e.nome_empresa,
           (SELECT COUNT(*) FROM candidaturas WHERE vaga_id = v.id) AS total_cand
      FROM vagas v
      JOIN empresas e ON v.empresa_id = e.id
      $where
     ORDER BY v.criado_em DESC
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:1400px;padding:32px 24px;">
  <a href="/teste/admin/" class="btn btn-ghost btn-sm">← Voltar ao painel</a>
  <h1 style="margin-top:12px;">Vagas cadastradas</h1>

  <div style="display:flex;gap:8px;margin:16px 0;">
    <a href="?filtro=todas" class="btn btn-<?= $filtro === 'todas' ? 'primary' : 'ghost' ?> btn-sm">Todas</a>
    <a href="?filtro=ativas" class="btn btn-<?= $filtro === 'ativas' ? 'primary' : 'ghost' ?> btn-sm">Ativas</a>
    <a href="?filtro=pausadas" class="btn btn-<?= $filtro === 'pausadas' ? 'primary' : 'ghost' ?> btn-sm">Pausadas</a>
  </div>

  <div class="table-wrap compact">
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
              <?php if ($v['restrita']): ?>
                <span class="badge badge-red" title="<?= htmlspecialchars($v['motivo_restricao'] ?? '') ?>">Restrita</span>
              <?php elseif ($v['ativa']): ?>
                <span class="badge badge-green">Ativa</span>
              <?php else: ?>
                <span class="badge badge-gray">Pausada</span>
              <?php endif; ?>
            </td>
            <td>
              <form method="POST" style="display:flex;gap:6px;flex-wrap:wrap;">
                <input type="hidden" name="vaga_id" value="<?= (int)$v['id'] ?>">
                <?php if ($v['restrita']): ?>
                  <button name="acao" value="liberar" class="btn btn-ghost btn-sm" style="color:var(--accent);">Liberar</button>
                <?php else: ?>
                  <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger);"
                          onclick="abrirModalRestringir(<?= (int)$v['id'] ?>, '<?= htmlspecialchars(addslashes($v['titulo']), ENT_QUOTES) ?>')">Restringir</button>
                <?php endif; ?>
                <?php if ($v['ativa']): ?>
                  <button name="acao" value="pausar" class="btn btn-ghost btn-sm">Pausar</button>
                <?php else: ?>
                  <button name="acao" value="ativar" class="btn btn-ghost btn-sm">Ativar</button>
                <?php endif; ?>
                <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger);"
                        onclick="abrirModalExcluir(<?= (int)$v['id'] ?>, '<?= htmlspecialchars(addslashes($v['titulo']), ENT_QUOTES) ?>')">Excluir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Restringir -->
<div class="modal-overlay" id="modal-restringir" onclick="if(event.target===this)fecharModal('modal-restringir')">
  <div class="modal-card">
    <h3 class="modal-title">Restringir vaga</h3>
    <p class="modal-sub">A vaga <strong id="modal-restr-titulo"></strong> ficará indisponível para candidaturas.</p>
    <form method="POST" id="form-restringir">
      <input type="hidden" name="vaga_id" id="modal-restr-vaga-id">
      <input type="hidden" name="acao" value="restringir">
      <label class="form-label">Motivo da restrição *</label>
      <textarea name="motivo" class="form-control" rows="4" required
                placeholder="Ex.: vaga fora das políticas, dados falsos, salário incompatível..."
                maxlength="255"></textarea>
      <div class="form-hint">Máx. 255 caracteres. Será exibido para a empresa.</div>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" onclick="fecharModal('modal-restringir')">Cancelar</button>
        <button type="submit" class="btn btn-primary" style="background:var(--danger);border-color:var(--danger);">Restringir</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Excluir -->
<div class="modal-overlay" id="modal-excluir" onclick="if(event.target===this)fecharModal('modal-excluir')">
  <div class="modal-card">
    <h3 class="modal-title">Excluir vaga</h3>
    <p class="modal-sub">A vaga <strong id="modal-excl-titulo"></strong> será removida em definitivo, junto com todas as candidaturas.</p>
    <p class="modal-sub" style="color:var(--danger);">Esta ação não pode ser desfeita.</p>
    <form method="POST">
      <input type="hidden" name="vaga_id" id="modal-excl-vaga-id">
      <input type="hidden" name="acao" value="excluir">
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" onclick="fecharModal('modal-excluir')">Cancelar</button>
        <button type="submit" class="btn btn-primary" style="background:var(--danger);border-color:var(--danger);">Excluir definitivamente</button>
      </div>
    </form>
  </div>
</div>

<style>
.modal-overlay{
  display:none; position:fixed; inset:0; z-index:1000;
  background:rgba(0,0,0,.6); backdrop-filter:blur(4px);
  align-items:center; justify-content:center; padding:24px;
}
.modal-overlay.open{ display:flex; }
.modal-card{
  background:var(--surface); color:var(--text);
  border:1px solid var(--border);
  border-radius:16px; padding:28px;
  width:100%; max-width:480px;
  box-shadow:0 20px 60px rgba(0,0,0,.4);
  animation:modalIn .18s ease-out;
}
@keyframes modalIn{ from{ transform:translateY(12px); opacity:0 } to{ transform:none; opacity:1 } }
.modal-title{ margin:0 0 8px; font-size:1.25rem; }
.modal-sub{ color:var(--gray-600); font-size:.92rem; margin:0 0 16px; }
.modal-actions{ display:flex; gap:8px; justify-content:flex-end; margin-top:20px; }
</style>

<script>
function abrirModal(id){
  document.getElementById(id).classList.add('open');
  document.body.style.overflow='hidden';
}
function fecharModal(id){
  document.getElementById(id).classList.remove('open');
  document.body.style.overflow='';
}
function abrirModalRestringir(vagaId, titulo){
  document.getElementById('modal-restr-vaga-id').value = vagaId;
  document.getElementById('modal-restr-titulo').textContent = titulo;
  document.querySelector('#form-restringir textarea').value = '';
  abrirModal('modal-restringir');
  setTimeout(()=>document.querySelector('#form-restringir textarea').focus(), 100);
}
function abrirModalExcluir(vagaId, titulo){
  document.getElementById('modal-excl-vaga-id').value = vagaId;
  document.getElementById('modal-excl-titulo').textContent = titulo;
  abrirModal('modal-excluir');
}
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    fecharModal('modal-restringir');
    fecharModal('modal-excluir');
  }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
