<?php

require_once __DIR__ . '/../config/security.php';

jagatedu_start_session();

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

$userId = (int) $_SESSION['user_id'];

try {
    // Ambil asesmen terakhir yang sudah selesai.
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
            'message' => 'Belum ada asesmen yang selesai.',
            'data' => [
                'session' => null,
                'average_score' => null,
                'scores' => [],
                'analysis' => null,
                'roadmap' => null,
                'roadmap_items' => []
            ]
        ]);
    }

    $sessionId = (int) $session['id'];

    // Ambil nilai kompetensi.
    $stmt = $pdo->prepare("
        SELECT
            c.id AS competency_id,
            c.name AS competency,
            s.multiple_choice_score,
            s.coding_score,
            s.final_score
        FROM assessment_scores s
        JOIN competencies c ON c.id = s.competency_id
        WHERE s.session_id = ?
        ORDER BY c.id ASC
    ");

    $stmt->execute([$sessionId]);
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $averageScore = null;

    if (count($scores) > 0) {
        $total = 0;

        foreach ($scores as $score) {
            $total += (float) $score['final_score'];
        }

        $averageScore = round($total / count($scores), 2);
    }

    // Ambil AI Analysis.
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
        $analysis['id'] = (int) $analysis['id'];
        $analysis['strengths'] = json_decode(
            $analysis['strengths'],
            true
        ) ?? [];

        $analysis['weaknesses'] = json_decode(
            $analysis['weaknesses'],
            true
        ) ?? [];

        $analysis['recommendations'] = json_decode(
            $analysis['recommendations'],
            true
        ) ?? [];
    }

    // Ambil roadmap yang berhubungan dengan analisis.
    $roadmap = null;
    $roadmapItems = [];

    if ($analysis) {
        $stmt = $pdo->prepare("
            SELECT
                id,
                title,
                description,
                duration_days,
                created_at
            FROM roadmaps
            WHERE user_id = ?
              AND analysis_id = ?
            LIMIT 1
        ");

        $stmt->execute([$userId, $analysis['id']]);
        $roadmap = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($roadmap) {
            $roadmap['id'] = (int) $roadmap['id'];
            $roadmap['duration_days'] = (int) $roadmap['duration_days'];

            $stmt = $pdo->prepare("
                SELECT
                    id,
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
            $roadmapItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($roadmapItems as &$item) {
                $item['id'] = (int) $item['id'];
                $item['day_number'] = (int) $item['day_number'];
            }

            unset($item);
        }
    }

    respond(200, [
        'success' => true,
        'message' => 'Data dashboard berhasil diambil.',
        'data' => [
            'session' => $session,
            'average_score' => $averageScore,
            'scores' => $scores,
            'analysis' => $analysis,
            'roadmap' => $roadmap,
            'roadmap_items' => $roadmapItems
        ]
    ]);
} catch (Throwable $e) {
    error_log('Get Dashboard Error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Gagal mengambil data dashboard.'
    ]);
}
