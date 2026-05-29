<?php
$pageTitle = 'Painel da Empresa — InternSHIP Conect';
require_once __DIR__ . '/../includes/auth.php';
requireEmpresa();

$db = getDB();
$empresaId = $_SESSION['perfil_id'];

$empresa = $db->prepare("SELECT e.*, u.nome, u.email FROM empresas e JOIN usuarios u ON e.usuario_id = u.id WHERE e.id = ?");
$empresa->execute([$empresaId]);
$empresa = $empresa->fetch();

$vagas = $db->prepare("
    SELECT v.*,
           (SELECT COUNT(*) FROM candidaturas WHERE vaga_id = v.id) AS total_cand,
           (SELECT COUNT(*) FROM candidaturas WHERE vaga_id = v.id AND status = 'pendente') AS pendentes
    FROM vagas v
    WHERE v.empresa_id = ?
    ORDER BY v.criado_em DESC
");
$vagas->execute([$empresaId]);
$vagas = $vagas->fetchAll();

$recentes = $db->prepare("
    SELECT c.*, v.titulo AS vaga_titulo, u.nome AS aluno_nome, al.curso, al.universidade
    FROM candidaturas c
    JOIN vagas v ON c.vaga_id = v.id
    JOIN alunos al ON c.aluno_id = al.id
    JOIN usuarios u ON al.usuario_id = u.id
    WHERE v.empresa_id = ?
    ORDER BY c.criado_em DESC
    LIMIT 8
");
$recentes->execute([$empresaId]);
$recentes = $recentes->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $candId = (int)$_POST['candidatura_id'];
    $status = $_POST['status'];
    $db->prepare("UPDATE candidaturas SET status=? WHERE id=? AND vaga_id IN (SELECT id FROM vagas WHERE empresa_id=?)")
       ->execute([$status, $candId, $empresaId]);
    header('Location: /teste/dashboard/empresa.php#candidaturas');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_vaga'])) {
    $vagaId = (int)$_POST['vaga_id'];
    $db->prepare("UPDATE vagas SET ativa = !ativa WHERE id=? AND empresa_id=?")->execute([$vagaId, $empresaId]);
    header('Location: /teste/dashboard/empresa.php');
    exit;
}

$totalVagas = count($vagas);
$vagasAtivas = count(array_filter($vagas, fn($v) => $v['ativa']));
$totalCand = array_sum(array_column($vagas, 'total_cand'));
$pendentes = array_sum(array_column($vagas, 'pendentes'));

function statusBadge($s) {
    return match($s) {
        'aprovado' => '<span class="badge badge-green">Aprovado</span>',
        'recusado' => '<span class="badge badge-red">Recusado</span>',
        'visualizado' => '<span class="badge badge-blue">Visualizado</span>',
        default => '<span class="badge badge-yellow">⏳ Pendente</span>',
    };
}

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <aside class="sidebar">
    <div class="sidebar-user">
      <div class="sidebar-avatar" style="border-radius:14px;font-size:1.3rem;">

      </div>
      <div class="sidebar-name"><?= htmlspecialchars($empresa['nome_empresa']) ?></div>
      <div class="sidebar-role"><?= htmlspecialchars($empresa['setor'] ?: 'Empresa') ?></div>
      <div style="margin-top:4px;font-size:.75rem;color:var(--gray-400);"><?= $empresa['cidade'] ?>/<?= $empresa['estado'] ?></div>
    </div>

    <nav class="sidebar-nav">
      <span class="sidebar-section">Principal</span>
      <a href="/teste/dashboard/empresa.php" class="active"><span class="icon"></span> Painel</a>
      <a href="/teste/empresa/publicar.php"><span class="icon"></span> Publicar Vaga</a>

      <span class="sidebar-section">Gestão</span>
      <a href="#vagas" onclick="document.getElementById('vagas').scrollIntoView({behavior:'smooth'});return false;"><span class="icon"></span> Minhas Vagas <span class="badge badge-purple" style="margin-left:auto"><?= $vagasAtivas ?></span></a>
      <a href="#candidaturas" onclick="document.getElementById('candidaturas').scrollIntoView({behavior:'smooth'});return false;"><span class="icon"></span> Candidaturas <?php if ($pendentes): ?><span class="badge badge-yellow" style="margin-left:auto"><?= $pendentes ?></span><?php endif; ?></a>

      <span class="sidebar-section">Conta</span>
      <a href="#perfil" onclick="document.getElementById('perfil').scrollIntoView({behavior:'smooth'});return false;"><span class="icon"></span> Perfil da Empresa</a>
      <a href="/teste/logout.php"><span class="icon"></span> Sair</a>
    </nav>
  </aside>

  <main class="dashboard-main">
    <?php if (isset($_GET['welcome'])): ?>
      <div class="alert alert-success">Conta criada. Complete o perfil da empresa e publique a primeira vaga.</div>
    <?php endif; ?>

    <div class="dashboard-header">
      <h1>Painel da Empresa </h1>
      <p>Gerencie suas vagas e acompanhe os candidatos</p>
    </div>

    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-top">
          <div><div class="kpi-num"><?= $vagasAtivas ?></div><div class="kpi-label">Vagas Ativas</div></div>
          <div class="kpi-icon" style="background:#EDE9FE;font-size:1.4rem;"></div>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top">
          <div><div class="kpi-num"><?= $totalCand ?></div><div class="kpi-label">Candidaturas Total</div></div>
          <div class="kpi-icon" style="background:#D1FAE5;font-size:1.4rem;"></div>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top">
          <div><div class="kpi-num"><?= $pendentes ?></div><div class="kpi-label">Pendentes</div></div>
          <div class="kpi-icon" style="background:#FEF3C7;font-size:1.4rem;">⏳</div>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top">
          <div><div class="kpi-num"><?= $totalVagas ?></div><div class="kpi-label">Total de Vagas</div></div>
          <div class="kpi-icon" style="background:#DBEAFE;font-size:1.4rem;"></div>
        </div>
      </div>
    </div>

    <div class="card mb-6" id="vagas">
      <div class="card-header">
        <h3>Minhas Vagas</h3>
        <a href="/teste/empresa/publicar.php" class="btn btn-primary btn-sm">+ Publicar vaga</a>
      </div>
      <?php if (empty($vagas)): ?>
        <div class="card-body">
          <div class="empty-state">
            <div class="empty-icon"></div>
            <h3>Nenhuma vaga publicada</h3>
            <p>Comece recrutando talentos publicando sua primeira vaga</p>
            <a href="/teste/empresa/publicar.php" class="btn btn-primary" style="margin-top:16px;">+ Publicar primeira vaga</a>
          </div>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Vaga</th>
                <th>Modalidade</th>
                <th>Bolsa</th>
                <th>Candidaturas</th>
                <th>Status</th>
                <th>Publicada</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($vagas as $v): ?>
              <tr>
                <td>
                  <div class="td-title"><?= htmlspecialchars($v['titulo']) ?></div>
                  <div style="font-size:.78rem;color:var(--gray-400);"><?= htmlspecialchars($v['area']) ?> · <?= $v['cidade'] ?>/<?= $v['estado'] ?></div>
                </td>
                <td>
                  <span class="badge <?= $v['modalidade'] === 'remoto' ? 'badge-green' : ($v['modalidade'] === 'hibrido' ? 'badge-blue' : 'badge-gray') ?>">
                    <?= ucfirst($v['modalidade']) ?>
                  </span>
                </td>
                <td style="font-weight:700;color:var(--accent);">R$ <?= number_format($v['bolsa'], 0, ',', '.') ?></td>
                <td>
                  <span style="font-weight:700;"><?= $v['total_cand'] ?></span>
                  <?php if ($v['pendentes']): ?>
                    <span class="badge badge-yellow" style="margin-left:4px;"><?= $v['pendentes'] ?> novos</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?= $v['ativa'] ? 'badge-green' : 'badge-gray' ?>">
                    <?= $v['ativa'] ? 'Ativa' : '⏸️ Pausada' ?>
                  </span>
                </td>
                <td style="font-size:.8rem;color:var(--gray-400);"><?= date('d/m/Y', strtotime($v['criado_em'])) ?></td>
                <td>
                  <div style="display:flex;gap:6px;">
                    <a href="/teste/vaga.php?id=<?= $v['id'] ?>" class="btn btn-ghost btn-sm"></a>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="vaga_id" value="<?= $v['id'] ?>">
                      <button type="submit" name="toggle_vaga" class="btn btn-ghost btn-sm" title="<?= $v['ativa'] ? 'Pausar' : 'Ativar' ?>">
                        <?= $v['ativa'] ? '⏸️' : '▶️' ?>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="card mb-6" id="candidaturas">
      <div class="card-header">
        <h3>Candidaturas Recentes</h3>
        <?php if ($pendentes): ?>
          <span class="badge badge-yellow">⏳ <?= $pendentes ?> pendentes</span>
        <?php endif; ?>
      </div>
      <?php if (empty($recentes)): ?>
        <div class="card-body">
          <div class="empty-state">
            <div class="empty-icon"></div>
            <h3>Nenhuma candidatura ainda</h3>
            <p>Publique vagas para começar a receber candidatos</p>
          </div>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Candidato</th>
                <th>Vaga</th>
                <th>Formação</th>
                <th>Status</th>
                <th>Data</th>
                <th>Ação</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentes as $c): ?>
              <tr>
                <td>
                  <div class="td-title"><?= htmlspecialchars($c['aluno_nome']) ?></div>
                </td>
                <td style="font-size:.88rem;"><?= htmlspecialchars($c['vaga_titulo']) ?></td>
                <td style="font-size:.82rem;color:var(--gray-500);">
                  <?= htmlspecialchars($c['curso'] ?: '—') ?>
                  <?php if ($c['universidade']): ?><br><span><?= htmlspecialchars($c['universidade']) ?></span><?php endif; ?>
                </td>
                <td><?= statusBadge($c['status']) ?></td>
                <td style="font-size:.8rem;color:var(--gray-400);"><?= date('d/m/Y', strtotime($c['criado_em'])) ?></td>
                <td>
                  <form method="POST" style="display:flex;gap:6px;">
                    <input type="hidden" name="candidatura_id" value="<?= $c['id'] ?>">
                    <select name="status" class="form-control" style="padding:5px 8px;font-size:.8rem;min-width:110px;">
                      <option value="pendente" <?= $c['status'] === 'pendente' ? 'selected' : '' ?>>⏳ Pendente</option>
                      <option value="visualizado" <?= $c['status'] === 'visualizado' ? 'selected' : '' ?>>Visualizado</option>
                      <option value="aprovado" <?= $c['status'] === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                      <option value="recusado" <?= $c['status'] === 'recusado' ? 'selected' : '' ?>>Recusado</option>
                    </select>
                    <button type="submit" name="update_status" class="btn btn-primary btn-sm">OK</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="card" id="perfil">
      <div class="card-header"><h3>Perfil da Empresa</h3></div>
      <div class="card-body">
        <form method="POST" action="/teste/empresa/update.php">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nome da Empresa</label>
              <input type="text" name="nome_empresa" class="form-control" value="<?= htmlspecialchars($empresa['nome_empresa']) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Setor</label>
              <input type="text" name="setor" class="form-control" value="<?= htmlspecialchars($empresa['setor'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Descrição da Empresa</label>
            <textarea name="descricao" class="form-control" rows="4"><?= htmlspecialchars($empresa['descricao'] ?? '') ?></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Cidade</label>
              <input type="text" name="cidade" class="form-control" value="<?= htmlspecialchars($empresa['cidade'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Site</label>
              <input type="url" name="site" class="form-control" placeholder="https://suaempresa.com" value="<?= htmlspecialchars($empresa['site'] ?? '') ?>">
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Salvar perfil</button>
        </form>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
