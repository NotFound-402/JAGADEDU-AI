
<?php

require_once __DIR__ . '/../config/security.php';

jagatedu_start_session();
jagatedu_require_csrf();

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respond(401, [
        'success' => false,
        'message' => 'Silakan login terlebih dahulu.'
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Method tidak diizinkan.'
    ]);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    respond(400, [
        'success' => false,
        'message' => 'Data request tidak valid.'
    ]);
}

$itemId = filter_var($input['item_id'] ?? null, FILTER_VALIDATE_INT);
$status = $input['status'] ?? '';

$allowedStatuses = ['pending', 'in_progress', 'completed'];

if (!$itemId || !in_array($status, $allowedStatuses, true)) {
    respond(422, [
        'success' => false,
        'message' => 'ID aktivitas atau status tidak valid.'
    ]);
}

try {
    // Pastikan aktivitas milik roadmap user yang sedang login.
    $stmt = $pdo->prepare("
        SELECT ri.id
        FROM roadmap_items ri
        INNER JOIN roadmaps r ON r.id = ri.roadmap_id
        WHERE ri.id = ?
          AND r.user_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $itemId,
        $_SESSION['user_id']
    ]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        respond(404, [
            'success' => false,
            'message' => 'Aktivitas tidak ditemukan.'
        ]);
    }

    $completedAt = $status === 'completed'
        ? date('Y-m-d H:i:s')
        : null;

    $stmt = $pdo->prepare("
        UPDATE roadmap_items
        SET status = ?,
            completed_at = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $status,
        $completedAt,
        $itemId
    ]);

    respond(200, [
        'success' => true,
        'message' => 'Progres belajar berhasil diperbarui.',
        'data' => [
            'item_id' => $itemId,
            'status' => $status,
            'completed_at' => $completedAt
        ]
    ]);

} catch (Throwable $e) {
    error_log('Update roadmap progress error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Gagal memperbarui progres belajar.'
    ]);
}
