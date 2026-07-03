<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$mensagemId = (int)($_GET['id'] ?? 0);
if (!$mensagemId) {
    header('Location: /teste/');
    exit;
}

// Carrega a mensagem original
$stmt = $db->prepare("SELECT mc.*, u.nome AS usuario_nome FROM mensagens_contato mc LEFT JOIN usuarios u ON u.id = mc.usuario_id WHERE mc.id = ?");
$stmt->execute([$mensagemId]);
$msg = $stmt->fetch();

if (!$msg) {
    http_response_code(404);
    $pageTitle = 'Conversa não encontrada';
    include __DIR__ . '/includes/header.php';
    echo '<div class="container" style="max-width:600px;padding:64px 24px;text-align:center;"><h2>Conversa não encontrada</h2><p class="text-muted">Essa conversa não existe ou foi excluída.</p><a href="/teste/" class="btn btn-primary" style="margin-top:16px;">Voltar</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$souCoordenacao = isCoordenacao() || isAdmin();
$souDono = ($msg['usuario_id'] !== null && (int)$msg['usuario_id'] === (int)$_SESSION['usuario_id']);

if (!$souCoordenacao && !$souDono) {
    http_response_code(403);
    $pageTitle = 'Acesso negado';
    include __DIR__ . '/includes/header.php';
    echo '<div class="container" style="max-width:600px;padding:64px 24px;text-align:center;"><h2>Acesso negado</h2><p class="text-muted">Você não tem permissão para ver esta conversa.</p><a href="/teste/" class="btn btn-primary" style="margin-top:16px;">Voltar</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// POST: envia nova resposta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar'])) {
    $conteudo = trim($_POST['conteudo'] ?? '');
    if ($conteudo !== '') {
        $remetenteTipo = $souCoordenacao ? 'coordenacao' : 'usuario';
        $db->prepare("INSERT INTO respostas_contato (mensagem_id, remetente_tipo, conteudo) VALUES (?, ?, ?)")
           ->execute([$mensagemId, $remetenteTipo, mb_substr($conteudo, 0, 2000)]);
        // Se a coordenação está respondendo, marca a mensagem original como lida
        if ($souCoordenacao && $msg['status'] === 'nova') {
            $db->prepare("UPDATE mensagens_contato SET status='lida' WHERE id=?")->execute([$mensagemId]);
        }
    }
    header("Location: /teste/conversa-contato.php?id={$mensagemId}");
    exit;
}

// Marca respostas do OUTRO lado como lidas ao abrir
$outroLado = $souCoordenacao ? 'usuario' : 'coordenacao';
$db->prepare("UPDATE respostas_contato SET lida=1 WHERE mensagem_id=? AND remetente_tipo=? AND lida=0")
   ->execute([$mensagemId, $outroLado]);
// Se coord entrou, marca como lida também
if ($souCoordenacao && $msg['status'] === 'nova') {
    $db->prepare("UPDATE mensagens_contato SET status='lida' WHERE id=?")->execute([$mensagemId]);
}

// Carrega todas as respostas
$respostas = $db->prepare("SELECT * FROM respostas_contato WHERE mensagem_id=? ORDER BY criado_em ASC");
$respostas->execute([$mensagemId]);
$respostas = $respostas->fetchAll();

$pageTitle = 'Conversa · ' . $msg['assunto'];
include __DIR__ . '/includes/header.php';

$iniciaisCoord = 'C';
$iniciaisUser = mb_strtoupper(mb_substr($msg['nome'], 0, 1));
?>

