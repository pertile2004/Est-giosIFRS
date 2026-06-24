<?php
$pageTitle = 'InternSHIP Conect — Contato';
require_once __DIR__ . '/includes/auth.php';

$contatoEmail = 'contato@internshipconnect.com.br';

$erro = '';
$enviado = isset($_GET['enviado']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome    = trim($_POST['nome'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $assunto = trim($_POST['assunto'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');

    if (!$nome || !$email || !$assunto || !$mensagem) {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } else {
        getDB()->prepare(
            "INSERT INTO mensagens_contato (nome, email, assunto, mensagem) VALUES (?, ?, ?, ?)"
        )->execute([
            mb_substr($nome, 0, 150),
            mb_substr($email, 0, 200),
            mb_substr($assunto, 0, 200),
            $mensagem,
        ]);
        header('Location: /teste/contato.php?enviado=1');
        exit;
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:780px;padding:48px 24px 80px;">
  <h1 style="margin-bottom:8px;">Fale com a gente</h1>
  <p class="text-muted" style="margin-bottom:32px;">
    Dúvidas, sugestões ou problemas? Estamos por aqui para ajudar estudantes e empresas.
  </p>

  <div class="card mb-6">
    <div class="card-body" style="display:flex;flex-direction:column;gap:18px;">
      <div style="display:flex;gap:14px;align-items:flex-start;">
        <span style="font-size:1.4rem;">✉️</span>
        <div>
          <div style="font-weight:700;margin-bottom:2px;">E-mail</div>
          <a href="mailto:<?= htmlspecialchars($contatoEmail) ?>" style="color:var(--primary);text-decoration:none;"><?= htmlspecialchars($contatoEmail) ?></a>
        </div>
      </div>
      <div style="display:flex;gap:14px;align-items:flex-start;">
        <span style="font-size:1.4rem;">📍</span>
        <div>
          <div style="font-weight:700;margin-bottom:2px;">Localização</div>
          <div style="color:var(--gray-600);">Serra Gaúcha — Bento Gonçalves, RS</div>
        </div>
      </div>
      <div style="display:flex;gap:14px;align-items:flex-start;">
        <span style="font-size:1.4rem;">⏰</span>
        <div>
          <div style="font-weight:700;margin-bottom:2px;">Atendimento</div>
          <div style="color:var(--gray-600);">Segunda a sexta, das 9h às 18h</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>Envie uma mensagem</h3></div>
    <div class="card-body">
      <?php if ($enviado): ?>
        <div class="alert alert-success">Mensagem enviada! A coordenação vai responder no e-mail informado. Obrigado pelo contato.</div>
      <?php endif; ?>
      <?php if ($erro): ?>
        <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <p class="text-muted text-sm" style="margin-bottom:16px;">
        Preencha os campos abaixo. Sua mensagem vai direto para a coordenação da InternSHIP.
      </p>
      <form method="POST" action="/teste/contato.php">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Seu nome</label>
            <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($_POST['nome'] ?? ($_SESSION['nome'] ?? '')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Seu e-mail</label>
            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? ($_SESSION['email'] ?? '')) ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Assunto</label>
          <input type="text" name="assunto" class="form-control" required value="<?= htmlspecialchars($_POST['assunto'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Mensagem</label>
          <textarea name="mensagem" class="form-control" rows="5" required><?= htmlspecialchars($_POST['mensagem'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Enviar mensagem</button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
