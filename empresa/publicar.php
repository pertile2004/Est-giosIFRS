<?php
require_once __DIR__ . '/../includes/auth.php';
requireEmpresa();

$db = getDB();
$empresaId = $_SESSION['perfil_id'];

// Modo edicao: ?id=N (vaga existente da propria empresa)
$vagaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$vagaExistente = null;
if ($vagaId) {
    $stmt = $db->prepare("SELECT * FROM vagas WHERE id = ? AND empresa_id = ?");
    $stmt->execute([$vagaId, $empresaId]);
    $vagaExistente = $stmt->fetch();
    if (!$vagaExistente) {
        header('Location: /teste/dashboard/empresa.php');
        exit;
    }
}
$ehEdicao = (bool)$vagaExistente;
$pageTitle = ($ehEdicao ? 'Editar' : 'Publicar') . ' Vaga — InternSHIP Conect';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $requisitos = trim($_POST['requisitos'] ?? '');
    $beneficios = trim($_POST['beneficios'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $modalidade = $_POST['modalidade'] ?? 'presencial';
    $bolsa = (float)str_replace(',', '.', str_replace('.', '', $_POST['bolsa'] ?? '0'));
    $carga = (int)($_POST['carga_horaria'] ?? 30);

    if (!$titulo || !$descricao || !$area || !$cidade || !$estado) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif ($ehEdicao) {
        $stmt = $db->prepare("
            UPDATE vagas
               SET titulo=?, descricao=?, requisitos=?, beneficios=?, area=?,
                   cidade=?, estado=?, modalidade=?, bolsa=?, carga_horaria=?
             WHERE id=? AND empresa_id=?
        ");
        $stmt->execute([$titulo, $descricao, $requisitos, $beneficios, $area, $cidade, $estado, $modalidade, $bolsa, $carga, $vagaId, $empresaId]);
        header("Location: /teste/vaga.php?id=$vagaId&updated=1");
        exit;
    } else {
        $stmt = $db->prepare("
            INSERT INTO vagas (empresa_id, titulo, descricao, requisitos, beneficios, area, cidade, estado, modalidade, bolsa, carga_horaria)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$empresaId, $titulo, $descricao, $requisitos, $beneficios, $area, $cidade, $estado, $modalidade, $bolsa, $carga]);
        $vagaId = $db->lastInsertId();
        header("Location: /teste/vaga.php?id=$vagaId&published=1");
        exit;
    }
}

// Helper para pegar o valor: do POST se for re-render por erro, do banco se for edicao, vazio se for novo.
$val = function($campo, $padrao = '') use ($vagaExistente) {
    if (isset($_POST[$campo])) return $_POST[$campo];
    if ($vagaExistente && isset($vagaExistente[$campo])) return $vagaExistente[$campo];
    return $padrao;
};

include __DIR__ . '/../includes/header.php';
?>

<div style="background:linear-gradient(135deg,#0F172A,#1E1B4B);padding:40px 24px;">
  <div class="container">
    <a href="/teste/dashboard/empresa.php" style="color:rgba(255,255,255,.5);font-size:.85rem;display:inline-flex;align-items:center;gap:6px;margin-bottom:16px;">
      ← Voltar ao painel
    </a>
    <h1 style="color:#fff;font-size:1.8rem;"><?= $ehEdicao ? 'Editar vaga' : 'Publicar nova vaga' ?></h1>
    <p style="color:rgba(255,255,255,.6);margin-top:4px;">
      <?= $ehEdicao ? 'Atualize os dados desta vaga' : 'Encontre o estagiário ideal para sua equipe' ?>
    </p>
  </div>
</div>

<div class="publish-wrap">
  <?php if ($erro): ?>
    <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="card mb-6">
      <div class="card-header"><h3>Informações da Vaga</h3></div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">Título da vaga *</label>
          <input type="text" name="titulo" class="form-control" placeholder="Ex: Estágio em Desenvolvimento Web" required value="<?= htmlspecialchars($val('titulo')) ?>">
          <div class="form-hint">Seja específico e claro. Um bom título atrai mais candidatos qualificados.</div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Área *</label>
            <select name="area" class="form-control" required>
              <option value="">Selecione a área...</option>
              <?php foreach (['Desenvolvimento Web','Desenvolvimento Mobile','Data Science','Machine Learning','UX/UI Design','Marketing Digital','Produto','Administração','Finanças','RH','Jurídico','Comunicação','Engenharia','Outro'] as $a): ?>
                <option value="<?= $a ?>" <?= $val('area') === $a ? 'selected' : '' ?>><?= $a ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Modalidade *</label>
            <select name="modalidade" class="form-control" required>
              <?php $mAtual = $val('modalidade', 'presencial'); ?>
              <option value="presencial" <?= $mAtual === 'presencial' ? 'selected' : '' ?>>Presencial</option>
              <option value="remoto"     <?= $mAtual === 'remoto'     ? 'selected' : '' ?>>Remoto</option>
              <option value="hibrido"    <?= $mAtual === 'hibrido'    ? 'selected' : '' ?>>Híbrido</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Cidade *</label>
            <select name="cidade" class="form-control" required>
              <option value="">Selecione a cidade...</option>
              <optgroup label="Destaque">
                <?php foreach (['Bento Gonçalves','Carlos Barbosa','Garibaldi'] as $c): ?>
                  <option value="<?= htmlspecialchars($c) ?>" <?= $val('cidade') === $c ? 'selected' : '' ?> style="color:#7C3AED;font-weight:600;"><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
              </optgroup>
              <optgroup label="Outras cidades">
                <?php foreach (['Alvorada','Antônio Prado','Canoas','Caxias do Sul','Erechim','Farroupilha','Feliz','Flores da Cunha','Ibirubá','Nova Petrópolis','Osório','Porto Alegre','Rio Grande','Rolante','Sertão','Vacaria','Veranópolis','Viamão'] as $c): ?>
                  <option value="<?= htmlspecialchars($c) ?>" <?= $val('cidade') === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
              </optgroup>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Estado *</label>
            <input type="text" name="estado" class="form-control" value="RS" readonly style="background:var(--gray-100);cursor:not-allowed;">
            <div class="form-hint">Plataforma focada no Rio Grande do Sul.</div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Bolsa mensal (R$)</label>
            <input type="number" name="bolsa" class="form-control" placeholder="1500" min="0" step="50" value="<?= htmlspecialchars($val('bolsa')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Carga horária semanal</label>
            <select name="carga_horaria" class="form-control">
              <?php $cAtual = (int)$val('carga_horaria', 30); ?>
              <option value="20" <?= $cAtual === 20 ? 'selected' : '' ?>>20h por semana</option>
              <option value="25" <?= $cAtual === 25 ? 'selected' : '' ?>>25h por semana</option>
              <option value="30" <?= $cAtual === 30 ? 'selected' : '' ?>>30h por semana</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-6">
      <div class="card-header"><h3>Descrição e Detalhes</h3></div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">Descrição da vaga *</label>
          <textarea name="descricao" class="form-control" rows="6" required placeholder="Descreva as responsabilidades, atividades e o que o estagiário vai aprender e fazer no dia a dia..."><?= htmlspecialchars($val('descricao')) ?></textarea>
          <div class="form-hint">Seja detalhado! Candidatos tomam decisões baseados nessa descrição.</div>
        </div>

        <div class="form-group">
          <label class="form-label">Requisitos</label>
          <textarea name="requisitos" class="form-control" rows="4" placeholder="Um requisito por linha:
Cursando Ciência da Computação ou áreas relacionadas
Conhecimento básico em Python
Inglês intermediário"><?= htmlspecialchars($val('requisitos')) ?></textarea>
          <div class="form-hint">Digite um requisito por linha.</div>
        </div>

        <div class="form-group">
          <label class="form-label">Benefícios</label>
          <textarea name="beneficios" class="form-control" rows="3" placeholder="Vale-refeição R$ 35/dia, Vale-transporte, Plano de saúde, Seguro de vida"><?= htmlspecialchars($val('beneficios')) ?></textarea>
          <div class="form-hint">Separe os benefícios por vírgula.</div>
        </div>
      </div>
    </div>

    <div style="display:flex;gap:12px;justify-content:flex-end;">
      <a href="/teste/dashboard/empresa.php" class="btn btn-ghost btn-lg">Cancelar</a>
      <button type="submit" class="btn btn-primary btn-lg">
        <?= $ehEdicao ? 'Salvar alterações' : 'Publicar vaga' ?>
      </button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
