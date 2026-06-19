<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$alunoId = (int)($_GET['id'] ?? 0);
if (!$alunoId) {
    header('Location: /teste/');
    exit;
}

$db = getDB();

// Empresa so pode ver alunos que se candidataram a alguma vaga sua.
// Aluno pode ver o proprio perfil.
$temAcesso = false;
if (isAluno() && $_SESSION['perfil_id'] == $alunoId) {
    $temAcesso = true;
} elseif (isEmpresa() && empresaPodeVerAluno($_SESSION['perfil_id'], $alunoId)) {
    $temAcesso = true;
}

if (!$temAcesso) {
    http_response_code(403);
    $pageTitle = 'Acesso negado';
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="container" style="max-width:600px;margin:80px auto;">
      <div class="card" style="padding:32px;text-align:center;">
        <h2 style="margin-bottom:8px;">Acesso negado</h2>
        <p style="color:var(--gray-600);">
          Empresas só podem visualizar perfis de alunos que se candidataram a uma de suas vagas.
        </p>
        <a href="/teste/" class="btn btn-primary mt-4" style="margin-top:16px;">Voltar ao início</a>
      </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$stmt = $db->prepare("
    SELECT a.*, u.nome, u.email
      FROM alunos a
      JOIN usuarios u ON a.usuario_id = u.id
     WHERE a.id = ?
");
$stmt->execute([$alunoId]);
$aluno = $stmt->fetch();

if (!$aluno) {
    header('Location: /teste/');
    exit;
}

$pageTitle = 'Perfil de ' . $aluno['nome'];
$iniciais = mb_strtoupper(mb_substr($aluno['nome'], 0, 1) . (strpos($aluno['nome'], ' ') ? mb_substr($aluno['nome'], strpos($aluno['nome'], ' ') + 1, 1) : ''));

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:820px;padding:40px 24px 80px;">
  <a href="javascript:history.back()" class="btn btn-ghost btn-sm">← Voltar</a>

  <div class="card" style="margin-top:16px;padding:32px;">
    <div style="display:flex;gap:20px;align-items:center;margin-bottom:24px;">
      <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:800;flex-shrink:0;">
        <?= htmlspecialchars($iniciais) ?>
      </div>
      <div style="flex:1;">
        <h1 style="font-size:1.6rem;margin-bottom:4px;"><?= htmlspecialchars($aluno['nome']) ?></h1>
        <div style="color:var(--gray-600);font-size:.95rem;">
          <?= htmlspecialchars($aluno['curso'] ?: 'Curso não informado') ?>
          <?php if ($aluno['semestre']): ?> · <?= (int)$aluno['semestre'] ?>º semestre<?php endif; ?>
        </div>
        <?php if ($aluno['universidade']): ?>
          <div style="color:var(--gray-500);font-size:.88rem;margin-top:2px;"><?= htmlspecialchars($aluno['universidade']) ?></div>
        <?php endif; ?>
        <?php if ($aluno['cidade']): ?>
          <div style="color:var(--gray-500);font-size:.85rem;margin-top:4px;"><?= htmlspecialchars($aluno['cidade']) ?><?= $aluno['estado'] ? '/' . $aluno['estado'] : '' ?></div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($aluno['sobre'])): ?>
      <div style="margin-bottom:24px;">
        <h3 style="font-size:1rem;margin-bottom:8px;color:var(--gray-700);">Sobre</h3>
        <p style="color:var(--gray-600);line-height:1.7;"><?= nl2br(htmlspecialchars($aluno['sobre'])) ?></p>
      </div>
    <?php endif; ?>

    <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
      <?php if (!empty($aluno['linkedin'])): ?>
        <a href="<?= htmlspecialchars($aluno['linkedin']) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">LinkedIn</a>
      <?php endif; ?>
      <?php if (!empty($aluno['github'])): ?>
        <a href="<?= htmlspecialchars($aluno['github']) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">GitHub</a>
      <?php endif; ?>
      <?php if (!empty($aluno['curriculo_path'])): ?>
        <a href="/teste/<?= htmlspecialchars($aluno['curriculo_path']) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm">Baixar currículo (PDF)</a>
      <?php endif; ?>
      <?php if (isEmpresa()): ?>
        <a href="mailto:<?= htmlspecialchars($aluno['email']) ?>" class="btn btn-ghost btn-sm">Enviar e-mail</a>
      <?php endif; ?>
    </div>

    <?php if (empty($aluno['sobre']) && empty($aluno['linkedin']) && empty($aluno['github']) && empty($aluno['curriculo_path'])): ?>
      <div class="alert alert-warning">
        Este aluno ainda não preencheu informações adicionais do perfil.
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
