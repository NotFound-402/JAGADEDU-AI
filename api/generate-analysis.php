
<?php

require_once __DIR__ . '/../config/security.php';

jagatedu_start_session();
jagatedu_require_csrf();

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/GeminiService.php';

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

$userId = (int) $_SESSION['user_id'];

try {
    // Pastikan asesmen milik user dan sudah selesai.
    $stmt = $pdo->prepare("
        SELECT id
        FROM assessment_sessions
        WHERE id = ?
          AND user_id = ?
          AND status = 'completed'
        LIMIT 1
    ");
    $stmt->execute([$sessionId, $userId]);

    if (!$stmt->fetch()) {
        respond(404, [
            'success' => false,
            'message' => 'Asesmen tidak ditemukan atau belum selesai.'
        ]);
    }

    // Jangan menghabiskan kuota Gemini jika analisis sudah tersedia.
    $stmt = $pdo->prepare("
        SELECT id, summary, strengths, weaknesses, recommendations
        FROM ai_analyses
        WHERE session_id = ?
        LIMIT 1
    ");
    $stmt->execute([$sessionId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        respond(200, [
            'success' => true,
            'message' => 'Analisis sudah tersedia.',
            'analysis' => $existing
        ]);
    }

    // Ambil nilai setiap kompetensi.
    $stmt = $pdo->prepare("
        SELECT
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

    if (!$scores) {
        respond(400, [
            'success' => false,
            'message' => 'Data nilai asesmen belum tersedia.'
        ]);
    }

    // Ambil feedback evaluasi coding.
    $stmt = $pdo->prepare("
        SELECT
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
    $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Hitung rata-rata nilai.
    $total = 0;

    foreach ($scores as $score) {
        $total += (float) $score['final_score'];
    }

    $average = round($total / count($scores), 2);

    $assessmentData = [
        'average_score' => $average,
        'competency_scores' => $scores,
        'coding_feedback' => $feedback
    ];

    $prompt = "
Kamu adalah AI Career & Learning Advisor untuk JagatEDU AI,
platform pendidikan bagi siswa kelas 12 jurusan RPL/PPLG.

Analisis hasil asesmen siswa berikut:

" . json_encode($assessmentData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "

Buat analisis yang spesifik berdasarkan data tersebut.

Aturan:
1. Jangan mengarang nilai atau hasil asesmen.
2. Identifikasi kompetensi terkuat dan terlemah.
3. Gunakan feedback coding untuk menjelaskan kekurangan siswa.
4. Berikan rekomendasi belajar yang praktis dan bertahap.
5. Gunakan bahasa Indonesia yang mudah dipahami siswa SMK.
6. Jangan memberikan diagnosis psikologis atau medis.
7. Jangan mengklaim kode benar-benar dieksekusi; evaluasi coding berasal dari penilaian AI.

Balas HANYA dengan JSON valid menggunakan struktur:

{
    \"summary\": \"Ringkasan kemampuan siswa\",
    \"strengths\": [
        \"Kekuatan pertama\",
        \"Kekuatan kedua\"
    ],
    \"weaknesses\": [
        \"Kelemahan pertama\",
        \"Kelemahan kedua\"
    ],
    \"recommendations\": [
        \"Rekomendasi pertama\",
        \"Rekomendasi kedua\"
    ]
}
";

    // Minta analisis Gemini.
    $gemini = new GeminiService();
    $rawResponse = $gemini->generateResponse($prompt);

    // Bersihkan kemungkinan Markdown JSON.
    $cleanResponse = trim($rawResponse);
    $cleanResponse = preg_replace('/^```(?:json)?\s*/i', '', $cleanResponse);
    $cleanResponse = preg_replace('/\s*```$/', '', $cleanResponse);

    $analysis = json_decode($cleanResponse, true);

    if (
        !is_array($analysis) ||
        !isset($analysis['summary']) ||
        !is_string($analysis['summary']) ||
        !isset($analysis['strengths']) ||
        !is_array($analysis['strengths']) ||
        !isset($analysis['weaknesses']) ||
        !is_array($analysis['weaknesses']) ||
        !isset($analysis['recommendations']) ||
        !is_array($analysis['recommendations'])
    ) {
        respond(502, [
            'success' => false,
            'message' => 'Format analisis AI tidak valid.',
            'raw_response' => $rawResponse
        ]);
    }

    // Simpan analisis ke database.
    $stmt = $pdo->prepare("
        INSERT INTO ai_analyses (
            session_id,
            summary,
            strengths,
            weaknesses,
            recommendations,
            raw_response
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $sessionId,
        $analysis['summary'],
        json_encode($analysis['strengths'], JSON_UNESCAPED_UNICODE),
        json_encode($analysis['weaknesses'], JSON_UNESCAPED_UNICODE),
        json_encode($analysis['recommendations'], JSON_UNESCAPED_UNICODE),
        $rawResponse
    ]);

    respond(200, [
        'success' => true,
        'message' => 'AI Analysis berhasil dibuat.',
        'analysis' => [
            'id' => (int) $pdo->lastInsertId(),
            'session_id' => $sessionId,
            'summary' => $analysis['summary'],
            'strengths' => $analysis['strengths'],
            'weaknesses' => $analysis['weaknesses'],
            'recommendations' => $analysis['recommendations']
        ]
    ]);

} catch (PDOException $e) {
    error_log('Generate Analysis Database Error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Terjadi kesalahan database.'
    ]);

} catch (Throwable $e) {
    error_log('Generate Analysis Error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Gagal membuat analisis AI: ' . $e->getMessage()
    ]);
}
