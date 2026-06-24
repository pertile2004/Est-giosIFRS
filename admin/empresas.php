<?php
$pageTitle = 'Admin · Empresas';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['usuario_id'] ?? 0);
    $acao = $_POST['acao'] ?? '';
    if ($userId) {
        if ($acao === 'desativar') {
            $db->prepare("UPDATE usuarios SET ativo=0 WHERE id=?")->execute([$userId]);
        } elseif ($acao === 'ativar') {
            $db->prepare("UPDATE usuarios SET ativo=1 WHERE id=?")->execute([$userId]);
        } elseif ($acao === 'excluir') {
            $db->prepare("DELETE FROM usuarios WHERE id=? AND tipo='empresa'")->execute([$userId]);
        }
    }
    header('Location: /teste/admin/empresas.php');
    exit;
}

$empresas = $db->query("
    SELECT u.id AS usuario_id, u.nome, u.email, u.ativo, u.criado_em,
           e.id AS empresa_id, e.nome_empresa, e.cnpj, e.cidade, e.estado,
           (SELECT COUNT(*) FROM vagas WHERE empresa_id = e.id) AS total_vagas
      FROM empresas e
      JOIN usuarios u ON e.usuario_id = u.id
     ORDER BY u.criado_em DESC
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:1400px;padding:32px 24px;">
  <a href="/teste/admin/" class="btn btn-ghost btn-sm">← Voltar ao painel</a>
  <h1 style="margin-top:12px;">Empresas cadastradas</h1>
  <p class="text-muted">Total: <?= count($empresas) ?></p>

  <div class="table-wrap compact" style="margin-top:20px;">
    <table>
      <thead>
        <tr>
          <th>Empresa</th>
          <th>Responsável</th>
          <th>CNPJ</th>
          <th>Cidade</th>
          <th>Vagas</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($empresas as $e): ?>
          <tr>
            <td>
              <div class="td-title"><?= htmlspecialchars($e['nome_empresa']) ?></div>
              <div class="text-muted text-sm"><?= htmlspecialchars($e['email']) ?></div>
            </td>
            <td><?= htmlspecialchars($e['nome']) ?></td>
            <td><?= htmlspecialchars(formatarCNPJ($e['cnpj'] ?? '') ?: '—') ?></td>
            <td><?= htmlspecialchars(($e['cidade'] ?? '—') . ($e['estado'] ? '/' . $e['estado'] : '')) ?></td>
            <td><?= (int)$e['total_vagas'] ?></td>
            <td>
              <?php if ($e['ativo']): ?>
                <span class="badge badge-green">Ativa</span>
              <?php else: ?>
                <span class="badge badge-red">Desativada</span>
              <?php endif; ?>
            </td>
            <td>
              <form method="POST" style="display:flex;gap:6px;">
                <input type="hidden" name="usuario_id" value="<?= (int)$e['usuario_id'] ?>">
                <?php if ($e['ativo']): ?>
                  <button name="acao" value="desativar" class="btn btn-ghost btn-sm">Desativar</button>
                <?php else: ?>
                  <button name="acao" value="ativar" class="btn btn-ghost btn-sm">Reativar</button>
                <?php endif; ?>
                <button name="acao" value="excluir" class="btn btn-ghost btn-sm" style="color:var(--danger);" onclick="return confirm('Excluir definitivamente esta empresa e todas as suas vagas?')">Excluir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
