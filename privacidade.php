<?php
$pageTitle = 'InternSHIP Conect — Política de Privacidade';
require_once __DIR__ . '/includes/auth.php';
include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:780px;padding:48px 24px 80px;">
  <h1 style="margin-bottom:8px;">Política de Privacidade</h1>
  <p class="text-muted" style="margin-bottom:32px;">Última atualização: junho de 2026</p>

  <section style="margin-bottom:28px;">
    <h2 style="font-size:1.2rem;margin-bottom:10px;">1. Dados que coletamos</h2>
    <p style="color:var(--gray-600);">
      Coletamos apenas os dados necessários para o funcionamento da plataforma:
      nome, e-mail, senha (armazenada em hash), curso, universidade, semestre,
      cidade e estado para alunos; e nome da empresa, CNPJ, setor, descrição,
      site e localização para empresas. Para vagas, registramos as informações
      publicadas e os candidatos que se inscreverem.
    </p>
  </section>

  <section style="margin-bottom:28px;">
    <h2 style="font-size:1.2rem;margin-bottom:10px;">2. Como usamos seus dados</h2>
    <p style="color:var(--gray-600);">
      Os dados são utilizados exclusivamente para conectar estudantes e empresas
      em processos seletivos de estágio, manter sua sessão ativa, exibir vagas
      compatíveis com seu perfil e permitir que empresas avaliem candidaturas.
      Não vendemos nem cedemos dados pessoais a terceiros.
    </p>
  </section>

  <section style="margin-bottom:28px;">
    <h2 style="font-size:1.2rem;margin-bottom:10px;">3. Cookies e armazenamento local</h2>
    <p style="color:var(--gray-600);">
      Usamos cookies de sessão (PHP) para manter você autenticado e
      <em>localStorage</em> do navegador para guardar preferências como o tema
      claro/escuro. Esses dados ficam apenas no seu dispositivo e podem ser
      apagados a qualquer momento limpando os dados do site no navegador.
    </p>
  </section>

  <section style="margin-bottom:28px;">
    <h2 style="font-size:1.2rem;margin-bottom:10px;">4. Seus direitos (LGPD)</h2>
    <p style="color:var(--gray-600);">
      Conforme a Lei Geral de Proteção de Dados (Lei 13.709/2018), você pode
      solicitar a qualquer momento: acesso aos seus dados, correção, exclusão
      da conta, portabilidade e revogação do consentimento. Entre em contato
      pelo e-mail informado na página de Contato.
    </p>
  </section>

  <section style="margin-bottom:28px;">
    <h2 style="font-size:1.2rem;margin-bottom:10px;">5. Segurança</h2>
    <p style="color:var(--gray-600);">
      Senhas são armazenadas com hash bcrypt. Recomendamos que você não
      compartilhe sua senha e que use senhas únicas e fortes. Em caso de
      suspeita de acesso indevido, redefina sua senha imediatamente em
      "Esqueceu a senha?".
    </p>
  </section>

  <p style="color:var(--gray-500);font-size:.88rem;margin-top:32px;">
    Esta política pode ser atualizada. Mudanças importantes serão comunicadas pela plataforma.
  </p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
