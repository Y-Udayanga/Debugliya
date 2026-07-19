<?php
function app_base64url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function app_base64url_decode(string $value): string|false
{
    $padding = strlen($value) % 4;
    if ($padding) {
        $value .= str_repeat('=', 4 - $padding);
    }

    return base64_decode(strtr($value, '-_', '+/'), true);
}

function app_session_secret(): string
{
    $secret = getenv('APP_SESSION_SECRET') ?: getenv('DATABASE_URL') ?: getenv('SUPABASE_DB_PASSWORD') ?: __DIR__;
    return hash('sha256', $secret);
}

function app_sign_payload(array $payload): string
{
    $body = app_base64url_encode(json_encode($payload));
    $signature = hash_hmac('sha256', $body, app_session_secret());
    return $body . '.' . $signature;
}

function app_read_signed_cookie(string $name): ?array
{
    if (empty($_COOKIE[$name]) || !str_contains($_COOKIE[$name], '.')) {
        return null;
    }

    [$body, $signature] = explode('.', $_COOKIE[$name], 2);
    $expected = hash_hmac('sha256', $body, app_session_secret());
    if (!hash_equals($expected, $signature)) {
        return null;
    }

    $json = app_base64url_decode($body);
    if ($json === false) {
        return null;
    }

    $payload = json_decode($json, true);
    if (!is_array($payload) || ($payload['exp'] ?? 0) < time()) {
        return null;
    }

    return $payload;
}

function app_cookie_options(bool $httpOnly = true): array
{
    return [
        'expires' => time() + 60 * 60 * 24 * 30,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || getenv('VERCEL'),
        'httponly' => $httpOnly,
        'samesite' => 'Lax',
    ];
}

function app_set_signed_cookie(string $name, array $payload, bool $httpOnly = true): void
{
    $payload['exp'] = time() + 60 * 60 * 24 * 30;
    setcookie($name, app_sign_payload($payload), app_cookie_options($httpOnly));
}

function app_clear_cookie(string $name): void
{
    $options = app_cookie_options();
    $options['expires'] = time() - 3600;
    setcookie($name, '', $options);
    unset($_COOKIE[$name]);
}

function app_session_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $auth = app_read_signed_cookie('debuglia_auth');
    if ($auth && empty($_SESSION['user_id'])) {
        $_SESSION['user_id'] = $auth['user_id'] ?? null;
        $_SESSION['username'] = $auth['username'] ?? '';
        $_SESSION['profile_photo'] = $auth['profile_photo'] ?? null;
    }

    $csrf = app_read_signed_cookie('debuglia_csrf');
    if ($csrf && empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = $csrf['csrf_token'] ?? null;
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        app_set_signed_cookie('debuglia_csrf', ['csrf_token' => $_SESSION['csrf_token']]);
    }
}

function app_persist_login(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['profile_photo'] = $user['profile_photo'] ?? null;

    app_set_signed_cookie('debuglia_auth', [
        'user_id' => $user['id'],
        'username' => $user['username'],
        'profile_photo' => $user['profile_photo'] ?? null,
    ]);
    app_set_signed_cookie('debuglia_csrf', ['csrf_token' => $_SESSION['csrf_token']]);
}

function app_clear_login(): void
{
    app_clear_cookie('debuglia_auth');
    app_clear_cookie('debuglia_csrf');
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}
?>
