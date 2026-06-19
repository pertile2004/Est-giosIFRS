<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'InternSHIP Conect — Plataforma de Estágios' ?></title>
  <meta name="description" content="Encontre o estágio ideal ou recrute talentos universitários na plataforma de estágios da região do IFRS.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/teste/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?>">
  <link rel="icon" type="image/svg+xml" href="/teste/assets/img/logo-icon.svg">
  <link rel="manifest" href="/teste/manifest.json">
  <meta name="theme-color" content="#7C3AED">
  <script>
    // Aplica tema antes do render para evitar flash
    (function() {
      var t = localStorage.getItem('theme');
      if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    })();
    // Registra service worker (PWA)
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function() {
        navigator.serviceWorker.register('/teste/service-worker.js').catch(function(){});
      });
    }
  </script>
</head>
<body>

<nav class="navbar">
  <div class="navbar-inner">
    <a href="/teste/" class="navbar-brand">
      <img src="/teste/assets/img/logo.svg" alt="InternSHIP Connect" style="height:52px;width:auto;display:block;object-fit:contain;">
    </a>

    <div class="navbar-nav">
      <a href="/teste/vagas.php" class="<?= $currentPage === 'vagas' ? 'active' : '' ?>">Vagas</a>
    </div>

    <div class="navbar-actions">
      <button class="theme-toggle" id="theme-toggle" title="Alternar tema" aria-label="Alternar tema claro/escuro">
        <span id="theme-icon">&#9790;</span>
      </button>
      <?php if (isLoggedIn()): ?>
        <?php if (isAdmin()): ?>
          <a href="/teste/admin/" class="btn btn-ghost btn-sm" style="color:var(--primary);">Admin</a>
        <?php endif; ?>
        <?php if (isAluno()): ?>
          <a href="/teste/dashboard/aluno.php" class="btn btn-ghost btn-sm">Meu Painel</a>
        <?php else: ?>
          <a href="/teste/dashboard/empresa.php" class="btn btn-ghost btn-sm">Painel Empresa</a>
          <a href="/teste/empresa/publicar.php" class="btn btn-primary btn-sm">+ Publicar Vaga</a>
        <?php endif; ?>
        <a href="<?= isAluno() ? '/teste/dashboard/aluno.php#perfil' : '/teste/dashboard/empresa.php#perfil' ?>"
           class="btn btn-ghost btn-sm"
           title="<?= htmlspecialchars($_SESSION['nome'] ?? 'Meu perfil') ?>"
           style="padding:6px 10px;display:inline-flex;align-items:center;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </a>
        <a href="/teste/logout.php" class="btn btn-ghost btn-sm">Sair</a>
      <?php else: ?>
        <a href="/teste/login.php" class="btn btn-ghost btn-sm">Entrar</a>
        <a href="/teste/register.php" class="btn btn-primary btn-sm">Cadastrar</a>
      <?php endif; ?>
    </div>

    <button class="navbar-menu-btn" id="menu-btn"></button>
  </div>

  <div class="mobile-nav" id="mobile-nav">
    <a href="/teste/vagas.php">Vagas</a>
    <?php if (isLoggedIn()): ?>
      <?php if (isAluno()): ?>
        <a href="/teste/dashboard/aluno.php">Meu Painel</a>
      <?php else: ?>
        <a href="/teste/dashboard/empresa.php">Painel Empresa</a>
        <a href="/teste/empresa/publicar.php">+ Publicar Vaga</a>
      <?php endif; ?>
      <a href="/teste/logout.php">Sair</a>
    <?php else: ?>
      <a href="/teste/login.php">Entrar</a>
      <a href="/teste/register.php">Cadastrar</a>
    <?php endif; ?>
  </div>
</nav>
