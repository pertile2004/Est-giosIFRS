<?php
$pageTitle = 'Coordenação — InternSHIP Conect';
require_once __DIR__ . '/../includes/auth.php';
requireCoordenacao();

$db = getDB();

$totalUsuarios  = $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$totalAlunos    = $db->query("SELECT COUNT(*) FROM alunos")->fetchColumn();
$totalEmpresas  = $db->query("SELECT COUNT(*) FROM empresas")->fetchColumn();
$totalVagas     = $db->query("SELECT COUNT(*) FROM vagas")->fetchColumn();
$vagasAtivas    = $db->query("SELECT COUNT(*) FROM vagas WHERE ativa=1")->fetchColumn();
$totalCand      = $db->query("SELECT COUNT(*) FROM candidaturas")->fetchColumn();
$inativos       = $db->query("SELECT COUNT(*) FROM usuarios WHERE ativo=0")->fetchColumn();
$msgsNovas      = contarMensagensNovas();

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:1100px;padding:32px 24px;">
  <div class="dashboard-header">
    <h1>Painel da Coordenação</h1>
    <p>Visão geral da plataforma, mensagens e moderação</p>
  </div>

  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-top">
        <div><div class="kpi-num"><?= $totalUsuarios ?></div><div class="kpi-label">Usuários</div></div>
      </div>
      <div class="text-muted text-sm" style="margin-top:4px;"><?= $totalAlunos ?> alunos · <?= $totalEmpresas ?> empresas</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top">
        <div><div class="kpi-num"><?= $vagasAtivas ?></div><div class="kpi-label">Vagas Ativas</div></div>
      </div>
      <div class="text-muted text-sm" style="margin-top:4px;">de <?= $totalVagas ?> publicadas</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top">
        <div><div class="kpi-num"><?= $totalCand ?></div><div class="kpi-label">Candidaturas</div></div>
      </div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top">
        <div><div class="kpi-num" style="color:<?= $msgsNovas ? 'var(--danger)' : 'var(--gray-700)' ?>;"><?= $msgsNovas ?></div><div class="kpi-label">Mensagens novas</div></div>
      </div>
      <div class="text-muted text-sm" style="margin-top:4px;"><?= $inativos ?> usuário(s) inativo(s)</div>
    </div>
  </div>

  <div class="card mb-6">
    <div class="card-header"><h3>Ferramentas da coordenação</h3></div>
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <a href="/teste/admin/mensagens.php" style="text-decoration:none;">
        <div style="padding:20px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);position:relative;">
          <h3 style="margin-bottom:4px;">Mensagens de contato
            <?php if ($msgsNovas): ?><span class="badge badge-red" style="margin-left:4px;"><?= $msgsNovas ?> novas</span><?php endif; ?>
          </h3>
          <p class="text-muted text-sm">Ler e responder as mensagens enviadas pela página de Contato.</p>
        </div>
      </a>
      <a href="/teste/admin/coordenadores.php" style="text-decoration:none;">
        <div style="padding:20px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);">
          <h3 style="margin-bottom:4px;">Equipe de coordenação</h3>
          <p class="text-muted text-sm">Criar ou remover logins de coordenadores (donos e devs).</p>
        </div>
      </a>
      <a href="/teste/admin/empresas.php" style="text-decoration:none;">
        <div style="padding:20px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);">
          <h3 style="margin-bottom:4px;">Empresas</h3>
          <p class="text-muted text-sm">Ativar, desativar ou excluir contas de empresas.</p>
        </div>
      </a>
      <a href="/teste/admin/vagas.php" style="text-decoration:none;">
        <div style="padding:20px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);">
          <h3 style="margin-bottom:4px;">Vagas</h3>
          <p class="text-muted text-sm">Pausar, reativar ou remover vagas inadequadas.</p>
        </div>
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
