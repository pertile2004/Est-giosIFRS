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
    .badge-green{background:#D1FAE5;color:#10B981}
    .badge-yellow{background:#FEF3C7;color:#F59E0B}
    .btn{display:inline-block;padding:8px 16px;background:#7C3AED;color:#fff;border-radius:6px;text-decoration:none;border:none;cursor:pointer}
    .filter{display:flex;gap:12px;margin-bottom:24px}
    .filter select,.filter input{padding:8px;border:1px solid #ddd;border-radius:6px;flex:1}
    input,button,select,textarea{padding:10px;border:1px solid #ddd;border-radius:6px;width:100%}
    button.btn{width:auto}
    .alert-error{background:#fee;color:#900;padding:10px;border-radius:6px;margin-bottom:12px}
    table{width:100%;border-collapse:collapse;margin-top:16px}
    th,td{padding:12px;text-align:left;border-bottom:1px solid #eee}
    .kpi{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:24px}
    .kpi .card{text-align:center}
    .kpi .num{font-size:2rem;font-weight:bold;color:#7C3AED}
  </style>
</head>
<body>
<nav class="navbar">
  <div><strong>IntenSHIP Conect</strong> · <a style="margin-left:16px" href="/teste/">Início</a> <a href="/teste/vagas.php">Vagas</a></div>
  <div>
    <?php if (isLoggedIn()): ?>
      <?php if (isAluno()): ?>
        <a href="/teste/dashboard/aluno.php">Meu Painel</a>
      <?php else: ?>
        <a href="/teste/dashboard/empresa.php">Painel Empresa</a>
        <a href="/teste/empresa/publicar.php">+ Publicar Vaga</a>
      <?php endif; ?>
      Olá, <?= htmlspecialchars($_SESSION['nome'] ?? 'Usuário') ?> · <a href="/teste/logout.php">Sair</a>
    <?php else: ?>
      <a href="/teste/login.php">Entrar</a>
      <a href="/teste/register.php">Cadastrar</a>
    <?php endif; ?>
  </div>
</nav>
