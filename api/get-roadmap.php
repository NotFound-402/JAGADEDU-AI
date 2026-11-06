
<?php

require_once __DIR__ . '/../config/security.php';
jagatedu_start_session();

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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(405, [
        'success' => false,
        'message' => 'Method tidak diizinkan.'
    ]);
}

try {
    // Ambil roadmap terbaru milik user yang sedang login.
    $stmt = $pdo->prepare("
        SELECT id, title, description, duration_days, created_at
        FROM roadmaps
        WHERE user_id = ?
        ORDER BY created_at DESC, id DESC
        LIMIT 1
    ");

    $stmt->execute([$_SESSION['user_id']]);
    $roadmap = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$roadmap) {
        respond(404, [
            'success' => false,
            'message' => 'Roadmap belum tersedia.'
        ]);
    }

    // Ambil seluruh aktivitas roadmap.
    $stmt = $pdo->prepare("
        SELECT
            id,
            roadmap_id,
            material_id,
            title,
            description,
            day_number,
            status,
            completed_at
        FROM roadmap_items
        WHERE roadmap_id = ?
        ORDER BY day_number ASC
    ");

    $stmt->execute([$roadmap['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = count($items);
    $completed = 0;
    $inProgress = 0;

    foreach ($items as $item) {
        if ($item['status'] === 'completed') {
            $completed++;
        } elseif ($item['status'] === 'in_progress') {
            $inProgress++;
        }
    }

    $progress = $total > 0
        ? round(($completed / $total) * 100, 2)
        : 0;

    respond(200, [
        'success' => true,
        'message' => 'Roadmap berhasil diambil.',
        'data' => [
            'roadmap' => $roadmap,
            'items' => $items,
            'progress' => [
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'pending' => $total - $completed - $inProgress,
                'percentage' => $progress
            ]
        ]
    ]);

} catch (Throwable $e) {
    error_log('Get roadmap error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Terjadi kesalahan saat mengambil roadmap.'
    ]);
}
