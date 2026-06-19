<?php
$pageTitle = 'InternSHIP Conect — Encontre seu Estágio Ideal';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$db = getDB();

$totalVagas = $db->query("SELECT COUNT(*) FROM vagas WHERE ativa=1")->fetchColumn();
$totalAlunos = $db->query("SELECT COUNT(*) FROM alunos")->fetchColumn();
$totalEmpresas = $db->query("SELECT COUNT(*) FROM empresas")->fetchColumn();
$totalCandidaturas = $db->query("SELECT COUNT(*) FROM candidaturas")->fetchColumn();

$vagas = $db->query("
  SELECT v.*, e.nome_empresa, e.logo, e.setor
  FROM vagas v
  JOIN empresas e ON v.empresa_id = e.id
  WHERE v.ativa = 1
  ORDER BY v.destaque DESC, v.criado_em DESC
  LIMIT 6
")->fetchAll();

include __DIR__ . '/includes/header.php';

function modalidadeLabel($m) {
    return match($m) { 'remoto' => 'Remoto', 'hibrido' => 'Híbrido', default => 'Presencial' };
}
function modalidadeBadge($m) {
    return match($m) { 'remoto' => 'badge-green', 'hibrido' => 'badge-blue', default => 'badge-gray' };
}
?>

<section class="hero">
  <div class="hero-inner">
    <div class="hero-content animate-in">
      <div class="hero-tag">
        Plataforma #1 de Estágios da Região
      </div>
      <h1>Seu próximo <span>grande passo</span> começa aqui</h1>
      <p>Conectamos estudantes do IFRS às empresas mais inovadoras da região. Encontre o estágio que vai transformar sua carreira.</p>

      <div class="hero-actions">
        <a href="/teste/vagas.php" class="btn btn-primary btn-lg">
          Explorar Vagas
        </a>
        <?php if (isLoggedIn()): ?>
          <?php if (isAluno()): ?>
            <a href="/teste/dashboard/aluno.php" class="btn btn-white btn-lg">
              Gerenciar Perfil
            </a>
          <?php else: ?>
            <a href="/teste/dashboard/empresa.php" class="btn btn-white btn-lg">
              Gerenciar Perfil
            </a>
          <?php endif; ?>
        <?php else: ?>
          <a href="/teste/register.php" class="btn btn-white btn-lg">
            Criar Perfil Grátis
          </a>
        <?php endif; ?>
      </div>

      <div class="hero-search">
        <span style="color:rgba(255,255,255,.5);font-size:1.1rem;"></span>
        <input type="text" id="hero-search" placeholder="Buscar por cargo, área ou empresa..." />
        <button onclick="window.location.href='/teste/vagas.php?q='+document.getElementById('hero-search').value" class="btn btn-primary btn-sm">Buscar</button>
      </div>
    </div>

    <div class="hero-visual animate-in-delay">
      <?php foreach (array_slice($vagas, 0, 2) as $v): ?>
      <div class="hero-card">
        <div class="hero-card-header">
          <div class="hero-card-icon" style="background:linear-gradient(135deg,<?= ['#7C3AED','#EC4899','#10B981','#F59E0B'][array_search($v,$vagas)%4] ?>,<?= ['#EC4899','#10B981','#F59E0B','#7C3AED'][array_search($v,$vagas)%4] ?>)">
            <?= mb_strtoupper(mb_substr($v['nome_empresa'], 0, 1)) ?>
          </div>
          <div>
            <div class="hero-card-title"><?= htmlspecialchars($v['titulo']) ?></div>
            <div class="hero-card-sub"><?= htmlspecialchars($v['nome_empresa']) ?> · <?= $v['cidade'] ?>/<?= $v['estado'] ?></div>
          </div>
        </div>
        <div class="hero-card-pills">
          <span class="hero-card-pill"><?= htmlspecialchars($v['area']) ?></span>
          <span class="hero-card-pill"><?= modalidadeLabel($v['modalidade']) ?></span>
          <span class="hero-card-pill">⏰ <?= $v['carga_horaria'] ?>h/sem</span>
        </div>
        <div class="hero-card-bolsa">R$ <?= number_format($v['bolsa'], 0, ',', '.') ?>/mês</div>
        <div class="hero-stats">
          <div class="hero-stat"><div class="hero-stat-num"><?= $totalAlunos ?>+</div><div class="hero-stat-label">Alunos</div></div>
          <div class="hero-stat"><div class="hero-stat-num"><?= $totalVagas ?>+</div><div class="hero-stat-label">Vagas</div></div>
          <div class="hero-stat"><div class="hero-stat-num"><?= $totalEmpresas ?>+</div><div class="hero-stat-label">Empresas</div></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" style="background:var(--bg-soft);padding:56px 24px;">
  <div class="container">
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background:#EDE9FE;color:#7C3AED;">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        </div>
        <div class="stat-num" data-target="<?= $totalVagas ?>" data-suffix="+">0</div>
        <div class="stat-label">Vagas Abertas</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#D1FAE5;color:#059669;">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 4 3 6 3s6-1 6-3v-5"/></svg>
        </div>
        <div class="stat-num" data-target="<?= $totalAlunos ?>" data-suffix="+">0</div>
        <div class="stat-label">Estudantes Cadastrados</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#DBEAFE;color:#2563EB;">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/></svg>
        </div>
        <div class="stat-num" data-target="<?= $totalEmpresas ?>" data-suffix="+">0</div>
        <div class="stat-label">Empresas Parceiras</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#FEF3C7;color:#B45309;">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="stat-num" data-target="<?= $totalCandidaturas ?>" data-suffix="+">0</div>
        <div class="stat-label">Candidaturas Realizadas</div>
      </div>
    </div>
  </div>
</section>

<section class="section" style="background:var(--bg);">
  <div class="container">
    <div class="section-header">
      <div class="section-tag">Por que usar o InternSHIP Conect</div>
      <h2>Tudo que você precisa em um só lugar</h2>
      <p>Ferramentas pensadas para estudantes e empresas que levam a sério o futuro</p>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon" style="background:#EDE9FE;color:#7C3AED;">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </div>
        <h3>Busca Inteligente</h3>
        <p>Filtre por área, modalidade, cidade, bolsa e muito mais. Encontre a vaga perfeita em segundos.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:#D1FAE5;color:#059669;">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        </div>
        <h3>Candidatura em 1 Click</h3>
        <p>Perfil completo, candidatura instantânea. Sem burocracia, sem complicação.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:#DBEAFE;color:#2563EB;">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="3" y1="20" x2="21" y2="20"/></svg>
        </div>
        <h3>Dashboard Completo</h3>
        <p>Acompanhe suas candidaturas em tempo real. Saiba o status de cada processo seletivo.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:#FCE7F3;color:#9D174D;">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
        </div>
        <h3>Empresas Verificadas</h3>
        <p>Todas as empresas passam por verificação. Vagas reais de empresas reais.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:#FEF3C7;color:#B45309;">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 4 3 6 3s6-1 6-3v-5"/></svg>
        </div>
        <h3>Para Universitários</h3>
        <p>Plataforma focada em estudantes universitários. Vagas com horários compatíveis com a faculdade.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:#FEE2E2;color:#B91C1C;">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><line x1="12" y1="6" x2="12" y2="8"/><line x1="12" y1="16" x2="12" y2="18"/></svg>
        </div>
        <h3>100% Gratuito</h3>
        <p>Sem mensalidade, sem taxa por candidatura. Gratuito para estudantes, sempre.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" style="background:var(--gray-50);padding:64px 24px;border-top:1px solid var(--gray-200);">
  <div class="container text-center">
    <?php if (isLoggedIn()): ?>
      <?php if (isAluno()): ?>
        <h2 style="margin-bottom:12px;">Encontre seu próximo estágio</h2>
        <p style="color:var(--gray-600);margin-bottom:28px;">Explore as vagas disponíveis ou acompanhe suas candidaturas no painel.</p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
          <a href="/teste/vagas.php" class="btn btn-primary">Ver vagas</a>
          <a href="/teste/dashboard/aluno.php" class="btn btn-secondary">Meu painel</a>
        </div>
      <?php elseif (isEmpresa()): ?>
        <h2 style="margin-bottom:12px;">Recrute talentos universitários</h2>
        <p style="color:var(--gray-600);margin-bottom:28px;">Publique uma nova vaga ou acompanhe as candidaturas recebidas.</p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
          <a href="/teste/empresa/publicar.php" class="btn btn-primary">+ Publicar vaga</a>
          <a href="/teste/dashboard/empresa.php" class="btn btn-secondary">Meu painel</a>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <h2 style="margin-bottom:12px;">Cadastre-se e candidate-se</h2>
      <p style="color:var(--gray-600);margin-bottom:28px;">Crie sua conta gratuita para enviar candidaturas e acompanhar o status delas.</p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="/teste/register.php" class="btn btn-primary">Criar conta</a>
        <a href="/teste/vagas.php" class="btn btn-secondary">Ver vagas</a>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
