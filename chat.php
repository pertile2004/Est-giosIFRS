<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$candidaturaId = (int)($_GET['candidatura_id'] ?? 0);

if (!$candidaturaId) {
    header('Location: /teste/');
    exit;
}

// Carrega a candidatura + vaga + aluno + empresa
$stmt = $db->prepare("
    SELECT c.id, c.aluno_id, c.status, c.criado_em AS candidatado_em,
           v.id AS vaga_id, v.titulo AS vaga_titulo, v.empresa_id,
           u_aluno.nome AS aluno_nome, a.id AS aluno_perfil_id, a.foto AS aluno_foto,
           u_emp.nome AS empresa_user_nome, e.nome_empresa, e.logo AS empresa_logo
      FROM candidaturas c
      JOIN vagas v ON v.id = c.vaga_id
      JOIN alunos a ON a.id = c.aluno_id
      JOIN usuarios u_aluno ON u_aluno.id = a.usuario_id
      JOIN empresas e ON e.id = v.empresa_id
      JOIN usuarios u_emp ON u_emp.id = e.usuario_id
     WHERE c.id = ?
");
$stmt->execute([$candidaturaId]);
$cand = $stmt->fetch();

if (!$cand) {
    header('Location: /teste/');
    exit;
}

// Permissoes: aluno dono OU empresa dona da vaga
$ehAluno = isAluno() && (int)$_SESSION['perfil_id'] === (int)$cand['aluno_id'];
$ehEmpresa = isEmpresa() && (int)$_SESSION['perfil_id'] === (int)$cand['empresa_id'];
if (!$ehAluno && !$ehEmpresa) {
    http_response_code(403);
    die('Você não tem acesso a esta conversa.');
}

$meuLado = $ehAluno ? 'aluno' : 'empresa';
$outroLado = $ehAluno ? 'empresa' : 'aluno';
$outroNome = $ehAluno ? $cand['nome_empresa'] : $cand['aluno_nome'];

// Envia nova mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar'])) {
    $conteudo = trim($_POST['conteudo'] ?? '');
    if ($conteudo !== '' && mb_strlen($conteudo) <= 2000) {
        $db->prepare("INSERT INTO mensagens (candidatura_id, remetente_tipo, conteudo) VALUES (?, ?, ?)")
           ->execute([$candidaturaId, $meuLado, $conteudo]);
    }
    header('Location: /teste/chat.php?candidatura_id=' . $candidaturaId . '#fim');
    exit;
}

// Marca como lidas as mensagens do OUTRO lado
$db->prepare("UPDATE mensagens SET lida = 1 WHERE candidatura_id = ? AND remetente_tipo = ? AND lida = 0")
   ->execute([$candidaturaId, $outroLado]);

// Carrega historico
$stmt = $db->prepare("SELECT * FROM mensagens WHERE candidatura_id = ? ORDER BY criado_em ASC");
$stmt->execute([$candidaturaId]);
$mensagens = $stmt->fetchAll();

$pageTitle = 'Chat — ' . $cand['vaga_titulo'];
include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:780px;padding:24px 20px 60px;">

  <div style="margin-bottom:16px;">
    <?php if ($ehAluno): ?>
      <a href="/teste/dashboard/aluno.php" class="btn btn-ghost btn-sm">← Voltar ao painel</a>
    <?php else: ?>
      <a href="/teste/dashboard/empresa.php" class="btn btn-ghost btn-sm">← Voltar ao painel</a>
    <?php endif; ?>
  </div>

  <div class="card" style="overflow:hidden;">
    <div class="card-header" style="display:flex;align-items:center;gap:14px;">
      <?php
        $avatarSrc = $ehAluno
            ? ($cand['empresa_logo'] ? '/teste/' . htmlspecialchars(ltrim($cand['empresa_logo'], '/')) : null)
            : ($cand['aluno_foto'] ? '/teste/' . htmlspecialchars(ltrim($cand['aluno_foto'], '/')) : null);
        $iniciais = mb_strtoupper(mb_substr($outroNome, 0, 2));
      ?>
      <div style="width:48px;height:48px;border-radius:50%;overflow:hidden;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1rem;flex-shrink:0;">
        <?php if ($avatarSrc): ?>
          <img src="<?= $avatarSrc ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
        <?php else: ?>
          <?= htmlspecialchars($iniciais) ?>
        <?php endif; ?>
      </div>
      <div style="flex:1;">
        <h3 style="margin:0;font-size:1rem;"><?= htmlspecialchars($outroNome) ?></h3>
        <div style="font-size:.82rem;color:var(--gray-500);">Vaga: <?= htmlspecialchars($cand['vaga_titulo']) ?></div>
      </div>
      <span class="badge badge-<?= match($cand['status']) { 'aprovado'=>'green', 'recusado'=>'red', 'visualizado'=>'blue', default=>'yellow' } ?>">
        <?= ucfirst($cand['status']) ?>
      </span>
    </div>

    <div id="chat-thread" style="padding:24px;max-height:520px;overflow-y:auto;background:var(--bg-soft);">
      <?php if (empty($mensagens)): ?>
        <div style="text-align:center;color:var(--gray-500);padding:40px 0;font-size:.92rem;">
          Comece a conversa enviando uma mensagem abaixo.
        </div>
      <?php else: ?>
        <?php
          $diaAtual = null;
          foreach ($mensagens as $m):
              $diaMsg = date('Y-m-d', strtotime($m['criado_em']));
              if ($diaMsg !== $diaAtual):
                  $diaAtual = $diaMsg;
        ?>
          <div style="text-align:center;margin:16px 0 12px;">
            <span style="font-size:.74rem;color:var(--gray-500);background:var(--surface);padding:3px 10px;border-radius:12px;border:1px solid var(--border);">
              <?= date('d/m/Y', strtotime($m['criado_em'])) ?>
            </span>
          </div>
        <?php endif; ?>
        <?php $souEu = $m['remetente_tipo'] === $meuLado; ?>
        <div style="display:flex;justify-content:<?= $souEu ? 'flex-end' : 'flex-start' ?>;margin-bottom:8px;">
          <div style="max-width:75%;background:<?= $souEu ? 'var(--primary)' : 'var(--surface)' ?>;color:<?= $souEu ? '#fff' : 'var(--text)' ?>;padding:10px 14px;border-radius:14px;<?= $souEu ? 'border-bottom-right-radius:4px;' : 'border-bottom-left-radius:4px;border:1px solid var(--border);' ?>">
            <div style="font-size:.92rem;line-height:1.45;white-space:pre-wrap;"><?= nl2br(htmlspecialchars($m['conteudo'])) ?></div>
            <div style="font-size:.7rem;<?= $souEu ? 'color:rgba(255,255,255,.7)' : 'color:var(--gray-500)' ?>;margin-top:4px;text-align:right;">
              <?= date('H:i', strtotime($m['criado_em'])) ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
      <div id="fim"></div>
    </div>

    <form method="POST" style="padding:14px 18px;border-top:1px solid var(--border);display:flex;gap:8px;align-items:flex-end;">
      <textarea name="conteudo" class="form-control" rows="2" required maxlength="2000"
                placeholder="Escreva sua mensagem..." style="resize:none;min-height:auto;"></textarea>
      <button type="submit" name="enviar" value="1" class="btn btn-primary" style="height:60px;padding:0 18px;flex-shrink:0;">
        Enviar
      </button>
    </form>
  </div>
</div>

<script>
  // Auto-scroll para a ultima mensagem
  (function() {
    var thread = document.getElementById('chat-thread');
    if (thread) thread.scrollTop = thread.scrollHeight;
  })();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
