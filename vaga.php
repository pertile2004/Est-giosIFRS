<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /teste/vagas.php'); exit; }

$db = getDB();
$stmt = $db->prepare("
    SELECT v.*, e.nome_empresa, e.logo, e.descricao AS empresa_desc, e.site, e.setor, e.cidade AS emp_cidade, e.estado AS emp_estado
    FROM vagas v
    JOIN empresas e ON v.empresa_id = e.id
    WHERE v.id = ? AND v.ativa = 1
");
$stmt->execute([$id]);
$vaga = $stmt->fetch();

if (!$vaga) { header('Location: /teste/vagas.php'); exit; }

$jaCandidatou = false;
if (isLoggedIn() && isAluno() && $_SESSION['perfil_id']) {
    $stmtC = $db->prepare("SELECT id FROM candidaturas WHERE aluno_id = ? AND vaga_id = ?");
    $stmtC->execute([$_SESSION['perfil_id'], $id]);
    $jaCandidatou = (bool)$stmtC->fetch();
}

$mensagem = '';
$erroApply = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidatar'])) {
    if (!isLoggedIn()) {
        header('Location: /teste/login.php');
        exit;
    }
    if (!isAluno()) {
        $erroApply = 'Somente estudantes podem se candidatar.';
    } elseif ($jaCandidatou) {
        $erroApply = 'Você já se candidatou para esta vaga.';
    } else {
        $carta = trim($_POST['carta'] ?? '');
        $stmt2 = $db->prepare("INSERT INTO candidaturas (aluno_id, vaga_id, carta) VALUES (?, ?, ?)");
        $stmt2->execute([$_SESSION['perfil_id'], $id, $carta]);
        $jaCandidatou = true;
        $mensagem = 'Candidatura enviada com sucesso! 🎉';
    }
}

