<?php
$pageTitle = 'InternSHIP Conect — Buscar Vagas';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$db = getDB();

$q = trim($_GET['q'] ?? '');
$area = $_GET['area'] ?? '';
$modalidade = $_GET['modalidade'] ?? '';
$cidade = $_GET['cidade'] ?? '';
$bolsa_min = (int)($_GET['bolsa_min'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$where = ['v.ativa = 1'];
$params = [];

if ($q) {
    $where[] = '(v.titulo LIKE ? OR v.descricao LIKE ? OR e.nome_empresa LIKE ? OR v.area LIKE ?)';
    $params = array_merge($params, ["%$q%", "%$q%", "%$q%", "%$q%"]);
}
if ($area) { $where[] = 'v.area = ?'; $params[] = $area; }
if ($modalidade) { $where[] = 'v.modalidade = ?'; $params[] = $modalidade; }
if ($cidade) { $where[] = 'v.cidade = ?'; $params[] = $cidade; }
if ($bolsa_min) { $where[] = 'v.bolsa >= ?'; $params[] = $bolsa_min; }

$whereSQL = implode(' AND ', $where);

$stmtCount = $db->prepare("SELECT COUNT(*) FROM vagas v JOIN empresas e ON v.empresa_id = e.id WHERE $whereSQL");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalPages = (int)ceil($total / $perPage);
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("
    SELECT v.*, e.nome_empresa, e.logo, e.setor
    FROM vagas v
    JOIN empresas e ON v.empresa_id = e.id
    WHERE $whereSQL
    ORDER BY v.destaque DESC, v.criado_em DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$vagas = $stmt->fetchAll();

$areas = $db->query("SELECT DISTINCT area FROM vagas WHERE ativa=1 AND area IS NOT NULL ORDER BY area")->fetchAll(PDO::FETCH_COLUMN);

$cidadesDestaque = ['Bento Gonçalves', 'Carlos Barbosa', 'Garibaldi'];
$cidadesIFRS = [
    'Alvorada', 'Antônio Prado', 'Canoas', 'Caxias do Sul', 'Erechim',
    'Farroupilha', 'Feliz', 'Flores da Cunha', 'Ibirubá', 'Nova Petrópolis',
    'Osório', 'Porto Alegre', 'Rio Grande', 'Rolante', 'Sertão',
    'Vacaria', 'Veranópolis', 'Viamão',
];
sort($cidadesIFRS, SORT_LOCALE_STRING);

function modalidadeBadge($m) {
    return match($m) { 'remoto' => 'badge-green', 'hibrido' => 'badge-blue', default => 'badge-gray' };
}
function modalidadeLabel($m) {
    return match($m) { 'remoto' => 'Remoto', 'hibrido' => 'Híbrido', default => 'Presencial' };
}

include __DIR__ . '/includes/header.php';
?>

<div style="background:linear-gradient(135deg,#0F172A,#1E1B4B);padding:48px 24px 32px;">
  <div class="container">
    <h1 style="color:#fff;font-size:2rem;margin-bottom:8px;">
      <?= $q ? "Resultados para \"" . htmlspecialchars($q) . "\"" : "Todas as Vagas" ?>
    </h1>
    <p style="color:rgba(255,255,255,.6);">
      <?= $total ?> vaga<?= $total !== 1 ? 's' : '' ?> encontrada<?= $total !== 1 ? 's' : '' ?>
      <?= $area ? " em <strong style='color:#A78BFA'>$area</strong>" : '' ?>
      <?= $modalidade ? " · <strong style='color:#A78BFA'>" . modalidadeLabel($modalidade) . "</strong>" : '' ?>
    </p>
  </div>
</div>

<div style="background:var(--gray-50);padding:32px 24px 80px;">
  <div class="container">

    <form method="GET" class="filter-bar">
      <div class="filter-group" style="flex:2;">
        <span class="filter-label">Buscar</span>
        <input type="text" name="q" class="form-control" placeholder="Cargo, empresa, área..." value="<?= htmlspecialchars($q) ?>">
      </div>
      <div class="filter-group">
        <span class="filter-label">Área</span>
        <select name="area" class="form-control">
          <option value="">Todas as áreas</option>
          <?php foreach ($areas as $a): ?>
            <option value="<?= htmlspecialchars($a) ?>" <?= $area === $a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-group">
        <span class="filter-label">Modalidade</span>
        <select name="modalidade" class="form-control">
          <option value="">Qualquer</option>
          <option value="presencial" <?= $modalidade === 'presencial' ? 'selected' : '' ?>>Presencial</option>
          <option value="remoto" <?= $modalidade === 'remoto' ? 'selected' : '' ?>>Remoto</option>
          <option value="hibrido" <?= $modalidade === 'hibrido' ? 'selected' : '' ?>>Híbrido</option>
        </select>
      </div>
      <div class="filter-group">
        <span class="filter-label">Cidade</span>
        <select name="cidade" class="form-control">
          <option value="">Todas</option>
          <optgroup label="Destaque">
            <?php foreach ($cidadesDestaque as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>" <?= $cidade === $c ? 'selected' : '' ?> style="color:#7C3AED;font-weight:600;background:#F5F3FF;"><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </optgroup>
          <optgroup label="Outras cidades">
            <?php foreach ($cidadesIFRS as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>" <?= $cidade === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </optgroup>
        </select>
      </div>
      <div class="filter-group">
        <span class="filter-label">Bolsa mínima</span>
        <select name="bolsa_min" class="form-control">
          <option value="0">Qualquer</option>
          <option value="500" <?= $bolsa_min === 500 ? 'selected' : '' ?>>R$ 500+</option>
          <option value="1000" <?= $bolsa_min === 1000 ? 'selected' : '' ?>>R$ 1.000+</option>
          <option value="1500" <?= $bolsa_min === 1500 ? 'selected' : '' ?>>R$ 1.500+</option>
          <option value="2000" <?= $bolsa_min === 2000 ? 'selected' : '' ?>>R$ 2.000+</option>
        </select>
      </div>
      <div style="display:flex;gap:8px;align-items:flex-end;">
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <?php if ($q || $area || $modalidade || $cidade || $bolsa_min): ?>
          <a href="/teste/vagas.php" class="btn btn-ghost">X Limpar</a>
        <?php endif; ?>
      </div>
    </form>

    <div style="display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap;">
      <?php foreach (['Tecnologia','Design','Marketing','Dados','Produto'] as $tag): ?>
        <a href="?area=<?= urlencode($tag) ?>" class="badge badge-<?= $area === $tag ? 'purple' : 'gray' ?>" style="padding:7px 16px;font-size:.85rem;cursor:pointer;text-decoration:none;">
          <?= $tag ?>
        </a>
      <?php endforeach; ?>
      <a href="?modalidade=remoto" class="badge badge-<?= $modalidade === 'remoto' ? 'green' : 'gray' ?>" style="padding:7px 16px;font-size:.85rem;cursor:pointer;text-decoration:none;">
        Só Remoto
      </a>
    </div>

    <?php if (empty($vagas)): ?>
      <div class="empty-state">
        <div class="empty-icon"></div>
        <h3>Nenhuma vaga encontrada</h3>
        <p>Tente mudar os filtros ou <a href="/teste/vagas.php">ver todas as vagas</a></p>
      </div>
    <?php else: ?>
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
            <?php if ($v['area']): ?>
              <span class="badge badge-purple"><?= htmlspecialchars($v['area']) ?></span>
            <?php endif; ?>
          </div>

          <div class="job-card-infos">
            <div class="job-card-info"><?= htmlspecialchars($v['cidade']) ?>/<?= $v['estado'] ?></div>
            <?php if ($v['carga_horaria']): ?>
              <div class="job-card-info">⏰ <?= $v['carga_horaria'] ?>h por semana</div>
            <?php endif; ?>
          </div>

          <?php if ($v['bolsa']): ?>
            <div class="job-card-bolsa">R$ <?= number_format($v['bolsa'], 0, ',', '.') ?><span style="font-size:.8rem;font-weight:400;color:var(--gray-400)">/mês</span></div>
          <?php endif; ?>

          <div class="job-card-footer">
            <span class="job-card-date"><?= date('d/m', strtotime($v['criado_em'])) ?></span>
            <a href="/teste/vaga.php?id=<?= $v['id'] ?>" class="btn btn-primary btn-sm">Ver Vaga →</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-btn">←</a>
          <?php endif; ?>
          <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
          <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-btn">→</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
