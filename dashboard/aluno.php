<?php
$pageTitle = 'Painel do Estudante — InternSHIP Conect';
require_once __DIR__ . '/../includes/auth.php';
requireAluno();

$db = getDB();
$alunoId = $_SESSION['perfil_id'];

$successMsg = '';
$erroMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $curso = trim($_POST['curso'] ?? '');
    $universidade = trim($_POST['universidade'] ?? '');
    $semestre = (int)($_POST['semestre'] ?? 1);
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $sobre = trim($_POST['sobre'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $github = trim($_POST['github'] ?? '');

    $db->prepare("UPDATE alunos SET curso=?,universidade=?,semestre=?,cidade=?,estado=?,sobre=?,linkedin=?,github=? WHERE id=?")
       ->execute([$curso, $universidade, $semestre, $cidade, $estado, $sobre, $linkedin, $github, $alunoId]);
    $db->prepare("UPDATE usuarios SET nome=? WHERE id=?")
       ->execute([trim($_POST['nome'] ?? ''), $_SESSION['usuario_id']]);
    $_SESSION['nome'] = trim($_POST['nome'] ?? '');
    $successMsg = 'Perfil atualizado com sucesso!';

    if (!empty($_FILES['curriculo']) && $_FILES['curriculo']['error'] === UPLOAD_ERR_OK) {
        $salvo = salvarCurriculoAluno($alunoId, $_FILES['curriculo']);
        if ($salvo) {
            $successMsg .= ' Currículo enviado.';
        } else {
            $erroMsg = 'Não foi possível enviar o currículo. Verifique se é um PDF válido com até 3 MB.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remover_curriculo'])) {
    $stmt = $db->prepare("SELECT curriculo_path FROM alunos WHERE id = ?");
    $stmt->execute([$alunoId]);
    $caminho = $stmt->fetchColumn();
    if ($caminho) {
        $arq = __DIR__ . '/../' . ltrim($caminho, '/');
        if (is_file($arq)) @unlink($arq);
        $db->prepare("UPDATE alunos SET curriculo_path = NULL WHERE id = ?")->execute([$alunoId]);
        $successMsg = 'Currículo removido.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remover_favorito'])) {
    $vagaId = (int)$_POST['vaga_id'];
    toggleFavorito($alunoId, $vagaId);
    header('Location: /teste/dashboard/aluno.php#favoritas');
    exit;
}

