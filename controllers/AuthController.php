<?php

require_once __DIR__ . '/../config/security.php';

jagatedu_start_session();
jagatedu_require_csrf();

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method tidak diizinkan.'
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email dan password wajib diisi.'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, name, email, password, role
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Email atau password salah.'
        ]);
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];

    echo json_encode([
        'success' => true,
        'message' => 'Login berhasil!',
        'redirect' => 'dashboard.php'
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan pada server.'
    ]);
}