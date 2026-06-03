<?php
$pageTitle = 'InternSHIP Conect — Criar Conta';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . (isAluno() ? '/teste/dashboard/aluno.php' : '/teste/dashboard/empresa.php'));
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirma = $_POST['confirma'] ?? '';

    if ($senha !== $confirma) {
        $erro = 'As senhas não coincidem.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($tipo === 'aluno') {
        $curso = trim($_POST['curso'] ?? '');
        $universidade = trim($_POST['universidade'] ?? '');
        $semestre = (int)($_POST['semestre'] ?? 1);
        if (registrarAluno($nome, $email, $senha, $curso, $universidade, $semestre)) {
            if (login($email, $senha)) {
                header('Location: /teste/dashboard/aluno.php?welcome=1');
                exit;
            }
        } else {
            $erro = 'E-mail já cadastrado ou erro ao criar conta.';
        }
    } elseif ($tipo === 'empresa') {
        $nomeEmpresa = trim($_POST['nome_empresa'] ?? '');
        $setor = trim($_POST['setor'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $estado = trim($_POST['estado'] ?? '');
        if (registrarEmpresa($nome, $email, $senha, $nomeEmpresa, $setor, $cidade, $estado)) {
            if (login($email, $senha)) {
                header('Location: /teste/dashboard/empresa.php?welcome=1');
                exit;
            }
        } else {
            $erro = 'E-mail já cadastrado ou erro ao criar conta.';
        }
    } else {
        $erro = 'Selecione o tipo de conta.';
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
  <div class="auth-left">
    <div class="auth-left-content">
      <a href="/teste/" style="display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,.6);font-size:.9rem;margin-bottom:48px;text-decoration:none;">
        ← Voltar ao início
      </a>
      <h2>Comece sua jornada!</h2>
      <p>Cadastre-se gratuitamente e acesse oportunidades de estágio nas melhores empresas da região do IFRS.</p>
      <div class="auth-features">
        <div class="auth-feature">
          <div class="auth-feature-icon">🆓</div>
          <div class="auth-feature-text">
            <strong>Sempre gratuito</strong>
            <span>Sem mensalidade, sem taxa escondida</span>
          </div>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon">⚡</div>
          <div class="auth-feature-text">
            <strong>Cadastro em 2 minutos</strong>
            <span>Crie seu perfil e já comece a se candidatar</span>
          </div>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon">🔒</div>
          <div class="auth-feature-text">
            <strong>Dados protegidos</strong>
            <span>Seus dados são seguros e nunca compartilhados</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="auth-right" style="padding:32px 64px;overflow-y:auto;">
    <div class="auth-form-wrap">
      <div class="auth-logo" style="justify-content:center;margin-bottom:24px;">
        <img src="/teste/assets/img/logo.svg" alt="InternSHIP Connect" style="height:48px;width:auto;object-fit:contain;">
      </div>

      <h2 style="margin-bottom:6px;">Criar conta grátis</h2>
      <p class="text-muted text-sm" style="margin-bottom:24px;">Escolha o tipo de conta que melhor descreve você</p>

      <?php if ($erro): ?>
        <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>

      <form method="POST" id="register-form">
        <div class="tipo-select">
          <button type="button" class="tipo-btn <?= ($_POST['tipo'] ?? '') === 'aluno' ? 'selected' : '' ?>" data-tipo="aluno">
            <span class="tipo-icon"></span>
            <span class="tipo-label">Sou Estudante</span>
          </button>
          <button type="button" class="tipo-btn <?= ($_POST['tipo'] ?? '') === 'empresa' ? 'selected' : '' ?>" data-tipo="empresa">
            <span class="tipo-icon"></span>
            <span class="tipo-label">Sou Empresa</span>
          </button>
        </div>
        <input type="hidden" name="tipo" id="tipo-value" value="<?= htmlspecialchars($_POST['tipo'] ?? '') ?>">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nome completo</label>
            <input type="text" name="nome" class="form-control" placeholder="João Silva" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" placeholder="joao@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
        </div>

        <div id="campos-aluno" style="display:<?= ($_POST['tipo'] ?? '') === 'empresa' ? 'none' : 'block' ?>">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Curso</label>
              <input type="text" name="curso" class="form-control" placeholder="Ciência da Computação" value="<?= htmlspecialchars($_POST['curso'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Semestre</label>
              <select name="semestre" class="form-control">
                <?php for ($s = 1; $s <= 12; $s++): ?>
                  <option value="<?= $s ?>" <?= ($_POST['semestre'] ?? '') == $s ? 'selected' : '' ?>><?= $s ?>º semestre</option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Universidade</label>
            <input type="text" name="universidade" class="form-control" placeholder="USP, UNICAMP, UFMG..." value="<?= htmlspecialchars($_POST['universidade'] ?? '') ?>">
          </div>
        </div>

        <div id="campos-empresa" style="display:<?= ($_POST['tipo'] ?? '') === 'empresa' ? 'block' : 'none' ?>">
          <div class="form-group">
            <label class="form-label">Nome da Empresa</label>
            <input type="text" name="nome_empresa" class="form-control" placeholder="Tech Solutions Ltda" value="<?= htmlspecialchars($_POST['nome_empresa'] ?? '') ?>">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Setor</label>
              <select name="setor" class="form-control">
                <option value="">Selecione...</option>
                <option value="Tecnologia" <?= ($_POST['setor'] ?? '') === 'Tecnologia' ? 'selected' : '' ?>>Tecnologia</option>
                <option value="Fintech" <?= ($_POST['setor'] ?? '') === 'Fintech' ? 'selected' : '' ?>>Fintech</option>
                <option value="E-commerce" <?= ($_POST['setor'] ?? '') === 'E-commerce' ? 'selected' : '' ?>>E-commerce</option>
                <option value="Saúde" <?= ($_POST['setor'] ?? '') === 'Saúde' ? 'selected' : '' ?>>Saúde</option>
                <option value="Educação" <?= ($_POST['setor'] ?? '') === 'Educação' ? 'selected' : '' ?>>Educação</option>
                <option value="Consultoria" <?= ($_POST['setor'] ?? '') === 'Consultoria' ? 'selected' : '' ?>>Consultoria</option>
                <option value="Outro" <?= ($_POST['setor'] ?? '') === 'Outro' ? 'selected' : '' ?>>Outro</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Estado</label>
              <select name="estado" class="form-control">
                <option value="">UF</option>
                <?php foreach (['SP','RJ','MG','RS','PR','SC','BA','CE','PE','GO','DF','AM','PA','ES','MT','MS','PB','RN','AL','PI','SE','TO','MA','RO','RR','AP','AC'] as $uf): ?>
                  <option value="<?= $uf ?>" <?= ($_POST['estado'] ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Cidade</label>
            <input type="text" name="cidade" class="form-control" placeholder="São Paulo" value="<?= htmlspecialchars($_POST['cidade'] ?? '') ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Senha</label>
            <input type="password" name="senha" class="form-control" placeholder="Mín. 6 caracteres" required>
          </div>
          <div class="form-group">
            <label class="form-label">Confirmar senha</label>
            <input type="password" name="confirma" class="form-control" placeholder="Repita a senha" required>
          </div>
        </div>

        <label class="form-check" style="margin-bottom:20px;">
          <input type="checkbox" required>
          <span style="font-size:.85rem;color:var(--gray-500);">Concordo com os <a href="#">Termos de Uso</a> e <a href="#">Política de Privacidade</a></span>
        </label>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
          Criar conta grátis
        </button>
      </form>

      <p class="auth-link">
        Já tem conta? <a href="/teste/login.php">Entrar →</a>
      </p>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.tipo-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tipo-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('tipo-value').value = btn.dataset.tipo;
    if (btn.dataset.tipo === 'aluno') {
      document.getElementById('campos-aluno').style.display = 'block';
      document.getElementById('campos-empresa').style.display = 'none';
    } else {
      document.getElementById('campos-aluno').style.display = 'none';
      document.getElementById('campos-empresa').style.display = 'block';
    }
  });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
