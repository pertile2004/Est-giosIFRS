<?php
$pageTitle = 'Publicar Vaga';
require_once __DIR__ . '/../includes/auth.php';
requireEmpresa();

$db = getDB();
$empresaId = $_SESSION['perfil_id'];

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo     = trim($_POST['titulo'] ?? '');
    $descricao  = trim($_POST['descricao'] ?? '');
    $area       = trim($_POST['area'] ?? '');
    $cidade     = trim($_POST['cidade'] ?? '');
    $estado     = trim($_POST['estado'] ?? '');
    $modalidade = $_POST['modalidade'] ?? 'presencial';
    $bolsa      = (float)($_POST['bolsa'] ?? 0);
    $carga      = (int)($_POST['carga_horaria'] ?? 30);

    if (!$titulo || !$descricao || !$cidade || !$estado) {
        $erro = 'Preencha os campos obrigatórios.';
    } else {
        $stmt = $db->prepare("INSERT INTO vagas (empresa_id,titulo,descricao,area,cidade,estado,modalidade,bolsa,carga_horaria) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$empresaId,$titulo,$descricao,$area,$cidade,$estado,$modalidade,$bolsa,$carga]);
        header('Location: /teste/dashboard/empresa.php');
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <h1>Publicar nova vaga</h1>

  <div class="card" style="margin-top:24px;">
    <?php if ($erro): ?><p style="color:#900;margin-bottom:12px;"><?= $erro ?></p><?php endif; ?>
    <form method="POST">
      <p><label>Título *</label><br><input style="width:100%;padding:8px;" type="text" name="titulo" required></p>
      <p style="margin-top:12px;"><label>Descrição *</label><br><textarea style="width:100%;padding:8px;" name="descricao" rows="5" required></textarea></p>
      <p style="margin-top:12px;"><label>Área</label><br><input style="width:100%;padding:8px;" type="text" name="area"></p>
      <p style="margin-top:12px;display:flex;gap:12px;">
        <span style="flex:2;"><label>Cidade *</label><br><input style="width:100%;padding:8px;" type="text" name="cidade" required></span>
        <span style="flex:1;"><label>Estado *</label><br><input style="width:100%;padding:8px;" type="text" name="estado" maxlength="2" required></span>
      </p>
      <p style="margin-top:12px;display:flex;gap:12px;">
        <span style="flex:1;"><label>Modalidade</label><br>
          <select style="width:100%;padding:8px;" name="modalidade">
            <option value="presencial">Presencial</option>
            <option value="remoto">Remoto</option>
            <option value="hibrido">Híbrido</option>
          </select>
        </span>
        <span style="flex:1;"><label>Bolsa (R$)</label><br><input style="width:100%;padding:8px;" type="number" name="bolsa" step="50"></span>
        <span style="flex:1;"><label>Carga horária</label><br><input style="width:100%;padding:8px;" type="number" name="carga_horaria" value="30"></span>
      </p>
      <button class="btn" type="submit" style="margin-top:16px;">Publicar</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
