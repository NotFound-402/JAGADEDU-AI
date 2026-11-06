<?php
require_once __DIR__ . '/config/security.php';

jagatedu_start_session();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AssessmentController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$sessionId = filter_input(INPUT_GET, 'session_id', FILTER_VALIDATE_INT);

if (!$sessionId || $sessionId < 1) {
    $controller = new AssessmentController($pdo);
    $sessionId = $controller->startAssessment($userId);
    header('Location: assessment.php?session_id=' . (int) $sessionId);
    exit;
}

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
    $userId
]);

if (!$stmt->fetch()) {
    $controller = new AssessmentController($pdo);
    $newSessionId = $controller->startAssessment($userId);
    header('Location: assessment.php?session_id=' . (int) $newSessionId);
    exit;
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(jagatedu_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <title>Assessment | JagatEDU AI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style/assessment.css">
</head>

<body class="assessment-body">
    <header class="navbar">
        <div class="container navbar-content">
            <a href="dashboard.php" class="brand" aria-label="JAGATEDU AI, ke dashboard">
                <div class="brand-icon" aria-hidden="true">✿</div>
                <div>
                    <h1>JAGAT<span>EDU</span> AI</h1>
                    <p>Student Development Intelligence</p>
                </div>
            </a>

            <button class="menu-toggle" id="assessmentMenuToggle" type="button" aria-label="Buka menu" aria-controls="assessmentNav" aria-expanded="false">☰</button>

            <nav class="nav-menu" id="assessmentNav" aria-label="Navigasi assessment">
                <a href="dashboard.php">Dashboard</a>
                <a href="assessment.php" class="active" aria-current="page">Asesmen</a>
                <a href="index.php#pembelajaran">Rencana</a>
                <a href="chatbot.php">AI Coach</a>
                <button class="theme-button" id="assessmentThemeButton" type="button" aria-label="Aktifkan tema terang" aria-pressed="false">☾</button>
                <a href="logout.php" class="login-button">Keluar</a>
            </nav>
        </div>
    </header>

    <div class="assessment-shell">
        <header class="assessment-header">
            <div class="brand-block">
                <div class="brand-mark">J</div>
                <div class="brand-copy">
                    <h1>JAGAT<span>EDU</span> AI</h1>
                    <p>Assessment Platform</p>
                </div>
            </div>

            <div class="header-center">
                <span class="header-label">Assessment</span>
                <h2>Logical Reasoning & Coding Challenge</h2>
            </div>

            <div class="timer-box" aria-live="polite">
                <span>Sisa Waktu</span>
                <strong id="timer">56:24</strong>
            </div>
        </header>

        <main class="assessment-layout">
            <aside class="question-sidebar">
                <div class="sidebar-top">
                    <div>
                        <span class="sidebar-caption">Daftar Soal</span>
                        <h3>15 Questions</h3>
                    </div>
                    <span class="sidebar-count" id="sidebarCount">3/15 selesai</span>
                </div>

                <div id="questionList" class="question-list" aria-label="Navigasi soal"></div>
            </aside>

            <section class="assessment-panel">
                <div class="progress-wrap">
                    <div class="progress-head">
                        <span>Progress Pengerjaan</span>
                        <strong id="progressText">3/15</strong>
                    </div>
                    <div class="progress-track">
                        <div id="progressBar" class="progress-bar"></div>
                    </div>
                </div>

                <div id="questionCard" class="question-card"></div>

                <div class="assessment-actions">
                    <button type="button" class="btn btn-secondary" id="prevQuestion">Sebelumnya</button>
                    <button type="button" class="btn btn-primary" id="nextQuestion">Berikutnya</button>
                    <button type="button" class="btn btn-finish" id="finishAssessment">Selesaikan Assessment</button>
                </div>
            </section>
        </main>
    </div>

    <div id="toast" class="toast" aria-live="polite">Preview tampilan assessment berhasil dimuat.</div>

    <script>
        window.ASSESSMENT_SESSION_ID = <?= json_encode($sessionId) ?>;
        window.JAGATEDU_CSRF = <?= json_encode(jagatedu_csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>

    <script>
        const assessmentMenuToggle = document.getElementById('assessmentMenuToggle');
        const assessmentNav = document.getElementById('assessmentNav');

        if (assessmentMenuToggle && assessmentNav) {
            assessmentMenuToggle.addEventListener('click', function () {
                const isOpen = !assessmentNav.classList.contains('show');
                assessmentNav.classList.toggle('show', isOpen);
                assessmentMenuToggle.setAttribute('aria-expanded', String(isOpen));
                assessmentMenuToggle.setAttribute('aria-label', isOpen ? 'Tutup menu' : 'Buka menu');
                assessmentMenuToggle.textContent = isOpen ? '✕' : '☰';
            });

            assessmentNav.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    assessmentNav.classList.remove('show');
                    assessmentMenuToggle.setAttribute('aria-expanded', 'false');
                    assessmentMenuToggle.textContent = '☰';
                });
            });
        }
    </script>

    <script src="/js/assessment.js" defer></script>
</body>

</html>