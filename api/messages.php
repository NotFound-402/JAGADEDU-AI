
<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

function sendResponse($success, $data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'data' => $data
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    sendResponse(false, 'Silakan login terlebih dahulu.', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(false, 'Method tidak diizinkan.', 405);
}

$conversationId = filter_input(INPUT_GET, 'conversation_id', FILTER_VALIDATE_INT);

if (!$conversationId || $conversationId < 1) {
    sendResponse(false, 'ID percakapan tidak valid.', 400);
}

try {
    // Pastikan percakapan milik user yang sedang login
    $stmt = $pdo->prepare("
        SELECT id
        FROM chat_conversations
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$conversationId, $_SESSION['user_id']]);

    if (!$stmt->fetch()) {
        sendResponse(false, 'Percakapan tidak ditemukan.', 404);
    }

    // Ambil seluruh pesan percakapan
    $stmt = $pdo->prepare("
        SELECT sender, message, created_at
        FROM chat_messages
        WHERE conversation_id = ?
        ORDER BY id ASC
    ");

    $stmt->execute([$conversationId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(true, $messages);

} catch (Throwable $e) {
    error_log($e->getMessage());
    sendResponse(false, 'Terjadi kesalahan server.', 500);
}
