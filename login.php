<?php
$pageTitle = 'IntenSHIP Conect — Entrar';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: /teste/');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (login(trim($_POST['email'] ?? ''), $_POST['senha'] ?? '')) {
        header('Location: /teste/');
        exit;
    }
    $erro = 'E-mail ou senha incorretos.';
}

include __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="card" style="max-width:400px;margin:48px auto;">
    <h2>Entrar</h2>
    <?php if ($erro): ?>
      <div class="alert-error" style="margin-top:12px;"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="POST" style="margin-top:12px;">
      <input type="email" name="email" placeholder="E-mail" required style="margin-bottom:8px;">
      <input type="password" name="senha" placeholder="Senha" required style="margin-bottom:12px;">
      <button class="btn" type="submit" style="width:100%;">Entrar</button>
    </form>
    <p style="text-align:center;margin-top:12px;">Não tem conta? <a href="/teste/register.php">Criar conta</a></p>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
