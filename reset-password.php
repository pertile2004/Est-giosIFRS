<?php
$pageTitle = 'InternSHIP Conect — Nova senha';
require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$erro = '';
$sucesso = false;

if (!$token) {
    header('Location: /teste/forgot-password.php');
    exit;
}

$tokenValido = (bool)validarTokenReset($token);

if (!$tokenValido && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Token invalido ou expirado: vamos mostrar a mensagem na tela
    $erro = 'Este link de recuperação é inválido ou expirou. Solicite um novo.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValido) {
    $senha = $_POST['senha'] ?? '';
    $confirma = $_POST['confirma'] ?? '';

    if (strlen($senha) < 6) {
        $erro = 'A senha precisa ter no mínimo 6 caracteres.';
    } elseif ($senha !== $confirma) {
        $erro = 'As senhas não coincidem.';
    } elseif (redefinirSenha($token, $senha)) {
        $sucesso = true;
    } else {
        $erro = 'Não foi possível redefinir a senha. Solicite um novo link.';
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
      <h2>Definir nova senha</h2>
      <p>Escolha uma senha que você consiga lembrar e que tenha pelo menos 6 caracteres.</p>
    </div>
  </div>

  <div class="auth-right">
    <div class="auth-form-wrap">
      <div class="auth-logo" style="justify-content:center;margin-bottom:28px;">
        <img src="/teste/assets/img/logo.svg" alt="InternSHIP Connect" style="height:48px;width:auto;object-fit:contain;">
      </div>

      <?php if ($sucesso): ?>
        <div class="alert alert-success">
          Senha redefinida com sucesso. Agora você já pode entrar com a nova senha.
        </div>
        <a href="/teste/login.php" class="btn btn-primary btn-block btn-lg">Ir para o login</a>
      <?php elseif (!$tokenValido): ?>
        <h2 style="margin-bottom:6px;">Link inválido</h2>
        <p class="text-muted text-sm" style="margin-bottom:20px;">O link pode ter expirado ou já ter sido usado.</p>
        <?php if ($erro): ?><div class="alert alert-error"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
        <a href="/teste/forgot-password.php" class="btn btn-primary btn-block btn-lg">Solicitar novo link</a>
      <?php else: ?>
        <h2 style="margin-bottom:6px;">Criar nova senha</h2>
        <p class="text-muted text-sm" style="margin-bottom:28px;">Digite e confirme sua nova senha</p>

        <?php if ($erro): ?>
          <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
          <div class="form-group">
            <label class="form-label">Nova senha</label>
            <input type="password" name="senha" class="form-control" placeholder="Mínimo 6 caracteres" required minlength="6" autofocus>
          </div>
          <div class="form-group">
            <label class="form-label">Confirmar nova senha</label>
            <input type="password" name="confirma" class="form-control" placeholder="Repita a senha" required minlength="6">
          </div>
          <button type="submit" class="btn btn-primary btn-block btn-lg">
            Redefinir senha
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
