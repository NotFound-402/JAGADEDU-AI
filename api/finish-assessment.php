
<?php
require_once __DIR__ . '/../config/security.php';

jagatedu_start_session();
jagatedu_require_csrf();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Silakan login terlebih dahulu.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method tidak diizinkan.'
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$sessionId = filter_var(
    $data['session_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$sessionId || $sessionId < 1) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Session ID tidak valid.'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Pastikan sesi milik user dan masih aktif
    $stmt = $pdo->prepare("
        SELECT id
        FROM assessment_sessions
        WHERE id = ?
          AND user_id = ?
          AND status = 'in_progress'
        FOR UPDATE
    ");

    $stmt->execute([
        $sessionId,
        $_SESSION['user_id']
    ]);

    if (!$stmt->fetch()) {
        $pdo->rollBack();

        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Sesi tidak ditemukan atau sudah selesai.'
        ]);
        exit;
    }

    // Periksa apakah seluruh soal sudah dijawab
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM questions
    ");
    $totalQuestions = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM answers
        WHERE session_id = ?
    ");
    $stmt->execute([$sessionId]);

    $answeredQuestions = (int) $stmt->fetchColumn();

    if ($answeredQuestions < $totalQuestions) {
        $pdo->rollBack();

        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Masih ada soal yang belum dijawab.',
            'total_questions' => $totalQuestions,
            'answered_questions' => $answeredQuestions
        ]);
        exit;
    }

    // Ambil nilai pilihan ganda per kompetensi
    $stmt = $pdo->prepare("
        SELECT
            q.competency_id,
            COALESCE(SUM(a.score), 0) AS mc_score
        FROM questions q
        LEFT JOIN answers a
            ON a.question_id = q.id
            AND a.session_id = ?
        WHERE q.type = 'multiple_choice'
        GROUP BY q.competency_id
    ");

    $stmt->execute([$sessionId]);
    $mcScores = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Coding belum dinilai otomatis.
    // Nilai coding sementara 0.
    $stmt = $pdo->query("
        SELECT id FROM competencies
    ");

    $competencies = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $insert = $pdo->prepare("
        INSERT INTO assessment_scores (
            session_id,
            competency_id,
            multiple_choice_score,
            coding_score,
            final_score
        )
        VALUES (?, ?, ?, 0, ?)
        ON DUPLICATE KEY UPDATE
            multiple_choice_score = VALUES(multiple_choice_score),
            coding_score = VALUES(coding_score),
            final_score = VALUES(final_score)
    ");

    foreach ($competencies as $competencyId) {
        $mcScore = (float) ($mcScores[$competencyId] ?? 0);

        // Sementara gunakan skor pilihan ganda.
        // Bobot final akan disesuaikan setelah penilaian coding dibuat.
        $finalScore = $mcScore;

        $insert->execute([
            $sessionId,
            $competencyId,
            $mcScore,
            $finalScore
        ]);
    }

    // Tandai assessment selesai
    $stmt = $pdo->prepare("
        UPDATE assessment_sessions
        SET status = 'completed',
            finished_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([$sessionId]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Assessment berhasil diselesaikan.',
        'total_questions' => $totalQuestions,
        'answered_questions' => $answeredQuestions
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('FINISH ASSESSMENT ERROR: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat menyelesaikan assessment.'
    ]);
}
