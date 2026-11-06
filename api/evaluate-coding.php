
<?php

require_once __DIR__ . '/../config/security.php';

jagatedu_start_session();
jagatedu_require_csrf();

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/CodingEvaluationService.php';

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Method tidak diizinkan.'
    ]);
}

$input = json_decode(file_get_contents('php://input'), true);

$sessionId = filter_var(
    $input['session_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$sessionId || $sessionId < 1) {
    respond(400, [
        'success' => false,
        'message' => 'Session ID tidak valid.'
    ]);
}

try {
    // Verifikasi assessment milik siswa.
    $stmt = $pdo->prepare("
        SELECT id
        FROM assessment_sessions
        WHERE id = ?
          AND user_id = ?
          AND status = 'completed'
        LIMIT 1
    ");

    $stmt->execute([
        $sessionId,
        $_SESSION['user_id']
    ]);

    if (!$stmt->fetch()) {
        respond(403, [
            'success' => false,
            'message' => 'Assessment tidak ditemukan atau belum selesai.'
        ]);
    }

    // Ambil seluruh jawaban coding.
    $stmt = $pdo->prepare("
        SELECT
            a.id AS answer_id,
            a.code_submission,
            a.coding_evaluated_at,
            q.id AS question_id,
            q.competency_id,
            q.title,
            q.question_text,
            q.programming_language,
            q.points
        FROM answers a
        JOIN questions q ON q.id = a.question_id
        WHERE a.session_id = ?
          AND q.type = 'coding'
        ORDER BY q.id ASC
    ");

    $stmt->execute([$sessionId]);
    $answers = $stmt->fetchAll();

    // Jumlah soal diambil langsung dari database.
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM questions
        WHERE type = 'coding'
    ");

    $totalCodingQuestions = (int) $stmt->fetchColumn();

    if (
        $totalCodingQuestions === 0 ||
        count($answers) !== $totalCodingQuestions
    ) {
        respond(400, [
            'success' => false,
            'message' => 'Jawaban coding belum lengkap.'
        ]);
    }

    // Cegah pemanggilan Gemini berulang.
    foreach ($answers as $answer) {
        if ($answer['coding_evaluated_at'] !== null) {
            respond(409, [
                'success' => false,
                'message' => 'Evaluasi sudah pernah dilakukan. Gemini tidak dipanggil ulang.'
            ]);
        }

        if (trim($answer['code_submission'] ?? '') === '') {
            respond(400, [
                'success' => false,
                'message' => 'Ada jawaban coding yang masih kosong.'
            ]);
        }
    }

    $evaluator = new CodingEvaluationService();
    $results = [];

    // Evaluasi seluruh jawaban.
    foreach ($answers as $answer) {

        $stmt = $pdo->prepare("
            SELECT input_data, expected_output, points
            FROM coding_test_cases
            WHERE question_id = ?
            ORDER BY id ASC
        ");

        $stmt->execute([$answer['question_id']]);
        $testCases = $stmt->fetchAll();

        if (count($testCases) === 0) {
            throw new Exception(
                'Test case belum tersedia untuk soal ID ' .
                $answer['question_id']
            );
        }

        $evaluation = $evaluator->evaluate(
            $answer,
            $answer['code_submission'],
            $testCases
        );

        $score = max(
            0,
            min(
                (float) $answer['points'],
                (float) $evaluation['score']
            )
        );

        $results[] = [
            'answer_id' => (int) $answer['answer_id'],
            'competency_id' => (int) $answer['competency_id'],
            'question_id' => (int) $answer['question_id'],
            'score' => $score,
            'feedback' => $evaluation['feedback']
        ];
    }

    // Simpan hasil evaluasi.
    $pdo->beginTransaction();

    $updateAnswer = $pdo->prepare("
        UPDATE answers
        SET score = ?,
            coding_feedback = ?,
            coding_evaluated_at = NOW(),
            is_correct = NULL
        WHERE id = ?
          AND coding_evaluated_at IS NULL
    ");

    foreach ($results as $result) {
        $updateAnswer->execute([
            $result['score'],
            $result['feedback'],
            $result['answer_id']
        ]);

        if ($updateAnswer->rowCount() !== 1) {
            throw new Exception(
                'Jawaban berubah atau sudah dievaluasi.'
            );
        }
    }

    // Hitung skor coding per kompetensi.
    $stmt = $pdo->prepare("
        SELECT
            q.competency_id,
            COALESCE(SUM(a.score), 0) AS coding_score
        FROM questions q
        LEFT JOIN answers a
            ON a.question_id = q.id
            AND a.session_id = ?
        WHERE q.type = 'coding'
        GROUP BY q.competency_id
    ");

    $stmt->execute([$sessionId]);
    $codingScores = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Ambil nilai pilihan ganda.
    $stmt = $pdo->prepare("
        SELECT competency_id, multiple_choice_score
        FROM assessment_scores
        WHERE session_id = ?
    ");

    $stmt->execute([$sessionId]);
    $mcScores = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $updateScore = $pdo->prepare("
        UPDATE assessment_scores
        SET coding_score = ?,
            final_score = ?
        WHERE session_id = ?
          AND competency_id = ?
    ");

    foreach ($mcScores as $competencyId => $mcScore) {

        $mc = (float) $mcScore;
        $coding = (float) ($codingScores[$competencyId] ?? 0);

        // Bobot 50% pilihan ganda dan 50% coding.
        $finalScore = ($mc / 20 * 50)
                    + ($coding / 20 * 50);

        $finalScore = max(0, min(100, $finalScore));

        $updateScore->execute([
            $coding,
            round($finalScore, 2),
            $sessionId,
            $competencyId
        ]);
    }

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'Evaluasi coding berhasil diselesaikan.',
        'results' => $results
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Coding Evaluation Error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Terjadi kesalahan saat mengevaluasi coding.'
    ]);
}
