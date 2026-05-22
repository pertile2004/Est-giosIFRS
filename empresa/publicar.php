<?php
$pageTitle = 'Publicar Vaga — InternSHIP Conect';
require_once __DIR__ . '/../includes/auth.php';
requireEmpresa();

$db = getDB();
$empresaId = $_SESSION['perfil_id'];

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo       = trim($_POST['titulo'] ?? '');
    $descricao    = trim($_POST['descricao'] ?? '');
    $requisitos   = trim($_POST['requisitos'] ?? '');
    $beneficios   = trim($_POST['beneficios'] ?? '');
    $area         = trim($_POST['area'] ?? '');
    $cidade       = trim($_POST['cidade'] ?? '');
    $estado       = trim($_POST['estado'] ?? '');
    $modalidade   = $_POST['modalidade'] ?? 'presencial';
    $bolsa        = (float)str_replace(',', '.', str_replace('.', '', $_POST['bolsa'] ?? '0'));
    $carga        = (int)($_POST['carga_horaria'] ?? 30);

    if (!$titulo || !$descricao || !$area || !$cidade || !$estado) {
        $erro = 'Preencha todos os campos obrigatórios.';
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

include __DIR__ . '/../includes/header.php';
?>

<div style="background:linear-gradient(135deg,#0F172A,#1E1B4B);padding:40px 24px;">
  <div class="container">
    <a href="/teste/dashboard/empresa.php" style="color:rgba(255,255,255,.5);font-size:.85rem;display:inline-flex;align-items:center;gap:6px;margin-bottom:16px;">
      ← Voltar ao painel
    </a>
    <h1 style="color:#fff;font-size:1.8rem;">Publicar nova vaga ➕</h1>
    <p style="color:rgba(255,255,255,.6);margin-top:4px;">Encontre o estagiário ideal para sua equipe</p>
  </div>
</div>

<div class="publish-wrap">
  <?php if ($erro): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="card mb-6">
      <div class="card-header"><h3>📝 Informações da Vaga</h3></div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">Título da vaga *</label>
          <input type="text" name="titulo" class="form-control" placeholder="Ex: Estágio em Desenvolvimento Web" required value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>">
          <div class="form-hint">Seja específico e claro. Um bom título atrai mais candidatos qualificados.</div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Área *</label>
            <select name="area" class="form-control" required>
              <option value="">Selecione a área...</option>
              <?php foreach (['Desenvolvimento Web','Desenvolvimento Mobile','Data Science','Machine Learning','UX/UI Design','Marketing Digital','Produto','Administração','Finanças','RH','Jurídico','Comunicação','Engenharia','Outro'] as $a): ?>
                <option value="<?= $a ?>" <?= ($_POST['area'] ?? '') === $a ? 'selected' : '' ?>><?= $a ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Modalidade *</label>
            <select name="modalidade" class="form-control" required>
              <option value="presencial" <?= ($_POST['modalidade'] ?? '') === 'presencial' ? 'selected' : '' ?>>🏢 Presencial</option>
              <option value="remoto"     <?= ($_POST['modalidade'] ?? '') === 'remoto'     ? 'selected' : '' ?>>🌐 Remoto</option>
              <option value="hibrido"    <?= ($_POST['modalidade'] ?? '') === 'hibrido'    ? 'selected' : '' ?>>🔀 Híbrido</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Cidade *</label>
            <input type="text" name="cidade" class="form-control" placeholder="São Paulo" required value="<?= htmlspecialchars($_POST['cidade'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Estado *</label>
            <select name="estado" class="form-control" required>
              <option value="">Selecione...</option>
              <?php foreach (['SP','RJ','MG','RS','PR','SC','BA','CE','PE','GO','DF','AM','PA','ES','MT','MS','PB','RN','AL','PI','SE','TO','MA','RO','RR','AP','AC'] as $uf): ?>
                <option value="<?= $uf ?>" <?= ($_POST['estado'] ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Bolsa mensal (R$)</label>
            <input type="number" name="bolsa" class="form-control" placeholder="1500" min="0" step="50" value="<?= htmlspecialchars($_POST['bolsa'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Carga horária semanal</label>
            <select name="carga_horaria" class="form-control">
              <option value="20" <?= ($_POST['carga_horaria'] ?? '') == 20 ? 'selected' : '' ?>>20h por semana</option>
              <option value="25" <?= ($_POST['carga_horaria'] ?? '') == 25 ? 'selected' : '' ?>>25h por semana</option>
              <option value="30" <?= ($_POST['carga_horaria'] ?? '') == 30 ? 'selected' : '' ?>>30h por semana</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-6">
      <div class="card-header"><h3>📄 Descrição e Detalhes</h3></div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">Descrição da vaga *</label>
          <textarea name="descricao" class="form-control" rows="6" required placeholder="Descreva as responsabilidades, atividades e o que o estagiário vai aprender e fazer no dia a dia..."><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
          <div class="form-hint">Seja detalhado! Candidatos tomam decisões baseados nessa descrição.</div>
        </div>

        <div class="form-group">
          <label class="form-label">Requisitos</label>
          <textarea name="requisitos" class="form-control" rows="4" placeholder="Um requisito por linha:
Cursando Ciência da Computação ou áreas relacionadas
Conhecimento básico em Python
Inglês intermediário"><?= htmlspecialchars($_POST['requisitos'] ?? '') ?></textarea>
          <div class="form-hint">Digite um requisito por linha.</div>
        </div>

        <div class="form-group">
          <label class="form-label">Benefícios</label>
          <textarea name="beneficios" class="form-control" rows="3" placeholder="Vale-refeição R$ 35/dia, Vale-transporte, Plano de saúde, Seguro de vida"><?= htmlspecialchars($_POST['beneficios'] ?? '') ?></textarea>
          <div class="form-hint">Separe os benefícios por vírgula.</div>
        </div>
      </div>
    </div>

    <div style="display:flex;gap:12px;justify-content:flex-end;">
      <a href="/teste/dashboard/empresa.php" class="btn btn-ghost btn-lg">Cancelar</a>
      <button type="submit" class="btn btn-primary btn-lg">
        🚀 Publicar vaga
      </button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