$similares = $db->prepare("
    SELECT v.id, v.titulo, v.bolsa, v.modalidade, e.nome_empresa
    FROM vagas v JOIN empresas e ON v.empresa_id = e.id
    WHERE v.ativa=1 AND v.id != ? AND v.area = ?
    LIMIT 3
");
$similares->execute([$id, $vaga['area']]);
$similares = $similares->fetchAll();

$pageTitle = htmlspecialchars($vaga['titulo']) . ' — InternSHIP Conect';

function modalidadeLabel($m) { return match($m) { 'remoto' => '🌐 Remoto', 'hibrido' => '🔀 Híbrido', default => '🏢 Presencial' }; }
function modalidadeBadge($m) { return match($m) { 'remoto' => 'badge-green', 'hibrido' => 'badge-blue', default => 'badge-gray' }; }

include __DIR__ . '/includes/header.php';
?>

<div class="job-detail-hero">
  <div class="container">
    <div style="padding-top:8px;padding-bottom:32px;">
      <a href="/teste/vagas.php" style="color:rgba(255,255,255,.5);font-size:.85rem;display:inline-flex;align-items:center;gap:6px;margin-bottom:24px;">
        ← Voltar às vagas
      </a>
    </div>
  </div>
</div>

<div style="background:var(--gray-50);padding:0 24px 80px;">
  <div class="container">
    <div class="grid-2" style="align-items:start;margin-top:-24px;">

      <div style="grid-column:1/2;">
        <div style="background:#fff;border-radius:var(--radius-lg);padding:36px;border:1px solid var(--gray-200);box-shadow:var(--shadow);">

          <?php if ($mensagem): ?>
            <div class="alert alert-success">🎉 <?= htmlspecialchars($mensagem) ?></div>
          <?php endif; ?>
          <?php if ($erroApply): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($erroApply) ?></div>
          <?php endif; ?>

          <div class="job-detail-header">
            <div class="company-logo-lg">
              <?php if ($vaga['logo']): ?>
                <img src="<?= htmlspecialchars($vaga['logo']) ?>" alt="">
              <?php else: ?>
                <?= mb_strtoupper(mb_substr($vaga['nome_empresa'], 0, 2)) ?>
              <?php endif; ?>
            </div>
            <div>
              <h1 style="font-size:1.6rem;margin-bottom:6px;"><?= htmlspecialchars($vaga['titulo']) ?></h1>
              <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <span style="font-size:1rem;color:var(--gray-500);font-weight:600;"><?= htmlspecialchars($vaga['nome_empresa']) ?></span>
                <span class="badge <?= modalidadeBadge($vaga['modalidade']) ?>"><?= modalidadeLabel($vaga['modalidade']) ?></span>
                <?php if ($vaga['destaque']): ?>
                  <span class="badge badge-yellow">⭐ Destaque</span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="job-meta-grid">
            <div class="job-meta-item">
              <div class="job-meta-icon">📍</div>
              <div>
                <div class="job-meta-label">Localização</div>
                <div class="job-meta-value"><?= htmlspecialchars($vaga['cidade']) ?>/<?= $vaga['estado'] ?></div>
              </div>
            </div>
            <div class="job-meta-item">
              <div class="job-meta-icon">💰</div>
              <div>
                <div class="job-meta-label">Bolsa mensal</div>
                <div class="job-meta-value" style="color:var(--accent);">R$ <?= number_format($vaga['bolsa'], 0, ',', '.') ?></div>
              </div>
            </div>
            <div class="job-meta-item">
              <div class="job-meta-icon">⏰</div>
              <div>
                <div class="job-meta-label">Carga horária</div>
                <div class="job-meta-value"><?= $vaga['carga_horaria'] ?>h por semana</div>
              </div>
            </div>
            <div class="job-meta-item">
              <div class="job-meta-icon">🎯</div>
              <div>
                <div class="job-meta-label">Área</div>
                <div class="job-meta-value"><?= htmlspecialchars($vaga['area']) ?></div>
              </div>
            </div>
          </div>

          <div class="job-section">
            <h3>Sobre a vaga</h3>
            <p><?= nl2br(htmlspecialchars($vaga['descricao'])) ?></p>
          </div>

          <?php if ($vaga['requisitos']): ?>
          <div class="job-section">
            <h3>✅ Requisitos</h3>
            <ul>
              <?php foreach (explode("\n", $vaga['requisitos']) as $req): ?>
                <?php $req = trim($req); if ($req): ?>
                  <li><?= htmlspecialchars($req) ?></li>
                <?php endif; ?>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>

          <?php if ($vaga['beneficios']): ?>
          <div class="job-section">
            <h3>🎁 Benefícios</h3>
            <ul>
              <?php foreach (explode(",", $vaga['beneficios']) as $ben): ?>
                <?php $ben = trim($ben); if ($ben): ?>
                  <li><?= htmlspecialchars($ben) ?></li>
                <?php endif; ?>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>

          <?php if ($vaga['empresa_desc']): ?>
          <div class="job-section">
            <h3>🏢 Sobre a <?= htmlspecialchars($vaga['nome_empresa']) ?></h3>
            <p><?= nl2br(htmlspecialchars($vaga['empresa_desc'])) ?></p>
            <?php if ($vaga['site']): ?>
              <a href="<?= htmlspecialchars($vaga['site']) ?>" target="_blank" class="btn btn-ghost btn-sm" style="margin-top:12px;">🌐 Site da empresa</a>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>

        <?php if (!empty($similares)): ?>
        <div style="margin-top:24px;">
          <h3 style="margin-bottom:16px;font-size:1.1rem;">Vagas similares</h3>
          <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach ($similares as $s): ?>
            <a href="/teste/vaga.php?id=<?= $s['id'] ?>" style="text-decoration:none;">
              <div class="card" style="padding:16px 20px;display:flex;justify-content:space-between;align-items:center;transition:all .2s;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--gray-200)'">
                <div>
                  <div style="font-weight:700;color:var(--gray-900);"><?= htmlspecialchars($s['titulo']) ?></div>
                  <div style="font-size:.82rem;color:var(--gray-500);"><?= htmlspecialchars($s['nome_empresa']) ?></div>
                </div>
                <div style="text-align:right;">
                  <div style="font-weight:700;color:var(--accent);">R$ <?= number_format($s['bolsa'], 0, ',', '.') ?></div>
                  <span class="badge <?= modalidadeBadge($s['modalidade']) ?>"><?= modalidadeLabel($s['modalidade']) ?></span>
                </div>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div>
        <div class="apply-card">
          <div style="text-align:center;margin-bottom:16px;">
            <div class="bolsa-display">R$ <?= number_format($vaga['bolsa'], 0, ',', '.') ?></div>
            <div style="color:var(--gray-500);font-size:.85rem;">por mês</div>
          </div>

          <div class="divider"></div>

          <?php if ($jaCandidatou): ?>
            <div class="alert alert-success">
              ✅ Você já se candidatou!
            </div>
            <a href="/teste/dashboard/aluno.php" class="btn btn-ghost btn-block">Ver minhas candidaturas</a>
          <?php elseif (isLoggedIn() && isAluno()): ?>
            <form method="POST">
              <div class="form-group">
                <label class="form-label">Carta de apresentação (opcional)</label>
                <textarea name="carta" class="form-control" rows="5" placeholder="Apresente-se brevemente, destaque suas habilidades e por que você é o candidato ideal..."></textarea>
              </div>
              <button type="submit" name="candidatar" class="btn btn-primary btn-block btn-lg">
                ⚡ Candidatar-se agora
              </button>
            </form>
          <?php elseif (isLoggedIn() && isEmpresa()): ?>
            <div class="alert alert-info">
              Empresas não podem se candidatar a vagas.
            </div>
          <?php else: ?>
            <a href="/teste/login.php" class="btn btn-primary btn-block btn-lg" style="margin-bottom:10px;">
              🔐 Entrar para se candidatar
            </a>
            <a href="/teste/register.php" class="btn btn-secondary btn-block">
              Criar conta grátis
            </a>
          <?php endif; ?>

          <div class="divider"></div>

          <div style="display:flex;flex-direction:column;gap:8px;font-size:.85rem;color:var(--gray-500);">
            <div style="display:flex;align-items:center;gap:8px;">📅 Publicada em <?= date('d/m/Y', strtotime($vaga['criado_em'])) ?></div>
            <div style="display:flex;align-items:center;gap:8px;">🏢 <?= htmlspecialchars($vaga['setor']) ?></div>
          </div>

          <div class="divider"></div>

          <div style="display:flex;gap:8px;">
            <button class="btn btn-ghost btn-sm" style="flex:1;" onclick="navigator.share ? navigator.share({title:'<?= htmlspecialchars($vaga['titulo']) ?>',url:window.location.href}) : showToast('Link copiado!','success') || navigator.clipboard.writeText(window.location.href)">
              🔗 Compartilhar
            </button>
            <button class="btn btn-ghost btn-sm" style="flex:1;" onclick="showToast('Vaga salva!','success')">
              🔖 Salvar
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
