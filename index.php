<?php
$pageTitle = 'InternSHIP Conect — Encontre seu Estágio Ideal';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$db = getDB();

$totalVagas   = $db->query("SELECT COUNT(*) FROM vagas WHERE ativa=1")->fetchColumn();
$totalAlunos  = $db->query("SELECT COUNT(*) FROM alunos")->fetchColumn();
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
    return match($m) { 'remoto' => '🌐 Remoto', 'hibrido' => '🔀 Híbrido', default => '🏢 Presencial' };
}
function modalidadeBadge($m) {
    return match($m) { 'remoto' => 'badge-green', 'hibrido' => 'badge-blue', default => 'badge-gray' };
}
?>

<section class="hero">
  <div class="hero-inner">
    <div class="hero-content animate-in">
      <div class="hero-tag">
        ✨ Plataforma #1 de Estágios da Região
      </div>
      <h1>Seu próximo <span>grande passo</span> começa aqui</h1>
      <p>Conectamos estudantes do IFRS às empresas mais inovadoras da região. Encontre o estágio que vai transformar sua carreira.</p>

      <div class="hero-actions">
        <a href="/teste/vagas.php" class="btn btn-primary btn-lg">
          🔍 Explorar Vagas
        </a>
        <a href="/teste/register.php" class="btn btn-white btn-lg">
          Criar Perfil Grátis
        </a>
      </div>

      <div class="hero-search">
        <span style="color:rgba(255,255,255,.5);font-size:1.1rem;">🔍</span>
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

<section class="section" style="background:#fff;padding:56px 24px;">
  <div class="container">
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background:#EDE9FE;">🎯</div>
        <div class="stat-num" data-target="<?= $totalVagas ?>" data-suffix="+">0</div>
        <div class="stat-label">Vagas Abertas</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#D1FAE5;">🎓</div>
        <div class="stat-num" data-target="<?= $totalAlunos ?>" data-suffix="+">0</div>
        <div class="stat-label">Estudantes Cadastrados</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#DBEAFE;">🏢</div>
        <div class="stat-num" data-target="<?= $totalEmpresas ?>" data-suffix="+">0</div>
        <div class="stat-label">Empresas Parceiras</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#FEF3C7;">✅</div>
        <div class="stat-num" data-target="<?= $totalCandidaturas ?>" data-suffix="+">0</div>
        <div class="stat-label">Candidaturas Realizadas</div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <div class="section-tag">Vagas em Destaque</div>
      <h2>Oportunidades feitas para você</h2>
      <p>As melhores vagas de estágio das empresas mais inovadoras do mercado</p>
    </div>

    <div class="jobs-grid">
      <?php foreach ($vagas as $v): ?>
      <div class="job-card <?= $v['destaque'] ? 'destaque' : '' ?>">
        <div class="job-card-header">
          <div class="company-logo">
            <?php if ($v['logo']): ?>
              <img src="<?= htmlspecialchars($v['logo']) ?>" alt="">
            <?php else: ?>
              <?= mb_strtoupper(mb_substr($v['nome_empresa'], 0, 2)) ?>
            <?php endif; ?>
          </div>
          <div>
            <div class="job-card-title"><?= htmlspecialchars($v['titulo']) ?></div>
            <div class="job-card-company"><?= htmlspecialchars($v['nome_empresa']) ?></div>
          </div>
        </div>

        <div class="job-card-pills">
          <span class="badge <?= modalidadeBadge($v['modalidade']) ?>"><?= modalidadeLabel($v['modalidade']) ?></span>
          <span class="badge badge-purple"><?= htmlspecialchars($v['area']) ?></span>
        </div>

        <div class="job-card-infos">
          <div class="job-card-info">📍 <?= htmlspecialchars($v['cidade']) ?>/<?= $v['estado'] ?></div>
          <div class="job-card-info">⏰ <?= $v['carga_horaria'] ?>h por semana</div>
        </div>

        <div class="job-card-bolsa">R$ <?= number_format($v['bolsa'], 0, ',', '.') ?><span style="font-size:.8rem;font-weight:400;color:var(--gray-400)">/mês</span></div>

        <div class="job-card-footer">
          <span class="job-card-date">📅 <?= date('d/m/Y', strtotime($v['criado_em'])) ?></span>
          <a href="/teste/vaga.php?id=<?= $v['id'] ?>" class="btn btn-primary btn-sm">Ver Vaga →</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-8">
      <a href="/teste/vagas.php" class="btn btn-secondary btn-lg">Ver todas as vagas →</a>
    </div>
  </div>
</section>

<section class="section" style="background:#fff;">
  <div class="container">
    <div class="section-header">
      <div class="section-tag">Por que usar o InternSHIP Conect</div>
      <h2>Tudo que você precisa em um só lugar</h2>
      <p>Ferramentas pensadas para estudantes e empresas que levam a sério o futuro</p>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon" style="background:#EDE9FE;">🔍</div>
        <h3>Busca Inteligente</h3>
        <p>Filtre por área, modalidade, cidade, bolsa e muito mais. Encontre a vaga perfeita em segundos.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:#D1FAE5;">⚡</div>
        <h3>Candidatura em 1 Click</h3>
        <p>Perfil completo, candidatura instantânea. Sem burocracia, sem complicação.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:#DBEAFE;">📊</div>
        <h3>Dashboard Completo</h3>
        <p>Acompanhe suas candidaturas em tempo real. Saiba o status de cada processo seletivo.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:#FCE7F3;">🏢</div>
        <h3>Empresas Verificadas</h3>
        <p>Todas as empresas passam por verificação. Vagas reais de empresas reais.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:#FEF3C7;">🎓</div>
        <h3>Para Universitários</h3>
        <p>Plataforma focada em estudantes universitários. Vagas com horários compatíveis com a faculdade.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon" style="background:#FEE2E2;">🆓</div>
        <h3>100% Gratuito</h3>
        <p>Sem mensalidade, sem taxa por candidatura. Gratuito para estudantes, sempre.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" style="background:linear-gradient(135deg,#0F172A,#1E1B4B);padding:100px 24px;">
  <div class="text-center" style="position:relative;z-index:1;">
    <div class="section-tag" style="background:rgba(167,139,250,.2);color:var(--primary-light);">Comece agora</div>
    <h2 style="color:#fff;margin-bottom:16px;">Pronto para decolar sua carreira?</h2>
    <p style="color:rgba(255,255,255,.65);font-size:1.1rem;max-width:480px;margin:0 auto 40px;">
      Junte-se a milhares de estudantes que já encontraram seu estágio ideal pelo InternSHIP Conect.
    </p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
      <a href="/teste/register.php" class="btn btn-primary btn-lg">✨ Criar conta grátis</a>
      <a href="/teste/vagas.php" class="btn btn-white btn-lg">🔍 Ver vagas</a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
