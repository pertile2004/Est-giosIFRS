<?php
$pageTitle = 'InternSHIP Conect — Recuperar senha';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: /teste/');
    exit;
}

$mensagem = '';
$linkDev = ''; // mostrado em ambiente local; em producao seria enviado por e-mail

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Informe um e-mail válido.';
    } else {
        $token = gerarTokenResetSenha($email);
        // Por seguranca, sempre mostramos a mesma mensagem (nao revelamos se o e-mail existe).
        $mensagem = 'Se este e-mail estiver cadastrado, você receberá as instruções para redefinir a senha.';

        if ($token) {
            // Em producao, aqui seria disparado um e-mail com o link abaixo.
            // No ambiente local exibimos o link diretamente para facilitar o teste.
            $linkDev = '/teste/reset-password.php?token=' . urlencode($token);
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
  <div class="auth-left">
    <div class="auth-left-content">
      <a href="/teste/login.php" style="display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,.6);font-size:.9rem;margin-bottom:48px;text-decoration:none;">
        ← Voltar para o login
      </a>
      <h2>Esqueceu a senha?</h2>
      <p>Sem problema. Informe o e-mail cadastrado e enviaremos um link para você criar uma nova senha. O link expira em 1 hora.</p>
    </div>
  </div>

  <div class="auth-right">
    <div class="auth-form-wrap">
      <div class="auth-logo" style="justify-content:center;margin-bottom:28px;">
        <img src="/teste/assets/img/logo.svg" alt="InternSHIP Connect" style="height:48px;width:auto;object-fit:contain;">
      </div>

      <h2 style="margin-bottom:6px;">Recuperar senha</h2>
      <p class="text-muted text-sm" style="margin-bottom:28px;">Informe o e-mail da sua conta</p>

      <?php if ($mensagem): ?>
        <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
      <?php endif; ?>

      <?php if ($linkDev): ?>
        <div class="alert alert-warning" style="font-size:.82rem;">
          <strong>Modo desenvolvimento:</strong> o envio por e-mail ainda não está configurado.<br>
          Use este link para redefinir:<br>
          <a href="<?= htmlspecialchars($linkDev) ?>"><?= htmlspecialchars($linkDev) ?></a>
        </div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label class="form-label">E-mail</label>
          <input type="email" name="email" class="form-control" placeholder="seu@email.com" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">
          Enviar link de recuperação
        </button>
      </form>

      <p class="auth-link">
        Lembrou da senha? <a href="/teste/login.php">Entrar →</a>
      </p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
