
<?php

require_once __DIR__ . '/config/security.php';

jagatedu_start_session();

// Hapus seluruh data session
$_SESSION = [];

// Ambil konfigurasi cookie session
$params = session_get_cookie_params();

// Hapus cookie session yang benar
if (ini_get('session.use_cookies')) {
    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]
    );
}

// Hancurkan session
session_destroy();

// Kembali ke halaman login
header('Location: login.php');
exit;
