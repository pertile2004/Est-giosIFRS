<?php
/**
 * Handler de login OAuth com GitHub.
 *
 * Fluxo:
 *  1. /auth/github.php             -> redireciona para o GitHub
 *  2. /auth/github.php?callback=1  -> recebe codigo, troca por token, busca dados
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/oauth.php';

$pageTitle = 'Entrar com GitHub';

if (!oauthConfigurado('github')) {
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="container" style="max-width:600px;margin:80px auto;">
      <div class="card" style="padding:32px;">
        <h2 style="margin-bottom:12px;">Login com GitHub ainda não configurado</h2>
        <p style="color:var(--gray-600);margin-bottom:16px;">
          Para ativar este login, edite <code>config/oauth.php</code> (ou crie
          <code>config/oauth.local.php</code>) e preencha as constantes
          <code>GITHUB_CLIENT_ID</code> e <code>GITHUB_CLIENT_SECRET</code> obtidas em
          <a href="https://github.com/settings/developers" target="_blank">GitHub Developer Settings</a>.
        </p>
        <p style="color:var(--gray-600);margin-bottom:20px;">
          O <em>callback URL</em> autorizado deve ser:<br>
          <code><?= htmlspecialchars(GITHUB_REDIRECT_URI) ?></code>
        </p>
        <a href="/teste/login.php" class="btn btn-primary">Voltar para o login</a>
      </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

if (!isset($_GET['callback'])) {
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    $params = http_build_query([
        'client_id'    => GITHUB_CLIENT_ID,
        'redirect_uri' => GITHUB_REDIRECT_URI,
        'scope'        => 'read:user user:email',
        'state'        => $state,
    ]);
    header('Location: https://github.com/login/oauth/authorize?' . $params);
    exit;
}

if (!isset($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    die('Estado OAuth invalido (possivel CSRF).');
}
unset($_SESSION['oauth_state']);

$code = $_GET['code'] ?? '';
if (!$code) {
    header('Location: /teste/login.php');
    exit;
}

// Troca codigo por access_token
$tokenResp = file_get_contents('https://github.com/login/oauth/access_token', false, stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Accept: application/json\r\nContent-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query([
            'code'          => $code,
            'client_id'     => GITHUB_CLIENT_ID,
            'client_secret' => GITHUB_CLIENT_SECRET,
            'redirect_uri'  => GITHUB_REDIRECT_URI,
        ]),
    ],
]));
$token = json_decode($tokenResp, true);
if (empty($token['access_token'])) {
    die('Falha ao obter token do GitHub.');
}

// Busca usuario e e-mail (no GitHub o e-mail pode ser privado)
$ctx = stream_context_create([
    'http' => ['header' => "Authorization: Bearer {$token['access_token']}\r\nUser-Agent: InternSHIP-Conect\r\n"],
]);
$user = json_decode(file_get_contents('https://api.github.com/user', false, $ctx), true);
$emails = json_decode(file_get_contents('https://api.github.com/user/emails', false, $ctx), true);

$email = '';
foreach ((array)$emails as $e) {
    if (!empty($e['primary']) && !empty($e['verified'])) { $email = $e['email']; break; }
}
if (!$email) $email = $user['email'] ?? '';

if (empty($user['id']) || !$email) {
    die('Nao foi possivel ler perfil do GitHub.');
}

$db = getDB();
$stmt = $db->prepare("SELECT u.id FROM usuarios u JOIN oauth_accounts o ON o.usuario_id=u.id WHERE o.provider='github' AND o.provider_user_id=?");
$stmt->execute([(string)$user['id']]);
$userId = $stmt->fetchColumn();

if (!$userId) {
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $userId = $stmt->fetchColumn();

    if (!$userId) {
        $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO usuarios (nome,email,senha,tipo) VALUES (?,?,?, 'aluno')")
           ->execute([$user['name'] ?: ($user['login'] ?? $email), $email, $hash]);
        $userId = $db->lastInsertId();
        $db->prepare("INSERT INTO alunos (usuario_id) VALUES (?)")->execute([$userId]);
    }

    $db->prepare("INSERT INTO oauth_accounts (usuario_id, provider, provider_user_id, email) VALUES (?, 'github', ?, ?)")
       ->execute([$userId, (string)$user['id'], $email]);
}

loginPorId((int)$userId);
header('Location: /teste/dashboard/aluno.php');
exit;
