<?php

require_once __DIR__ . '/../config/security.php';

jagatedu_start_session();
jagatedu_require_csrf();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

function sendResponse($success, $message, $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode([
        'success' => $success,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (!isset($_SESSION['user_id'])) {
    sendResponse(false, 'Silakan login terlebih dahulu.', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Metode request tidak diizinkan.', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$id = filter_var(
    $data['conversation_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {
    sendResponse(false, 'ID percakapan tidak valid.', 400);
}

try {
    $stmt = $pdo->prepare("
        DELETE FROM chat_conversations
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([
        $id,
        $_SESSION['user_id']
    ]);

    if ($stmt->rowCount() === 0) {
        sendResponse(false, 'Percakapan tidak ditemukan.', 404);
    }

    sendResponse(true, 'Percakapan berhasil dihapus.');

} catch (Throwable $e) {
    error_log('Delete Conversation Error: ' . $e->getMessage());

    sendResponse(false, 'Gagal menghapus percakapan.', 500);
}