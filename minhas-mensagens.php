<?php
$pageTitle = 'Minhas mensagens · InternSHIP Conect';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$uid = (int)$_SESSION['usuario_id'];

$mensagens = $db->prepare("
    SELECT mc.*,
           (SELECT COUNT(*) FROM respostas_contato rc WHERE rc.mensagem_id=mc.id AND rc.remetente_tipo='coordenacao' AND rc.lida=0) AS nao_lidas,
           (SELECT COUNT(*) FROM respostas_contato rc WHERE rc.mensagem_id=mc.id) AS total_respostas,
           (SELECT MAX(criado_em) FROM respostas_contato rc WHERE rc.mensagem_id=mc.id) AS ultima_resp
      FROM mensagens_contato mc
     WHERE mc.usuario_id = ?
     ORDER BY (nao_lidas > 0) DESC, COALESCE(ultima_resp, mc.criado_em) DESC
");
$mensagens->execute([$uid]);
$mensagens = $mensagens->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:820px;padding:32px 24px 64px;">
  <h1 style="margin-bottom:6px;">Minhas mensagens</h1>
  <p class="text-muted">Suas conversas com a Coordenação InternSHIP.</p>

  <div style="margin:24px 0;">
    <a href="/teste/contato.php" class="btn btn-primary">+ Nova mensagem</a>
  </div>

  <?php if (empty($mensagens)): ?>
    <div class="card"><div class="card-body">
      <div class="empty-state" style="padding:40px 20px;text-align:center;">
        <h3 style="margin-bottom:6px;">Você ainda não tem mensagens</h3>
        <p class="text-muted">Quando enviar uma mensagem pela página de Contato, ela vai aparecer aqui.</p>
      </div>
    </div></div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:12px;">
      <?php foreach ($mensagens as $m): ?>
        <a href="/teste/conversa-contato.php?id=<?= (int)$m['id'] ?>"
           class="card"
           style="text-decoration:none;color:inherit;display:block;<?= (int)$m['nao_lidas'] > 0 ? 'border-left:4px solid var(--primary);' : '' ?>">
          <div class="card-body">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
              <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:1.02rem;color:var(--text);"><?= htmlspecialchars($m['assunto']) ?></div>
                <div class="text-muted text-sm" style="margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                  <?= htmlspecialchars(mb_substr($m['mensagem'], 0, 120)) ?><?= mb_strlen($m['mensagem']) > 120 ? '…' : '' ?>
                </div>
                <div class="text-muted" style="font-size:.78rem;margin-top:8px;">
                  <?= (int)$m['total_respostas'] ?> resposta<?= (int)$m['total_respostas'] === 1 ? '' : 's' ?>
                  · Atualizada em <?= date('d/m/Y H:i', strtotime($m['ultima_resp'] ?? $m['criado_em'])) ?>
                </div>
              </div>
              <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-end;">
                <?php if ((int)$m['nao_lidas'] > 0): ?>
                  <span class="badge" style="background:var(--primary);color:#fff;"><?= (int)$m['nao_lidas'] ?> nova<?= (int)$m['nao_lidas'] === 1 ? '' : 's' ?></span>
                <?php endif; ?>
                <span class="badge <?= $m['status'] === 'resolvida' ? 'badge-green' : ($m['status'] === 'nova' ? 'badge-red' : 'badge-blue') ?>">
                  <?= ucfirst($m['status']) ?>
                </span>
              </div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
