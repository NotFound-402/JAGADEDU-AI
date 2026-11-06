
<?php
require_once __DIR__ . '/../config/security.php';
jagatedu_start_session();

header('Content-Type: application/json');

require_once __DIR__ . '/../controllers/AssessmentController.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Silakan login terlebih dahulu.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method tidak diizinkan.'
    ]);
    exit;
}

$sessionId = filter_input(INPUT_GET, 'session_id', FILTER_VALIDATE_INT);

if (!$sessionId || $sessionId < 1) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Session ID tidak valid.'
    ]);
    exit;
}

try {
    // Pastikan sesi milik user yang sedang login
    $stmt = $pdo->prepare("
        SELECT id
        FROM assessment_sessions
        WHERE id = ?
          AND user_id = ?
          AND status = 'in_progress'
        LIMIT 1
    ");

    $stmt->execute([
        $sessionId,
        $_SESSION['user_id']
    ]);

    if (!$stmt->fetch()) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Sesi assessment tidak ditemukan atau sudah selesai.'
        ]);
        exit;
    }

    $controller = new AssessmentController($pdo);
    $questions = $controller->getQuestions();

    // Ambil jawaban yang sudah tersimpan
    $stmt = $pdo->prepare("
    SELECT
        question_id,
        selected_option_id,
        code_submission
    FROM answers
    WHERE session_id = ?
");

    $stmt->execute([$sessionId]);

    $savedAnswers = $stmt->fetchAll();

    $answerMap = [];

    foreach ($savedAnswers as $answer) {
        $answerMap[$answer['question_id']] = $answer;
    }

    // Gabungkan jawaban tersimpan ke setiap soal
    foreach ($questions as &$question) {
        $saved = $answerMap[$question['id']] ?? null;

        $question['selected_option_id'] = $saved
            ? $saved['selected_option_id']
            : null;

        $question['code_submission'] = $saved
            ? $saved['code_submission']
            : '';
    }

    unset($question);

    echo json_encode([
        'success' => true,
        'session_id' => $sessionId,
        'total_questions' => count($questions),
        'questions' => $questions
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil soal.'
    ]);
}
