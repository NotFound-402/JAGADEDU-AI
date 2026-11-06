
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
    // Pastikan session milik user dan sudah selesai.
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

    // Periksa roadmap yang sudah tersedia.
    $stmt = $pdo->prepare("
        SELECT r.id, r.title, r.description, r.duration_days
        FROM roadmaps r
        WHERE r.user_id = ?
          AND r.analysis_id = (
              SELECT id
              FROM ai_analyses
              WHERE session_id = ?
          )
        LIMIT 1
    ");
    $stmt->execute([$userId, $sessionId]);
    $existingRoadmap = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingRoadmap) {
        respond(200, [
            'success' => true,
            'message' => 'Roadmap sudah tersedia.',
            'roadmap' => $existingRoadmap
        ]);
    }

    // Ambil analisis AI.
    $stmt = $pdo->prepare("
        SELECT
            id,
            summary,
            strengths,
            weaknesses,
            recommendations
        FROM ai_analyses
        WHERE session_id = ?
        LIMIT 1
    ");
    $stmt->execute([$sessionId]);
    $analysis = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$analysis) {
        respond(400, [
            'success' => false,
            'message' => 'AI Analysis belum tersedia. Buat analisis terlebih dahulu.'
        ]);
    }

    $analysisData = [
        'summary' => $analysis['summary'],
        'strengths' => json_decode($analysis['strengths'], true),
        'weaknesses' => json_decode($analysis['weaknesses'], true),
        'recommendations' => json_decode($analysis['recommendations'], true)
    ];

    // Prompt untuk Gemini.
    $prompt = "
Kamu adalah AI Learning Path Generator untuk JagatEDU AI.

Buat roadmap belajar personal selama 30 hari untuk siswa kelas 12 RPL/PPLG SMK.

Gunakan hasil analisis berikut:

" . json_encode(
        $analysisData,
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    ) . "

Ketentuan:
1. Prioritaskan kompetensi yang masih lemah.
2. Jangan mengabaikan kompetensi yang sudah kuat.
3. Buat aktivitas belajar yang realistis untuk siswa SMK.
4. Setiap aktivitas harus memiliki tugas praktik.
5. Urutkan aktivitas dari dasar hingga proyek kecil.
6. Gunakan bahasa Indonesia yang mudah dipahami.
7. Buat tepat 30 aktivitas, satu aktivitas untuk setiap hari.
8. Jangan membuat klaim bahwa siswa sudah menyelesaikan aktivitas.

Balas hanya JSON valid dengan format:

{
    \"title\": \"Roadmap Belajar Personal 30 Hari\",
    \"description\": \"Deskripsi roadmap\",
    \"duration_days\": 30,
    \"items\": [
        {
            \"day_number\": 1,
            \"title\": \"Judul aktivitas\",
            \"description\": \"Penjelasan dan tugas praktik\"
        }
    ]
}
";

    $gemini = new GeminiService();
    $rawResponse = $gemini->generateResponse($prompt);

    // Bersihkan format Markdown jika diberikan Gemini.
    $cleanResponse = trim($rawResponse);
    $cleanResponse = preg_replace('/^```(?:json)?\s*/i', '', $cleanResponse);
    $cleanResponse = preg_replace('/\s*```$/', '', $cleanResponse);

    $roadmapData = json_decode($cleanResponse, true);

    if (
        !is_array($roadmapData) ||
        !isset($roadmapData['title']) ||
        !is_string($roadmapData['title']) ||
        !isset($roadmapData['description']) ||
        !is_string($roadmapData['description']) ||
        !isset($roadmapData['items']) ||
        !is_array($roadmapData['items']) ||
        count($roadmapData['items']) !== 30
    ) {
        respond(502, [
            'success' => false,
            'message' => 'Format roadmap dari AI tidak valid.',
            'raw_response' => $rawResponse
        ]);
    }

    // Validasi seluruh aktivitas sebelum menyimpan.
    $validatedItems = [];

    foreach ($roadmapData['items'] as $item) {
        $day = filter_var(
            $item['day_number'] ?? null,
            FILTER_VALIDATE_INT
        );

        $title = trim($item['title'] ?? '');
        $description = trim($item['description'] ?? '');

        if (
            !$day ||
            $day < 1 ||
            $day > 30 ||
            $title === '' ||
            $description === '' ||
            !is_string($title) ||
            !is_string($description)
        ) {
            respond(502, [
                'success' => false,
                'message' => 'Terdapat aktivitas roadmap yang tidak valid.'
            ]);
        }

        $validatedItems[] = [
            'day_number' => $day,
            'title' => $title,
            'description' => $description
        ];
    }

    $days = array_column($validatedItems, 'day_number');
    sort($days);

    if ($days !== range(1, 30)) {
        respond(502, [
            'success' => false,
            'message' => 'Nomor hari roadmap harus berurutan dari 1 sampai 30.'
        ]);
    }

    // Simpan roadmap dan seluruh aktivitas.
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO roadmaps (
            user_id,
            analysis_id,
            title,
            description,
            duration_days
        ) VALUES (?, ?, ?, ?, 30)
    ");

    $stmt->execute([
        $userId,
        $analysis['id'],
        $roadmapData['title'],
        $roadmapData['description']
    ]);

    $roadmapId = (int) $pdo->lastInsertId();

    $insertItem = $pdo->prepare("
        INSERT INTO roadmap_items (
            roadmap_id,
            material_id,
            title,
            description,
            day_number,
            status
        ) VALUES (?, NULL, ?, ?, ?, 'pending')
    ");

    foreach ($validatedItems as $item) {
        $insertItem->execute([
            $roadmapId,
            $item['title'],
            $item['description'],
            $item['day_number']
        ]);
    }

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'AI Roadmap berhasil dibuat.',
        'roadmap' => [
            'id' => $roadmapId,
            'analysis_id' => (int) $analysis['id'],
            'title' => $roadmapData['title'],
            'description' => $roadmapData['description'],
            'duration_days' => 30,
            'total_items' => count($validatedItems)
        ]
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Generate Roadmap Error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Gagal membuat roadmap: ' . $e->getMessage()
    ]);
}
