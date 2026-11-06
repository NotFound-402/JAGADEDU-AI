
<?php
require_once __DIR__ . '/config/security.php';

jagatedu_start_session();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b1f3a">
    <meta name="csrf-token" content="<?= htmlspecialchars(jagatedu_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

    <title>Roadmap Belajar | JAGATEDU AI</title>

    <link rel="stylesheet" href="/style/roadmap.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>

<body class="dashboard-page roadmap-page">

    <header class="navbar">
        <div class="container navbar-content">

            <a href="dashboard.php" class="brand">
                <div class="brand-icon">✿</div>
                <div>
                    <h1>JAGAT<span>EDU</span> AI</h1>
                    <p>Student Development Intelligence</p>
                </div>
            </a>

            <nav class="nav-menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="assessment.php">Asesmen</a>
                <a href="chatbot.php">AI Coach</a>
                <a href="logout.php" class="login-button">Keluar</a>
            </nav>

        </div>
    </header>

    <main class="dashboard-main">

        <section class="dashboard-welcome">
            <div class="container dashboard-welcome-content">
                <div>
                    <span class="section-label">PERSONAL LEARNING PLAN</span>
                    <h2>Roadmap Belajarmu</h2>
                    <p>
                        Selesaikan aktivitas secara bertahap untuk
                        meningkatkan kemampuan programming-mu.
                    </p>
                </div>

                <a href="dashboard.php" class="primary-button">
                    ← Kembali ke Dashboard
                </a>
            </div>
        </section>

        <div class="container dashboard-content">

            <p id="roadmapMessage" class="dashboard-error" role="alert" hidden></p>

            <section class="dashboard-panel">

                <span class="section-label">AI PERSONALIZED ROADMAP</span>

                <h2 id="roadmapTitle">Memuat roadmap...</h2>

                <p id="roadmapDescription"></p>

                <div class="roadmap-progress-info">
                    <span id="roadmapProgressText">0 dari 0 aktivitas selesai</span>
                    <strong id="roadmapPercentage">0%</strong>
                </div>

                <div class="roadmap-progress-track">
                    <span id="roadmapProgressBar" style="width: 0%"></span>
                </div>

                <div class="roadmap-stats">
                    <div>
                        <strong id="totalActivities">0</strong>
                        <span>Total aktivitas</span>
                    </div>

                    <div>
                        <strong id="completedActivities">0</strong>
                        <span>Selesai</span>
                    </div>

                    <div>
                        <strong id="pendingActivities">0</strong>
                        <span>Belum selesai</span>
                    </div>
                </div>

            </section>

            <section class="roadmap-list-section">

                <div class="dashboard-section-heading">
                    <div>
                        <span class="section-label">30 DAYS DEVELOPMENT</span>
                        <h2>Aktivitas Belajar</h2>
                    </div>
                </div>

                <div id="roadmapItems">
                    <p>Memuat aktivitas belajar...</p>
                </div>

            </section>

        </div>

    </main>

    <footer class="footer">
        <div class="container footer-content">
            <div>
                <h2>JAGAT<span>EDU</span> AI</h2>
                <p>Student Development Intelligence</p>
            </div>

            <div class="footer-copy">
                © 2026 JAGATEDU AI.
            </div>
        </div>
    </footer>

    <script>
        window.JAGATEDU_CSRF = <?= json_encode(jagatedu_csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
    <script src="js/roadmap.js"></script>

</body>
</html>
