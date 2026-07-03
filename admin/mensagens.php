<?php
$pageTitle = 'Coordenação · Mensagens';
require_once __DIR__ . '/../includes/auth.php';
requireCoordenacao();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $acao = $_POST['acao'] ?? '';
    if ($id) {
        if ($acao === 'ler') {
            $db->prepare("UPDATE mensagens_contato SET status='lida' WHERE id=? AND status='nova'")->execute([$id]);
        } elseif ($acao === 'resolver') {
            $db->prepare("UPDATE mensagens_contato SET status='resolvida' WHERE id=?")->execute([$id]);
        } elseif ($acao === 'reabrir') {
            $db->prepare("UPDATE mensagens_contato SET status='lida' WHERE id=?")->execute([$id]);
        } elseif ($acao === 'excluir') {
            $db->prepare("DELETE FROM mensagens_contato WHERE id=?")->execute([$id]);
        }
    }
    header('Location: /teste/admin/mensagens.php');
    exit;
}

$filtro = $_GET['status'] ?? 'todas';
$where = in_array($filtro, ['nova','lida','resolvida'], true) ? "WHERE mc.status = " . $db->quote($filtro) : '';
$mensagens = $db->query("
    SELECT mc.*,
           (SELECT COUNT(*) FROM respostas_contato rc WHERE rc.mensagem_id=mc.id AND rc.remetente_tipo='usuario' AND rc.lida=0) AS nao_lidas,
           (SELECT COUNT(*) FROM respostas_contato rc WHERE rc.mensagem_id=mc.id) AS total_respostas
      FROM mensagens_contato mc
      $where
     ORDER BY (mc.status='nova') DESC, (nao_lidas > 0) DESC, mc.criado_em DESC
")->fetchAll();

$contagem = $db->query("
    SELECT
      SUM(status='nova') AS novas,
      SUM(status='lida') AS lidas,
      SUM(status='resolvida') AS resolvidas,
      COUNT(*) AS total
    FROM mensagens_contato
")->fetch();

function badgeStatusMsg($s) {
    return match($s) {
        'nova'      => '<span class="badge badge-red">Nova</span>',
        'lida'      => '<span class="badge badge-blue">Lida</span>',
        'resolvida' => '<span class="badge badge-green">Resolvida</span>',
        default     => '<span class="badge badge-gray">—</span>',
    };
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:1000px;padding:32px 24px;">
  <a href="/teste/admin/" class="btn btn-ghost btn-sm">← Voltar ao painel</a>
  <h1 style="margin-top:12px;">Mensagens de contato</h1>
  <p class="text-muted">Mensagens enviadas pela página de Contato do site.</p>

  <div style="display:flex;gap:8px;flex-wrap:wrap;margin:20px 0;">
    <?php
      $abas = [
        'todas'     => 'Todas (' . (int)$contagem['total'] . ')',
        'nova'      => 'Novas (' . (int)$contagem['novas'] . ')',
        'lida'      => 'Lidas (' . (int)$contagem['lidas'] . ')',
        'resolvida' => 'Resolvidas (' . (int)$contagem['resolvidas'] . ')',
      ];
      foreach ($abas as $k => $label):
        $ativo = ($filtro === $k) || ($k === 'todas' && !in_array($filtro, ['nova','lida','resolvida'], true));
    ?>
      <a href="/teste/admin/mensagens.php?status=<?= $k ?>"
         class="btn btn-sm <?= $ativo ? 'btn-primary' : 'btn-ghost' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($mensagens)): ?>
    <div class="card"><div class="card-body">
      <div class="empty-state">
        <h3>Nenhuma mensagem</h3>
        <p>Quando alguém enviar uma mensagem pela página de Contato, ela aparece aqui.</p>
      </div>
    </div></div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:14px;">
      <?php foreach ($mensagens as $m): ?>
      <div class="card" style="<?= $m['status'] === 'nova' ? 'border-left:4px solid var(--danger);' : '' ?>">
        <div class="card-body">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
            <div>
              <div style="font-weight:700;font-size:1.05rem;"><?= htmlspecialchars($m['assunto']) ?></div>
              <div class="text-muted text-sm" style="margin-top:2px;">
                <?= htmlspecialchars($m['nome']) ?> ·
                <a href="mailto:<?= htmlspecialchars($m['email']) ?>" style="color:var(--primary);"><?= htmlspecialchars($m['email']) ?></a>
                · <?= date('d/m/Y H:i', strtotime($m['criado_em'])) ?>
              </div>
            </div>
            <?= badgeStatusMsg($m['status']) ?>
          </div>

          <p style="margin-top:12px;color:var(--gray-700);white-space:pre-wrap;"><?= htmlspecialchars($m['mensagem']) ?></p>

          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;">
            <a href="/teste/conversa-contato.php?id=<?= (int)$m['id'] ?>" class="btn btn-primary btn-sm">
              Abrir conversa
              <?php if ((int)$m['nao_lidas'] > 0): ?>
                <span style="background:#fff;color:var(--primary);padding:1px 7px;border-radius:10px;font-size:.72rem;font-weight:800;margin-left:6px;"><?= (int)$m['nao_lidas'] ?></span>
              <?php elseif ((int)$m['total_respostas'] > 0): ?>
                <span style="opacity:.8;font-size:.78rem;margin-left:6px;">· <?= (int)$m['total_respostas'] ?></span>
              <?php endif; ?>
            </a>

            <a href="mailto:<?= htmlspecialchars($m['email']) ?>?subject=<?= rawurlencode('Re: ' . $m['assunto']) ?>"
               class="btn btn-ghost btn-sm">E-mail</a>

            <?php if ($m['status'] === 'nova'): ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <button name="acao" value="ler" class="btn btn-ghost btn-sm">Marcar como lida</button>
              </form>
            <?php endif; ?>

            <?php if ($m['status'] !== 'resolvida'): ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <button name="acao" value="resolver" class="btn btn-ghost btn-sm" style="color:var(--accent);">Marcar como resolvida</button>
              </form>
            <?php else: ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <button name="acao" value="reabrir" class="btn btn-ghost btn-sm">Reabrir</button>
              </form>
            <?php endif; ?>

            <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir esta mensagem?');">
              <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
              <button name="acao" value="excluir" class="btn btn-ghost btn-sm" style="color:var(--danger);">Excluir</button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
