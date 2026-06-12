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
  <link rel="stylesheet" href="/teste/assets/css/style.css">
  <link rel="icon" type="image/svg+xml" href="/teste/assets/img/logo-icon.svg">
  <script>
    // Aplica tema antes do render para evitar flash
    (function() {
      var t = localStorage.getItem('theme');
      if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    })();
  </script>
</head>
<body>

<nav class="navbar">
  <div class="navbar-inner">
    <a href="/teste/" class="navbar-brand">
      <img src="/teste/assets/img/logo.svg" alt="InternSHIP Connect" style="height:42px;width:auto;display:block;object-fit:contain;">
    </a>

    <div class="navbar-nav">
      <a href="/teste/vagas.php" class="<?= $currentPage === 'vagas' ? 'active' : '' ?>">Vagas</a>
      <a href="/teste/vagas.php?modalidade=remoto">Remoto</a>
      <a href="/teste/vagas.php?area=Tecnologia">Tecnologia</a>
      <a href="/teste/vagas.php?area=Design">Design</a>
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
