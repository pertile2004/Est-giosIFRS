<?php
/**
 * Handler de login OAuth com Google.
 *
 * Fluxo:
 *  1. /auth/google.php             -> redireciona para o Google
 *  2. /auth/google.php?callback=1  -> recebe codigo, troca por token, busca dados
 *
 * Sem credenciais configuradas, mostra uma mensagem orientando como ativar.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/oauth.php';

$pageTitle = 'Entrar com Google';

if (!oauthConfigurado('google')) {
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="container" style="max-width:600px;margin:80px auto;">
      <div class="card" style="padding:32px;">
        <h2 style="margin-bottom:12px;">Login com Google ainda não configurado</h2>
        <p style="color:var(--gray-600);margin-bottom:16px;">
          Para ativar este login, edite <code>config/oauth.php</code> (ou crie
          <code>config/oauth.local.php</code>) e preencha as constantes
          <code>GOOGLE_CLIENT_ID</code> e <code>GOOGLE_CLIENT_SECRET</code> obtidas em
          <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>.
        </p>
        <p style="color:var(--gray-600);margin-bottom:20px;">
          O <em>redirect URI</em> autorizado deve ser:<br>
          <code><?= htmlspecialchars(GOOGLE_REDIRECT_URI) ?></code>
        </p>
        <a href="/teste/login.php" class="btn btn-primary">Voltar para o login</a>
      </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

if (!isset($_GET['callback'])) {
    // 1. Redireciona para autorizacao do Google
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    $params = http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
    ]);
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    exit;
}

// 2. Callback: validar state, trocar codigo por token, buscar perfil
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
$tokenResp = file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query([
            'code'          => $code,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        ]),
    ],
]));
$token = json_decode($tokenResp, true);
if (empty($token['access_token'])) {
    die('Falha ao obter token do Google.');
}

// Busca dados do usuario
$userInfo = json_decode(file_get_contents('https://openidconnect.googleapis.com/v1/userinfo', false, stream_context_create([
    'http' => ['header' => "Authorization: Bearer {$token['access_token']}\r\n"],
])), true);

if (empty($userInfo['sub']) || empty($userInfo['email'])) {
    die('Nao foi possivel ler perfil do Google.');
}

// Encontra ou cria o usuario localmente
$db = getDB();
$stmt = $db->prepare("SELECT u.id FROM usuarios u JOIN oauth_accounts o ON o.usuario_id=u.id WHERE o.provider='google' AND o.provider_user_id=?");
$stmt->execute([$userInfo['sub']]);
$userId = $stmt->fetchColumn();

if (!$userId) {
    // Talvez ja exista um usuario com esse e-mail (cadastro local previo)
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$userInfo['email']]);
    $userId = $stmt->fetchColumn();

    if (!$userId) {
        // Cria usuario novo como aluno (por padrao)
        $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO usuarios (nome,email,senha,tipo) VALUES (?,?,?, 'aluno')")
           ->execute([$userInfo['name'] ?? $userInfo['email'], $userInfo['email'], $hash]);
        $userId = $db->lastInsertId();
        $db->prepare("INSERT INTO alunos (usuario_id) VALUES (?)")->execute([$userId]);
    }

    $db->prepare("INSERT INTO oauth_accounts (usuario_id, provider, provider_user_id, email) VALUES (?, 'google', ?, ?)")
       ->execute([$userId, $userInfo['sub'], $userInfo['email']]);
}

loginPorId((int)$userId);
header('Location: /teste/dashboard/aluno.php');
exit;
