
<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respond(401, [
        'success' => false,
        'message' => 'Silakan login terlebih dahulu.'
    ]);
}

try {
    $userId = (int) $_SESSION['user_id'];

    // Ambil assessment terakhir yang sudah selesai.
    $stmt = $pdo->prepare("
        SELECT id, started_at, finished_at, status
        FROM assessment_sessions
        WHERE user_id = ?
          AND status = 'completed'
        ORDER BY finished_at DESC, id DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        respond(200, [
            'success' => true,
            'message' => 'Belum ada assessment yang selesai.',
            'data' => null
        ]);
    }

    $sessionId = (int) $session['id'];

    // Ambil nilai setiap kompetensi.
    $stmt = $pdo->prepare("
        SELECT
            c.id AS competency_id,
            c.name AS competency,
            sc.multiple_choice_score,
            sc.coding_score,
            sc.final_score
        FROM assessment_scores sc
        JOIN competencies c ON c.id = sc.competency_id
        WHERE sc.session_id = ?
        ORDER BY c.id ASC
    ");
    $stmt->execute([$sessionId]);
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Hitung rata-rata nilai.
    $averageScore = 0;

    if (count($scores) > 0) {
        $total = array_sum(array_map(
            fn($item) => (float) $item['final_score'],
            $scores
        ));

        $averageScore = round($total / count($scores), 2);
    }

    // Ambil feedback hasil evaluasi coding.
    $stmt = $pdo->prepare("
        SELECT
            q.id AS question_id,
            q.title,
            c.name AS competency,
            a.score,
            a.coding_feedback
        FROM answers a
        JOIN questions q ON q.id = a.question_id
        JOIN competencies c ON c.id = q.competency_id
        WHERE a.session_id = ?
          AND q.type = 'coding'
        ORDER BY q.id ASC
    ");
    $stmt->execute([$sessionId]);
    $codingFeedback = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil analisis AI jika sudah tersedia.
    $stmt = $pdo->prepare("
        SELECT
            id,
            summary,
            strengths,
            weaknesses,
            recommendations,
            created_at
        FROM ai_analyses
        WHERE session_id = ?
        LIMIT 1
    ");
    $stmt->execute([$sessionId]);
    $analysis = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($analysis) {
        foreach (['strengths', 'weaknesses', 'recommendations'] as $field) {
            $analysis[$field] = json_decode(
                $analysis[$field] ?? '[]',
                true
            ) ?? [];
        }
    }

    // Ambil roadmap milik user untuk analisis ini.
    $roadmap = null;

    if ($analysis) {
        $stmt = $pdo->prepare("
            SELECT id, title, description, duration_days
            FROM roadmaps
            WHERE user_id = ?
              AND analysis_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$userId, $analysis['id']]);
        $roadmap = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($roadmap) {
            $stmt = $pdo->prepare("
                SELECT
                    id,
                    material_id,
                    title,
                    description,
                    day_number,
                    status,
                    completed_at
                FROM roadmap_items
                WHERE roadmap_id = ?
                ORDER BY day_number ASC, id ASC
            ");
            $stmt->execute([$roadmap['id']]);
            $roadmap['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    respond(200, [
        'success' => true,
        'message' => 'Data dashboard berhasil diambil.',
        'data' => [
            'session' => $session,
            'average_score' => $averageScore,
            'scores' => $scores,
            'coding_feedback' => $codingFeedback,
            'analysis' => $analysis,
            'roadmap' => $roadmap
        ]
    ]);

} catch (Throwable $e) {
    error_log('Dashboard API error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Terjadi kesalahan saat mengambil data dashboard.'
    ]);
}
