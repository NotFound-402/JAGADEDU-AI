
<?php

require_once __DIR__ . '/../config/security.php';

jagatedu_start_session();
jagatedu_require_csrf();

header('Content-Type: application/json');

require_once __DIR__ . '/../controllers/AssessmentController.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Silakan login terlebih dahulu.'
    ]);

    exit;
}

// Hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method tidak diizinkan.'
    ]);

    exit;
}

try {
    $controller = new AssessmentController($pdo);

    $sessionId = $controller->startAssessment(
        (int) $_SESSION['user_id']
    );

    echo json_encode([
        'success' => true,
        'message' => 'Assessment berhasil dimulai.',
        'session_id' => $sessionId
    ]);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Gagal memulai assessment.'
    ]);
}