$favoritas = $db->prepare("
    SELECT v.id, v.titulo, v.bolsa, v.modalidade, v.cidade, v.estado, v.area, e.nome_empresa
    FROM vagas_favoritas f
    JOIN vagas v ON f.vaga_id = v.id
    JOIN empresas e ON v.empresa_id = e.id
    WHERE f.aluno_id = ? AND v.ativa = 1
    ORDER BY f.criado_em DESC
");
$favoritas->execute([$alunoId]);
$favoritas = $favoritas->fetchAll();

$aluno = $db->prepare("SELECT a.*, u.nome, u.email FROM alunos a JOIN usuarios u ON a.usuario_id = u.id WHERE a.id = ?");
$aluno->execute([$alunoId]);
$aluno = $aluno->fetch();

$candidaturas = $db->prepare("
    SELECT c.*, v.titulo, v.bolsa, v.modalidade, v.cidade, v.estado, e.nome_empresa, e.logo
    FROM candidaturas c
    JOIN vagas v ON c.vaga_id = v.id
    JOIN empresas e ON v.empresa_id = e.id
    WHERE c.aluno_id = ?
    ORDER BY c.criado_em DESC
");
$candidaturas->execute([$alunoId]);
$candidaturas = $candidaturas->fetchAll();

$totalCand = count($candidaturas);
$aprovadas = count(array_filter($candidaturas, fn($c) => $c['status'] === 'aprovado'));
$pendentes = count(array_filter($candidaturas, fn($c) => $c['status'] === 'pendente'));
$visualizadas = count(array_filter($candidaturas, fn($c) => $c['status'] === 'visualizado'));

$vagas = $db->prepare("
    SELECT v.id, v.titulo, v.bolsa, v.modalidade, v.area, v.cidade, v.estado, e.nome_empresa
    FROM vagas v JOIN empresas e ON v.empresa_id = e.id
    WHERE v.ativa=1
    ORDER BY v.destaque DESC, v.criado_em DESC LIMIT 4
");
$vagas->execute();
$vagasRec = $vagas->fetchAll();

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
      <div class="sidebar-avatar">
        <?= mb_strtoupper(mb_substr($aluno['nome'], 0, 1)) ?>
      </div>
      <div class="sidebar-name"><?= htmlspecialchars($aluno['nome']) ?></div>
      <div class="sidebar-role"><?= htmlspecialchars($aluno['curso'] ?: 'Estudante') ?></div>
      <div style="margin-top:8px;font-size:.75rem;color:var(--gray-400);"><?= htmlspecialchars($aluno['universidade'] ?: '') ?></div>
    </div>

    <nav class="sidebar-nav">
      <span class="sidebar-section">Principal</span>
      <a href="/teste/dashboard/aluno.php" class="active"><span class="icon"></span> Painel</a>
      <a href="/teste/vagas.php"><span class="icon"></span> Buscar Vagas</a>

      <span class="sidebar-section">Minha Conta</span>
      <a href="#candidaturas" onclick="document.getElementById('candidaturas').scrollIntoView({behavior:'smooth'});return false;"><span class="icon"></span> Candidaturas <span class="badge badge-purple" style="margin-left:auto"><?= $totalCand ?></span></a>
      <a href="#perfil" onclick="document.getElementById('perfil').scrollIntoView({behavior:'smooth'});return false;"><span class="icon"></span> Meu Perfil</a>

      <span class="sidebar-section">Outros</span>
      <a href="/teste/logout.php"><span class="icon"></span> Sair</a>
    </nav>
  </aside>

  <main class="dashboard-main">
    <?php if (isset($_GET['welcome'])): ?>
      <div class="alert alert-success">Conta criada com sucesso. Complete seu perfil para começar.</div>
    <?php endif; ?>
    <?php if ($successMsg): ?>
      <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>

    <div class="dashboard-header">
      <h1>Olá, <?= htmlspecialchars(explode(' ', $aluno['nome'])[0]) ?>! </h1>
      <p>Acompanhe suas candidaturas e descubra novas oportunidades</p>
    </div>

    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-top">
          <div><div class="kpi-num"><?= $totalCand ?></div><div class="kpi-label">Candidaturas</div></div>
          <div class="kpi-icon" style="background:#EDE9FE;font-size:1.4rem;"></div>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top">
          <div><div class="kpi-num"><?= $aprovadas ?></div><div class="kpi-label">Aprovadas</div></div>
          <div class="kpi-icon" style="background:#D1FAE5;font-size:1.4rem;"></div>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top">
          <div><div class="kpi-num"><?= $visualizadas ?></div><div class="kpi-label">Visualizadas</div></div>
          <div class="kpi-icon" style="background:#DBEAFE;font-size:1.4rem;"></div>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top">
          <div><div class="kpi-num"><?= $pendentes ?></div><div class="kpi-label">Pendentes</div></div>
          <div class="kpi-icon" style="background:#FEF3C7;font-size:1.4rem;">⏳</div>
        </div>
      </div>
    </div>

    <div class="card mb-6" id="candidaturas">
      <div class="card-header">
        <h3>Minhas Candidaturas</h3>
        <a href="/teste/vagas.php" class="btn btn-primary btn-sm">+ Nova candidatura</a>
      </div>
      <?php if (empty($candidaturas)): ?>
        <div class="card-body">
          <div class="empty-state">
            <div class="empty-icon"></div>
            <h3>Nenhuma candidatura ainda</h3>
            <p>Comece explorando as vagas disponíveis!</p>
            <a href="/teste/vagas.php" class="btn btn-primary" style="margin-top:16px;">Buscar vagas</a>
          </div>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Vaga</th>
                <th>Empresa</th>
                <th>Bolsa</th>
                <th>Modalidade</th>
                <th>Status</th>
                <th>Data</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($candidaturas as $c): ?>
              <tr>
                <td class="td-title"><?= htmlspecialchars($c['titulo']) ?></td>
                <td><?= htmlspecialchars($c['nome_empresa']) ?></td>
                <td style="color:var(--accent);font-weight:700;">R$ <?= number_format($c['bolsa'], 0, ',', '.') ?></td>
                <td>
                  <span class="badge <?= $c['modalidade'] === 'remoto' ? 'badge-green' : ($c['modalidade'] === 'hibrido' ? 'badge-blue' : 'badge-gray') ?>">
                    <?= $c['modalidade'] === 'remoto' ? '' : ($c['modalidade'] === 'hibrido' ? '' : '') ?> <?= ucfirst($c['modalidade']) ?>
                  </span>
                </td>
                <td><?= statusBadge($c['status']) ?></td>
                <td style="color:var(--gray-400);font-size:.8rem;"><?= date('d/m/Y', strtotime($c['criado_em'])) ?></td>
                <td><a href="/teste/vaga.php?id=<?= $c['vaga_id'] ?>" class="btn btn-ghost btn-sm">Ver</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="card mb-6" id="favoritas">
      <div class="card-header">
        <h3>Vagas Salvas</h3>
        <span class="text-muted text-sm"><?= count($favoritas) ?> salva<?= count($favoritas) === 1 ? '' : 's' ?></span>
      </div>
      <?php if (empty($favoritas)): ?>
        <div class="card-body">
          <p class="text-muted text-sm">Você ainda não salvou nenhuma vaga. Clique em "Salvar" em qualquer vaga para guardá-la aqui.</p>
        </div>
      <?php else: ?>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <?php foreach ($favoritas as $v): ?>
            <div style="padding:14px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);">
              <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                <div style="flex:1;">
                  <div style="font-weight:700;color:var(--gray-900);font-size:.92rem;"><?= htmlspecialchars($v['titulo']) ?></div>
                  <div style="font-size:.8rem;color:var(--gray-500);margin:2px 0 8px;"><?= htmlspecialchars($v['nome_empresa']) ?> · <?= htmlspecialchars($v['cidade']) ?>/<?= $v['estado'] ?></div>
                </div>
                <form method="POST" style="margin:0;">
                  <input type="hidden" name="vaga_id" value="<?= (int)$v['id'] ?>">
                  <button type="submit" name="remover_favorito" value="1" class="btn btn-ghost btn-sm" title="Remover dos favoritos" style="padding:4px 8px;">×</button>
                </form>
              </div>
              <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-weight:700;color:var(--accent);font-size:.92rem;">R$ <?= number_format($v['bolsa'], 0, ',', '.') ?></span>
                <a href="/teste/vaga.php?id=<?= (int)$v['id'] ?>" class="btn btn-primary btn-sm">Ver vaga</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="card mb-6">
      <div class="card-header">
        <h3>Vagas Recomendadas</h3>
        <a href="/teste/vagas.php" class="btn btn-ghost btn-sm">Ver todas →</a>
      </div>
      <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <?php foreach ($vagasRec as $v): ?>
        <a href="/teste/vaga.php?id=<?= $v['id'] ?>" style="text-decoration:none;">
          <div style="padding:14px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);transition:all .2s;" onmouseover="this.style.borderColor='var(--primary)';this.style.background='#FAFAF';" onmouseout="this.style.borderColor='var(--gray-200)';this.style.background='';">
            <div style="font-weight:700;color:var(--gray-900);margin-bottom:2px;font-size:.9rem;"><?= htmlspecialchars($v['titulo']) ?></div>
            <div style="font-size:.8rem;color:var(--gray-500);margin-bottom:8px;"><?= htmlspecialchars($v['nome_empresa']) ?> · <?= $v['cidade'] ?>/<?= $v['estado'] ?></div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <span style="font-weight:700;color:var(--accent);">R$ <?= number_format($v['bolsa'], 0, ',', '.') ?></span>
              <span class="badge badge-purple" style="font-size:.72rem;"><?= htmlspecialchars($v['area']) ?></span>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card" id="perfil">
      <div class="card-header">
        <h3>Meu Perfil</h3>
        <span class="badge badge-<?= $aluno['curso'] && $aluno['universidade'] ? 'green' : 'yellow' ?>">
          <?= $aluno['curso'] && $aluno['universidade'] ? 'Completo' : 'Incompleto' ?>
        </span>
      </div>
      <div class="card-body">
        <?php if ($erroMsg): ?>
          <div class="alert alert-error"><?= htmlspecialchars($erroMsg) ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nome completo</label>
              <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($aluno['nome']) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">E-mail</label>
              <input type="email" class="form-control" value="<?= htmlspecialchars($aluno['email']) ?>" disabled style="background:var(--gray-100);">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Curso</label>
              <input type="text" name="curso" class="form-control" placeholder="Ciência da Computação" value="<?= htmlspecialchars($aluno['curso'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Universidade</label>
              <input type="text" name="universidade" class="form-control" placeholder="USP, UNICAMP..." value="<?= htmlspecialchars($aluno['universidade'] ?? '') ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Semestre atual</label>
              <select name="semestre" class="form-control">
                <?php for ($s = 1; $s <= 12; $s++): ?>
                  <option value="<?= $s ?>" <?= ($aluno['semestre'] ?? 1) == $s ? 'selected' : '' ?>><?= $s ?>º semestre</option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Cidade</label>
              <input type="text" name="cidade" class="form-control" placeholder="São Paulo" value="<?= htmlspecialchars($aluno['cidade'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Sobre mim</label>
            <textarea name="sobre" class="form-control" rows="4" placeholder="Apresente-se brevemente: habilidades, experiências, objetivos..."><?= htmlspecialchars($aluno['sobre'] ?? '') ?></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">LinkedIn</label>
              <input type="url" name="linkedin" class="form-control" placeholder="https://linkedin.com/in/seu-perfil" value="<?= htmlspecialchars($aluno['linkedin'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">GitHub</label>
              <input type="url" name="github" class="form-control" placeholder="https://github.com/seu-user" value="<?= htmlspecialchars($aluno['github'] ?? '') ?>">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Currículo (PDF, máx. 3MB)</label>
            <?php if (!empty($aluno['curriculo_path'])): ?>
              <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                <a href="/teste/<?= htmlspecialchars($aluno['curriculo_path']) ?>" target="_blank" class="btn btn-ghost btn-sm">Baixar currículo atual</a>
                <button type="submit" name="remover_curriculo" value="1" class="btn btn-ghost btn-sm" style="color:var(--danger);" onclick="return confirm('Remover o currículo atual?')">Remover</button>
              </div>
            <?php endif; ?>
            <input type="file" name="curriculo" class="form-control" accept="application/pdf">
            <div class="form-hint">Enviar um novo PDF substitui o anterior.</div>
          </div>

          <button type="submit" name="update_profile" class="btn btn-primary">Salvar perfil</button>
        </form>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
