
<?php

require_once __DIR__ . '/../config/security.php';

jagatedu_start_session();
jagatedu_require_csrf();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/GeminiService.php';

function sendResponse($success, $message, $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode([
        'success' => $success,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Wajib login
if (!isset($_SESSION['user_id'])) {
    sendResponse(false, 'Silakan login terlebih dahulu.', 401);
}

// Hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Metode request tidak diizinkan.', 405);
}

// Rate limit 10 pesan per menit
$_SESSION['chat_requests'] = $_SESSION['chat_requests'] ?? [];

$_SESSION['chat_requests'] = array_filter(
    $_SESSION['chat_requests'],
    fn($time) => $time > time() - 60
);

if (count($_SESSION['chat_requests']) >= 10) {
    sendResponse(false, 'Terlalu banyak pesan. Coba lagi nanti.', 429);
}

// Membaca JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    sendResponse(false, 'Format data tidak valid.', 400);
}

$message = trim($data['message'] ?? '');

$conversationId = filter_var(
    $data['conversation_id'] ?? null,
    FILTER_VALIDATE_INT
);

if ($message === '') {
    sendResponse(false, 'Pesan tidak boleh kosong.', 400);
}

if (mb_strlen($message) > 2000) {
    sendResponse(false, 'Pesan maksimal 2000 karakter.', 400);
}

if (!$conversationId || $conversationId < 1) {
    sendResponse(false, 'ID percakapan tidak valid.', 400);
}

$userId = $_SESSION['user_id'];

// Ambil profil siswa yang sedang login
$stmt = $pdo->prepare("
    SELECT name, kelas, jurusan, role
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    sendResponse(false, 'Profil pengguna tidak ditemukan.', 404);
}

// Ambil assessment terbaru yang sudah selesai
$stmt = $pdo->prepare("
    SELECT id
    FROM assessment_sessions
    WHERE user_id = ?
    AND status = 'completed'
    ORDER BY finished_at DESC, id DESC
    LIMIT 1
");

$stmt->execute([$userId]);
$assessmentSession = $stmt->fetch(PDO::FETCH_ASSOC);

$assessmentContext = "";

if ($assessmentSession) {

    $stmt = $pdo->prepare("
        SELECT
            c.name AS competency,
            sc.multiple_choice_score,
            sc.coding_score,
            sc.final_score
        FROM assessment_scores sc
        JOIN competencies c
            ON c.id = sc.competency_id
        WHERE sc.session_id = ?
        ORDER BY c.id ASC
    ");

    $stmt->execute([$assessmentSession['id']]);
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $assessmentContext = "
HASIL ASSESSMENT PENGGUNA:

";

    foreach ($scores as $score) {
        $assessmentContext .= "
Kompetensi: {$score['competency']}
Nilai pilihan ganda: {$score['multiple_choice_score']}
Nilai coding: {$score['coding_score']}
Nilai akhir: {$score['final_score']}
";
    }

    $assessmentContext .= "
Gunakan hasil assessment ini untuk memberikan saran belajar.
Jangan mengubah atau mengarang nilai.
";
} else {
    $assessmentContext = "
PENGGUNA BELUM MEMILIKI HASIL ASSESSMENT.
Jangan mengarang kemampuan atau nilai pengguna.
Sarankan pengguna mengikuti assessment jika relevan.
";
}

try {

    // Pastikan percakapan milik pengguna yang login
    $stmt = $pdo->prepare("
        SELECT id
        FROM chat_conversations
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$conversationId, $userId]);

    if (!$stmt->fetch()) {
        sendResponse(false, 'Percakapan tidak ditemukan.', 404);
    }

    // Ambil jumlah pesan sebelumnya
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM chat_messages
        WHERE conversation_id = ?
    ");

    $stmt->execute([$conversationId]);

    $totalMessages = (int) $stmt->fetchColumn();

    // Ambil maksimal 20 pesan sebelumnya
    $stmt = $pdo->prepare("
        SELECT sender, message
        FROM chat_messages
        WHERE conversation_id = ?
        ORDER BY id DESC
        LIMIT 20
    ");

    $stmt->execute([$conversationId]);

    $history = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));


    // Konteks profil untuk JagatEDU AI
    $studentContext = "
    INFORMASI PENGGUNA:
    Nama: {$student['name']}
    Kelas: {$student['kelas']}
    Jurusan: {$student['jurusan']}
    Role: {$student['role']}

    INSTRUKSI PERSONALISASI:
    - Sapa pengguna menggunakan namanya jika sesuai.
    - Sesuaikan penjelasan dengan kelas dan jurusannya.
    - Jika pengguna siswa RPL/PPLG, prioritaskan contoh pemrograman dan teknologi.
    - Jangan mengarang nilai, kemampuan, hasil assessment, atau pengalaman pengguna.
    - Jika informasi belum tersedia, tanyakan kepada pengguna.
    - Tetap menjadi asisten pendidikan, pengembangan diri, dan panduan karier JagatEDU AI.
    - Jangan menanyakan nama, kelas, atau jurusan jika informasi tersebut sudah tersedia.
    - Gunakan informasi profil yang diberikan sebagai sumber utama.
    - Jangan menyimpulkan bahwa pengguna tidak memahami materi hanya dari nilai 0.
    - Jelaskan hasil berdasarkan angka yang tersedia tanpa memberikan diagnosis kemampuan secara berlebihan.
    - Bedakan antara nilai assessment dan kemampuan nyata pengguna.
    - Berikan rekomendasi belajar yang realistis, bertahap, dan sesuai kompetensi.

    FORMAT ANALISIS ASSESSMENT:

    Jika pengguna meminta analisis hasil assessment:
    1. Tampilkan 2 kompetensi dengan nilai tertinggi.
    2. Tampilkan kompetensi yang paling perlu ditingkatkan.
    3. Jelaskan perbedaan nilai pilihan ganda dan coding.
    4. Berikan 3 prioritas belajar berdasarkan hasil assessment.
    5. Berikan contoh latihan praktis untuk setiap prioritas.
    6. Buat rekomendasi yang sesuai dengan jurusan dan tingkat pendidikan pengguna.

    ATURAN:
    - Gunakan data assessment terbaru yang tersedia.
    - Jangan mengarang nilai atau hasil pengujian.
    - Jika belum ada assessment, jelaskan bahwa data belum tersedia.
    - Jangan menganggap nilai assessment sebagai ukuran mutlak kecerdasan atau kemampuan seseorang.
    - Gunakan bahasa yang ramah, suportif, dan tidak menghakimi.    

    {$assessmentContext}
    ";


    // Konversi database ke format Gemini
    $contents = [];

    // Masukkan konteks profil siswa sebagai instruksi awal
    $contents[] = [
        'role' => 'user',
        'parts' => [
            ['text' => $studentContext]
        ]
    ];

    $contents[] = [
        'role' => 'model',
        'parts' => [
            ['text' => 'Baik, saya akan menggunakan informasi profil tersebut sebagai konteks untuk membantu siswa.']
        ]
    ];

    foreach ($history as $item) {
        $contents[] = [
            'role' => $item['sender'] === 'assistant' ? 'model' : 'user',
            'parts' => [
                ['text' => $item['message']]
            ]
        ];
    }

    // Tambahkan pesan baru
    $contents[] = [
        'role' => 'user',
        'parts' => [
            ['text' => $message]
        ]
    ];

    // Panggil Gemini
    $gemini = new GeminiService();

    $reply = $gemini->generateResponse($contents);

    if (!$reply) {
        sendResponse(false, 'AI belum memberikan jawaban.', 502);
    }

    // Mulai transaksi penyimpanan
    $pdo->beginTransaction();

    // Simpan pesan user
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages
            (conversation_id, sender, message)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $conversationId,
        'user',
        $message
    ]);

    // Jika ini pesan pertama, jadikan sebagai judul
    if ($totalMessages === 0) {

        $title = mb_substr($message, 0, 45);

        $stmt = $pdo->prepare("
            UPDATE chat_conversations
            SET title = ?
            WHERE id = ? AND user_id = ?
        ");

        $stmt->execute([
            $title,
            $conversationId,
            $userId
        ]);
    }

    // Simpan balasan assistant
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages
            (conversation_id, sender, message)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $conversationId,
        'assistant',
        $reply
    ]);

    // Update waktu percakapan
    $stmt = $pdo->prepare("
        UPDATE chat_conversations
        SET updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([
        $conversationId,
        $userId
    ]);

    // Simpan seluruh perubahan
    $pdo->commit();

    // Update rate limit
    $_SESSION['chat_requests'][] = time();

    sendResponse(true, $reply);
} catch (Throwable $e) {

    // Batalkan transaksi jika terjadi error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('JagatEDU Chat Error: ' . $e->getMessage());

    sendResponse(
        false,
        'JagatEDU AI sedang mengalami gangguan. Coba lagi nanti.',
        500
    );
}
