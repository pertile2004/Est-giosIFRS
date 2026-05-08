<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'IntenSHIP Conect' ?></title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:Arial,sans-serif}
    body{background:#f5f5f5;color:#222}
    .navbar{background:#0F172A;color:#fff;padding:16px 24px;display:flex;justify-content:space-between;align-items:center}
    .navbar a{color:#fff;text-decoration:none;margin-right:12px}
    .container{max-width:1100px;margin:0 auto;padding:24px}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
    .card{background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.1)}
    .badge{display:inline-block;padding:4px 10px;border-radius:99px;font-size:.75rem;background:#EDE9FE;color:#7C3AED}
    .btn{display:inline-block;padding:8px 16px;background:#7C3AED;color:#fff;border-radius:6px;text-decoration:none;border:none;cursor:pointer}
    .filter{display:flex;gap:12px;margin-bottom:24px}
    .filter select,.filter input{padding:8px;border:1px solid #ddd;border-radius:6px;flex:1}
    input,button,select,textarea{padding:10px;border:1px solid #ddd;border-radius:6px;width:100%}
    button.btn{width:auto}
    .alert-error{background:#fee;color:#900;padding:10px;border-radius:6px;margin-bottom:12px}
  </style>
</head>
<body>
<nav class="navbar">
  <div><strong>IntenSHIP Conect</strong> · <a style="margin-left:16px" href="/estagios/etapas/01-base-e-vagas-publicas/">Início</a> <a href="/estagios/etapas/01-base-e-vagas-publicas/vagas.php">Vagas</a></div>
  <div>
    <?php if (isLoggedIn()): ?>
      Olá, <?= htmlspecialchars($_SESSION['nome'] ?? 'Usuário') ?> · <a href="/estagios/etapas/01-base-e-vagas-publicas/logout.php">Sair</a>
    <?php else: ?>
      <a href="/estagios/etapas/01-base-e-vagas-publicas/login.php">Entrar</a>
      <a href="/estagios/etapas/01-base-e-vagas-publicas/register.php">Cadastrar</a>
    <?php endif; ?>
  </div>
</nav>
