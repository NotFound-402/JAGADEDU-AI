
<?php
require_once __DIR__ . '/../config/security.php';

jagatedu_start_session();
jagatedu_require_csrf();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

// 1. Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Silakan login terlebih dahulu.'
    ]);

    exit;
}

// 2. Pastikan request menggunakan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method tidak diizinkan.'
    ]);

    exit;
}

// 3. Ambil data JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Format data tidak valid.'
    ]);

    exit;
}

$sessionId = filter_var(
    $data['session_id'] ?? null,
    FILTER_VALIDATE_INT
);

$questionId = filter_var(
    $data['question_id'] ?? null,
    FILTER_VALIDATE_INT
);

$selectedOptionId = $data['selected_option_id'] ?? null;
$codeSubmission = $data['code_submission'] ?? null;

if (!$sessionId || !$questionId) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Session ID atau Question ID tidak valid.'
    ]);

    exit;
}

try {
    // 4. Pastikan session milik pengguna dan masih aktif
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
            'message' => 'Session assessment tidak ditemukan atau sudah selesai.'
        ]);

        exit;
    }

    // 5. Ambil informasi soal
    $stmt = $pdo->prepare("
        SELECT id, type, points
        FROM questions
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$questionId]);

    $question = $stmt->fetch();

    if (!$question) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Soal tidak ditemukan.'
        ]);

        exit;
    }

    $isCorrect = null;
    $score = 0;
    $selectedOptionId = null;
    $codeSubmission = null;

    // 6. Validasi berdasarkan jenis soal
    if ($question['type'] === 'multiple_choice') {

        $optionId = filter_var(
            $data['selected_option_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$optionId) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Pilihan jawaban wajib diisi.'
            ]);

            exit;
        }

        // Pastikan pilihan benar-benar milik soal tersebut
        $stmt = $pdo->prepare("
            SELECT id, is_correct
            FROM question_options
            WHERE id = ?
              AND question_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $optionId,
            $questionId
        ]);

        $option = $stmt->fetch();

        if (!$option) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Pilihan jawaban tidak valid.'
            ]);

            exit;
        }

        $selectedOptionId = (int) $option['id'];

        // Pastikan selalu integer 0 atau 1
        $isCorrect = (int) $option['is_correct'];

        $score = ($isCorrect === 1)
            ? (float) $question['points']
            : 0;
    } elseif ($question['type'] === 'coding') {

        if (!is_string($data['code_submission'] ?? null)) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Kode jawaban wajib berupa teks.'
            ]);

            exit;
        }

        $codeSubmission = $data['code_submission'];

        if (strlen($codeSubmission) > 100000) {
            http_response_code(413);

            echo json_encode([
                'success' => false,
                'message' => 'Ukuran kode terlalu besar.'
            ]);

            exit;
        }

        // Belum ada penilaian otomatis untuk kode.
        // Nilai akan diproses pada tahap penilaian coding.
        $isCorrect = null;
        $score = 0;
    } else {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Jenis soal tidak dikenali.'
        ]);

        exit;
    }


    error_log('DEBUG SAVE ANSWER: ' . json_encode([
        'session_id' => $sessionId,
        'question_id' => $questionId,
        'selected_option_id' => $selectedOptionId,
        'code_submission' => $codeSubmission,
        'is_correct' => $isCorrect,
        'score' => $score
    ]));
    // 7. Simpan atau perbarui jawaban
    $stmt = $pdo->prepare("
    INSERT INTO answers (
        session_id,
        question_id,
        selected_option_id,
        code_submission,
        is_correct,
        score
    )
    VALUES (?, ?, ?, ?, ?, ?)

    ON DUPLICATE KEY UPDATE
        selected_option_id = VALUES(selected_option_id),
        code_submission = VALUES(code_submission),
        is_correct = VALUES(is_correct),
        score = VALUES(score),
        submitted_at = CURRENT_TIMESTAMP
");

    $stmt->bindValue(1, $sessionId, PDO::PARAM_INT);
    $stmt->bindValue(2, $questionId, PDO::PARAM_INT);

    $stmt->bindValue(
        3,
        $selectedOptionId,
        $selectedOptionId === null ? PDO::PARAM_NULL : PDO::PARAM_INT
    );

    $stmt->bindValue(
        4,
        $codeSubmission,
        $codeSubmission === null ? PDO::PARAM_NULL : PDO::PARAM_STR
    );

    $stmt->bindValue(
        5,
        $isCorrect,
        $isCorrect === null ? PDO::PARAM_NULL : PDO::PARAM_INT
    );

    $stmt->bindValue(6, $score, PDO::PARAM_STR);

    $stmt->execute();

    $stmt->execute([
        $sessionId,
        $questionId,
        $selectedOptionId,
        $codeSubmission,
        $isCorrect,
        $score
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Jawaban berhasil disimpan.',
        'question_id' => $questionId
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('SAVE ANSWER ERROR: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Throwable $e) {
    error_log('SAVE ANSWER FATAL: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
