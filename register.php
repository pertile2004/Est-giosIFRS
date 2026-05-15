<?php
$pageTitle = 'IntenSHIP Conect — Cadastrar';
require_once __DIR__ . '/includes/auth.php';

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? 'aluno';
    $nome  = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (strlen($senha) < 6) {
        $erro = 'Senha deve ter no mínimo 6 caracteres.';
    } elseif ($tipo === 'aluno') {
        if (registrarAluno($nome, $email, $senha) && login($email, $senha)) {
            header('Location: /teste/');
            exit;
        }
        $erro = 'E-mail já cadastrado.';
    } else {
        $nomeEmpresa = trim($_POST['nome_empresa'] ?? '');
        if (registrarEmpresa($nome, $email, $senha, $nomeEmpresa) && login($email, $senha)) {
            header('Location: /teste/');
            exit;
        }
        $erro = 'E-mail já cadastrado.';
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="card" style="max-width:400px;margin:48px auto;">
    <h2>Criar conta</h2>
    <?php if ($erro): ?>
      <div class="alert-error" style="margin-top:12px;"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="POST" style="margin-top:12px;">
      <select name="tipo" onchange="document.getElementById('emp').style.display=this.value==='empresa'?'block':'none'" style="margin-bottom:8px;">
        <option value="aluno">Sou estudante</option>
        <option value="empresa">Sou empresa</option>
      </select>
      <input type="text" name="nome" placeholder="Nome completo" required style="margin-bottom:8px;">
      <input type="email" name="email" placeholder="E-mail" required style="margin-bottom:8px;">
      <input type="password" name="senha" placeholder="Senha (mín. 6)" required style="margin-bottom:8px;">
      <div id="emp" style="display:none;">
        <input type="text" name="nome_empresa" placeholder="Nome da empresa" style="margin-bottom:8px;">
      </div>
      <button class="btn" type="submit" style="width:100%;margin-top:4px;">Cadastrar</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
