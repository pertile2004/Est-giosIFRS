<?php
/**
 * Configuracao OAuth (Google + GitHub).
 *
 * Para ATIVAR o login social, preencha as variaveis abaixo com credenciais reais
 * obtidas em:
 *   - Google: https://console.cloud.google.com/apis/credentials
 *   - GitHub: https://github.com/settings/developers
 *
 * SUGESTAO DE SEGURANCA: nao commite credenciais reais no git.
 * Crie um arquivo config/oauth.local.php (ja ignorado pelo .gitignore) com os
 * mesmos defines e ele tera prioridade sobre este.
 */

if (file_exists(__DIR__ . '/oauth.local.php')) {
    require_once __DIR__ . '/oauth.local.php';
} else {
    // Placeholders (vazios = OAuth desabilitado).
    define('GOOGLE_CLIENT_ID',     '');
    define('GOOGLE_CLIENT_SECRET', '');
    define('GOOGLE_REDIRECT_URI',  'http://localhost/teste/auth/google.php?callback=1');

    define('GITHUB_CLIENT_ID',     '');
    define('GITHUB_CLIENT_SECRET', '');
    define('GITHUB_REDIRECT_URI',  'http://localhost/teste/auth/github.php?callback=1');
}

function oauthConfigurado($provider) {
    if ($provider === 'google') return GOOGLE_CLIENT_ID !== '' && GOOGLE_CLIENT_SECRET !== '';
    if ($provider === 'github') return GITHUB_CLIENT_ID !== '' && GITHUB_CLIENT_SECRET !== '';
    return false;
}
