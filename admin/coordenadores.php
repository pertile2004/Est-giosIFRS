<?php
$pageTitle = 'Coordenação · Equipe';
require_once __DIR__ . '/../includes/auth.php';
requireCoordenacao();

$db = getDB();
$erro = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'criar';

    if ($acao === 'criar') {
        $nome  = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if (!$nome || !$email || !$senha) {
            $erro = 'Preencha nome, e-mail e senha.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Informe um e-mail válido.';
        } elseif (strlen($senha) < 6) {
            $erro = 'A senha deve ter pelo menos 6 caracteres.';
        } else {
            $existe = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $existe->execute([$email]);
            if ($existe->fetch()) {
                $erro = 'Já existe um usuário com esse e-mail.';
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $db->prepare("INSERT INTO usuarios (nome, email, senha, tipo, is_admin) VALUES (?, ?, ?, 'coordenacao', 1)")
                   ->execute([$nome, $email, $hash]);
                $ok = "Coordenador(a) \"$nome\" criado com sucesso.";
            }
        }
    } elseif ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);
        // Não permite excluir a si mesmo
        if ($id && $id !== (int)$_SESSION['usuario_id']) {
            $db->prepare("DELETE FROM usuarios WHERE id=? AND tipo='coordenacao'")->execute([$id]);
            $ok = 'Coordenador removido.';
        } else {
            $erro = 'Você não pode remover a sua própria conta.';
        }
    }
}

$coords = $db->query("SELECT id, nome, email, criado_em FROM usuarios WHERE tipo='coordenacao' ORDER BY criado_em ASC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:900px;padding:32px 24px;">
  <a href="/teste/admin/" class="btn btn-ghost btn-sm">← Voltar ao painel</a>
  <h1 style="margin-top:12px;">Equipe de Coordenação</h1>
  <p class="text-muted">Contas com acesso ao painel da coordenação (donos e desenvolvedores da InternSHIP).</p>

  <?php if ($erro): ?><div class="alert alert-error" style="margin-top:16px;"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="alert alert-success" style="margin-top:16px;"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

  <div class="card mb-6" style="margin-top:20px;">
    <div class="card-header"><h3>Adicionar coordenador</h3></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="acao" value="criar">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nome</label>
            <input type="text" name="nome" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Senha provisória</label>
          <input type="text" name="senha" class="form-control" minlength="6" placeholder="mínimo 6 caracteres" required>
          <div class="form-hint">O coordenador pode trocar depois em "Esqueceu a senha?".</div>
        </div>
        <button type="submit" class="btn btn-primary">Criar coordenador</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>Coordenadores (<?= count($coords) ?>)</h3></div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Nome</th><th>E-mail</th><th>Desde</th><th>Ações</th></tr>
        </thead>
        <tbody>
          <?php foreach ($coords as $c): ?>
          <tr>
            <td class="td-title">
              <?= htmlspecialchars($c['nome']) ?>
              <?php if ((int)$c['id'] === (int)$_SESSION['usuario_id']): ?>
                <span class="badge badge-purple" style="margin-left:6px;">você</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($c['email']) ?></td>
            <td class="text-muted text-sm"><?= date('d/m/Y', strtotime($c['criado_em'])) ?></td>
            <td>
              <?php if ((int)$c['id'] !== (int)$_SESSION['usuario_id']): ?>
                <form method="POST" onsubmit="return confirm('Remover este coordenador?');">
                  <input type="hidden" name="acao" value="excluir">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <button class="btn btn-ghost btn-sm" style="color:var(--danger);">Remover</button>
                </form>
              <?php else: ?>
                <span class="text-muted text-sm">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
