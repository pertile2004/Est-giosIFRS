<?php
$pageTitle = 'InternSHIP Conect — Entrar';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . (isAluno() ? '/teste/dashboard/aluno.php' : '/teste/dashboard/empresa.php'));
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    if (login($email, $senha)) {
        header('Location: ' . (isAluno() ? '/teste/dashboard/aluno.php' : '/teste/dashboard/empresa.php'));
        exit;
    } else {
        $erro = 'E-mail ou senha incorretos.';
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
  <div class="auth-left">
    <div class="auth-left-content">
      <a href="/teste/" style="display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,.6);font-size:.9rem;margin-bottom:48px;text-decoration:none;">
        ← Voltar ao início
      </a>
      <h2>Bem-vindo de volta! 👋</h2>
      <p>Entre na sua conta e continue sua jornada rumo ao estágio perfeito.</p>
      <div class="auth-features">
        <div class="auth-feature">
          <div class="auth-feature-icon">🎯</div>
          <div class="auth-feature-text">
            <strong>Vagas exclusivas</strong>
            <span>Acesse vagas que não estão em lugar nenhum</span>
          </div>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon">⚡</div>
          <div class="auth-feature-text">
            <strong>Candidatura rápida</strong>
            <span>Se candidate em segundos com seu perfil salvo</span>
          </div>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon">📊</div>
          <div class="auth-feature-text">
            <strong>Acompanhamento real-time</strong>
            <span>Veja o status das suas candidaturas em tempo real</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="auth-right">
    <div class="auth-form-wrap">
      <div class="auth-logo">
        <div class="brand-icon">🎯</div>
        InternSHIP Conect
      </div>

      <h2 style="margin-bottom:6px;">Entrar na conta</h2>
      <p class="text-muted text-sm" style="margin-bottom:28px;">Digite suas credenciais para continuar</p>

      <?php if ($erro): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label class="form-label">E-mail</label>
          <input type="email" name="email" class="form-control" placeholder="seu@email.com" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label" style="display:flex;justify-content:space-between;">
            Senha
            <a href="#" style="font-weight:400;font-size:.82rem;color:var(--primary);">Esqueceu a senha?</a>
          </label>
          <input type="password" name="senha" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:8px;">
          Entrar →
        </button>
      </form>

      <div class="auth-divider"><span>ou continue com</span></div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <button class="btn btn-ghost" onclick="showToast('Em breve!','info')">
          <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
          Google
        </button>
        <button class="btn btn-ghost" onclick="showToast('Em breve!','info')">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61-.546-1.385-1.335-1.755-1.335-1.755-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.63-5.37-12-12-12"/></svg>
          GitHub
        </button>
      </div>

      <p class="auth-link">
        Não tem conta? <a href="/teste/register.php">Criar conta grátis →</a>
      </p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
