
<?php

require_once __DIR__ . '/../config/security.php';

jagatedu_start_session();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

function sendResponse($success, $data, $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode([
        'success' => $success,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Wajib login
if (!isset($_SESSION['user_id'])) {
    sendResponse(false, 'Silakan login terlebih dahulu.', 401);
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {

    // Membuat percakapan baru
    if ($method === 'POST') {
        jagatedu_require_csrf();

        $stmt = $pdo->prepare("
            INSERT INTO chat_conversations (user_id, title)
            VALUES (?, ?)
        ");

        $stmt->execute([$userId, 'Percakapan baru']);

        $conversationId = $pdo->lastInsertId();

        sendResponse(true, [
            'conversation_id' => $conversationId,
            'title' => 'Percakapan baru'
        ], 201);
    }

    // Mengambil daftar percakapan

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("
        SELECT
            c.id,
            c.title,
            c.created_at,
            c.updated_at,
            (
                SELECT m.message
                FROM chat_messages m
                WHERE m.conversation_id = c.id
                ORDER BY m.id DESC
                LIMIT 1
            ) AS last_message
        FROM chat_conversations c
        WHERE c.user_id = ?
        ORDER BY c.updated_at DESC, c.id DESC
    ");

        $stmt->execute([$_SESSION['user_id']]);

        sendResponse(true, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }


    sendResponse(false, 'Metode request tidak diizinkan.', 405);
} catch (Throwable $e) {

    error_log('JagatEDU Conversation Error: ' . $e->getMessage());

    sendResponse(false, 'Terjadi kesalahan pada server.', 500);
}