<style>
.chat-wrap{ max-width:820px; margin:24px auto 64px; padding:0 20px; }
.chat-header{ background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:18px 20px; margin-bottom:16px; }
.chat-thread{ background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:20px; min-height:340px; display:flex; flex-direction:column; gap:14px; }
.msg-row{ display:flex; gap:10px; align-items:flex-end; }
.msg-row.mine{ flex-direction:row-reverse; }
.msg-avatar{ width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg,var(--primary),var(--secondary)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.85rem; flex-shrink:0; }
.msg-avatar.coord{ background:linear-gradient(135deg,#F59E0B,#EF4444); }
.msg-bubble{ max-width:70%; padding:10px 14px; border-radius:14px; background:var(--gray-100); color:var(--text); white-space:pre-wrap; word-wrap:break-word; }
html[data-theme="dark"] .msg-bubble{ background:#2A2338; }
.msg-row.mine .msg-bubble{ background:linear-gradient(135deg,var(--primary),var(--secondary)); color:#fff; }
.msg-time{ font-size:.72rem; color:var(--gray-500); margin-top:4px; }
.msg-row.mine .msg-time{ text-align:right; }
.msg-header-info{ display:flex; align-items:center; gap:6px; margin-bottom:2px; }
.msg-header-info .who{ font-size:.78rem; font-weight:700; color:var(--gray-600); }
.msg-row.mine .msg-header-info{ justify-content:flex-end; }
.day-sep{ text-align:center; color:var(--gray-500); font-size:.78rem; margin:10px 0; text-transform:uppercase; letter-spacing:.06em; }
.chat-input{ background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:14px; margin-top:14px; display:flex; gap:10px; align-items:flex-end; }
.chat-input textarea{ flex:1; resize:none; }
.origem-msg{ background:var(--gray-50); border-left:3px solid var(--primary); padding:12px 14px; border-radius:8px; margin-top:12px; }
html[data-theme="dark"] .origem-msg{ background:#1A1626; }
</style>

<div class="chat-wrap">
  <a href="<?= $souCoordenacao ? '/teste/admin/mensagens.php' : '/teste/minhas-mensagens.php' ?>"
     class="btn btn-ghost btn-sm">← Voltar</a>

  <div class="chat-header" style="margin-top:14px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
      <div>
        <div style="font-size:1.15rem;font-weight:700;"><?= htmlspecialchars($msg['assunto']) ?></div>
        <div class="text-muted text-sm" style="margin-top:3px;">
          <?= $souCoordenacao
            ? 'De ' . htmlspecialchars($msg['nome']) . ' &lt;' . htmlspecialchars($msg['email']) . '&gt;'
            : 'Conversa com a Coordenação InternSHIP' ?>
          · <?= date('d/m/Y H:i', strtotime($msg['criado_em'])) ?>
        </div>
      </div>
      <span class="badge <?= $msg['status'] === 'resolvida' ? 'badge-green' : ($msg['status'] === 'nova' ? 'badge-red' : 'badge-blue') ?>">
        <?= ucfirst($msg['status']) ?>
      </span>
    </div>

    <div class="origem-msg">
      <div class="text-muted text-sm" style="margin-bottom:6px;"><strong>Mensagem inicial</strong></div>
      <div style="white-space:pre-wrap;color:var(--text);"><?= htmlspecialchars($msg['mensagem']) ?></div>
    </div>
  </div>

  <div class="chat-thread" id="chat-thread">
    <?php if (empty($respostas)): ?>
      <div style="text-align:center;color:var(--gray-500);padding:40px 20px;">
        <div style="font-size:1rem;margin-bottom:4px;">Ainda sem respostas nesta conversa.</div>
        <div class="text-sm">
          <?= $souCoordenacao
            ? 'Envie a primeira resposta abaixo.'
            : 'Aguarde — a coordenação responderá em breve. Você pode enviar mais informações aqui.' ?>
        </div>
      </div>
    <?php else: ?>
      <?php
        $ultimoDia = '';
        foreach ($respostas as $r):
          $dia = date('d/m/Y', strtotime($r['criado_em']));
          if ($dia !== $ultimoDia) {
              echo '<div class="day-sep">' . $dia . '</div>';
              $ultimoDia = $dia;
          }
          $ehMinha = ($souCoordenacao && $r['remetente_tipo'] === 'coordenacao') ||
                     (!$souCoordenacao && $r['remetente_tipo'] === 'usuario');
          $ehCoord = $r['remetente_tipo'] === 'coordenacao';
          $ini = $ehCoord ? $iniciaisCoord : $iniciaisUser;
      ?>
        <div class="msg-row <?= $ehMinha ? 'mine' : '' ?>">
          <div class="msg-avatar <?= $ehCoord ? 'coord' : '' ?>"><?= $ini ?></div>
          <div style="max-width:75%;">
            <div class="msg-header-info">
              <span class="who">
                <?= $ehCoord ? 'Coordenação' : htmlspecialchars($msg['nome']) ?>
              </span>
            </div>
            <div class="msg-bubble"><?= htmlspecialchars($r['conteudo']) ?></div>
            <div class="msg-time"><?= date('H:i', strtotime($r['criado_em'])) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if ($msg['status'] !== 'resolvida' || $souCoordenacao): ?>
    <form method="POST" class="chat-input" onsubmit="setTimeout(()=>this.conteudo.value='',10)">
      <textarea name="conteudo" class="form-control" rows="2" placeholder="Escreva sua mensagem..." required maxlength="2000"></textarea>
      <button type="submit" name="enviar" value="1" class="btn btn-primary">Enviar</button>
    </form>
  <?php else: ?>
    <div class="alert alert-success" style="margin-top:14px;">
      Esta conversa foi marcada como <strong>resolvida</strong> pela coordenação.
    </div>
  <?php endif; ?>
</div>

<script>
// Rola para o final ao abrir
document.addEventListener('DOMContentLoaded', function(){
  var t = document.getElementById('chat-thread');
  if (t) t.scrollTop = t.scrollHeight;
  window.scrollTo(0, document.body.scrollHeight);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
