<?php

function jagatedu_is_secure_request(): bool
{
    return isset($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== ''
        && $_SERVER['HTTPS'] !== 'off';
}

function jagatedu_start_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return;
    }

    $isSecure = jagatedu_is_secure_request();

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', $isSecure ? '1' : '0');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name('JAGATEDU_SESSID');
    session_start();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function jagatedu_csrf_token(): string
{
    jagatedu_start_session();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function jagatedu_require_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_SERVER['HTTP_X_XSRF_TOKEN'] ?? null));

    if (!is_string($token) || trim($token) === '') {
        $rawBody = file_get_contents('php://input');

        if (is_string($rawBody) && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);

            if (is_array($decoded) && isset($decoded['csrf_token'])) {
                $token = $decoded['csrf_token'];
            }
        }
    }

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', trim($token))) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $newToken = $_SESSION['csrf_token'];

        http_response_code(419);
        header('Content-Type: application/json; charset=utf-8');
        header('X-CSRF-Token: ' . $newToken);

        echo json_encode([
            'success' => false,
            'message' => 'Token CSRF tidak valid atau kadaluarsa. Silakan coba lagi.',
            'csrf_token' => $newToken
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}
