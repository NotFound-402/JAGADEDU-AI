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
    <meta name="description" content="Dashboard pengembangan diri JAGATEDU AI untuk melihat keterampilan, minat, refleksi, dan rencana karier.">
    <meta name="theme-color" content="#0b1f3a">
    <meta name="csrf-token" content="<?= htmlspecialchars(jagatedu_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <title>Dashboard | JAGATEDU AI</title>
    <link rel="stylesheet" href="/style/dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>

<body class="dashboard-page">
    <a class="skip-link" href="#dashboardMain">Lewati ke konten</a>
    <header class="navbar">
        <div class="container navbar-content">
            <a href="index.php" class="brand" aria-label="JAGATEDU AI, ke beranda">
                <div class="brand-icon" aria-hidden="true">✿</div>
                <div>
                    <h1>JAGAT<span>EDU</span> AI</h1>
                    <p>Student Development Intelligence</p>
                </div>
            </a>
            <button class="menu-toggle" id="menuToggle" type="button" aria-label="Buka menu" aria-controls="dashboardNav" aria-expanded="false">☰</button>
            <nav class="nav-menu" id="dashboardNav" aria-label="Navigasi dashboard">
                <a class="active" href="dashboard.php" aria-current="page">Dashboard</a>
                <a href="assessment.php">Asesmen</a>
                <a href="roadmap.php">Roadmap</a>
                <a href="chatbot.php">AI Coach</a>
                <button class="theme-button" id="themeButton" type="button" aria-label="Aktifkan tema terang" aria-pressed="false">☾</button>
                <a class="login-button" href="logout.php">Keluar</a>
            </nav>
        </div>
    </header>

    <main id="dashboardMain" class="dashboard-main">
        <section class="dashboard-welcome">
            <div class="container dashboard-welcome-content">
                <div>
                    <span class="section-label">RUANG PENGEMBANGAN SISWA</span>
                    <h2>Selamat datang di perjalanan belajarmu.</h2>
                    <p>Pahami apa yang sudah kamu coba, pilih langkah berikutnya, dan evaluasi perkembanganmu secara bertahap.</p>
                </div>
                <a class="primary-button" href="assessment.php">Lanjutkan perjalanan <span aria-hidden="true">→</span></a>
            </div>
        </section>

        <div class="container dashboard-content">
            <p id="dashboardDataMessage" class="dashboard-error" role="alert" hidden></p>

            <section class="journey-overview" aria-labelledby="journeyTitle">
                <div class="dashboard-section-heading">
                    <div><span class="section-label">ALUR PENGEMBANGAN</span>
                        <h2 id="journeyTitle">Perjalananmu</h2>
                    </div>
                    <span id="journeyStatus" class="status-pill">Belum dimulai</span>
                </div>
                <ol class="dashboard-journey">
                    <li id="journeySkill"><span class="journey-icon">01</span>
                        <div><strong>Asesmen keterampilan</strong><small>Latihan berbasis soal dan rubrik</small></div><span class="journey-state">Belum</span>
                    </li>
                    <li id="journeyInterest"><span class="journey-icon">02</span>
                        <div><strong>Eksplorasi minat</strong><small>Pilihan aktivitas kariermu</small></div><span class="journey-state">Belum</span>
                    </li>
                    <li id="journeyGrowth"><span class="journey-icon">03</span>
                        <div><strong>Refleksi diri</strong><small>Sukarela, bukan diagnosis</small></div><span class="journey-state">Opsional</span>
                    </li>
                    <li id="journeyPlan"><span class="journey-icon">04</span>
                        <div><strong>Rencana dan evaluasi</strong><small>Target kecil yang bisa ditinjau</small></div><span class="journey-state">Belum</span>
                    </li>
                </ol>
            </section>

            <section class="dashboard-grid" aria-label="Ringkasan pengembangan">
                <article class="dashboard-panel skill-panel">
                    <div class="dashboard-section-heading">
                        <div><span class="section-label">SKILL INTELLIGENCE</span>
                            <h2>Peta keterampilan</h2>
                        </div>
                        <span class="panel-icon" aria-hidden="true">▥</span>
                    </div>
                    <p class="panel-description">Hasil asesmen keterampilan terakhir yang tersimpan di akunmu.</p>
                    <div id="dashboardSkills" class="dashboard-skills"></div>
                    <p id="skillEmpty" class="dashboard-empty" hidden>Belum ada hasil tersimpan. Kerjakan latihan keterampilan untuk melihat peta kemampuan awalmu.</p>
                    <a href="assessment.php" class="panel-link">Kerjakan latihan keterampilan <span aria-hidden="true">→</span></a>
                </article>

                <article class="dashboard-panel interest-panel">
                    <div class="dashboard-section-heading">
                        <div><span class="section-label">INTEREST INTELLIGENCE</span>
                            <h2>Minat kariermu</h2>
                        </div>
                        <span class="panel-icon" aria-hidden="true">⌖</span>
                    </div>
                    <p class="panel-description">Bidang yang paling banyak kamu pilih untuk dieksplorasi.</p>
                    <div id="dashboardInterests" class="interest-summary"></div>
                    <p id="interestEmpty" class="dashboard-empty" hidden>Asesmen minat belum diisi. Jawab skenario minat untuk menemukan bidang yang ingin kamu eksplorasi.</p>
                    <a href="assessment.php" class="panel-link">Eksplorasi minat <span aria-hidden="true">→</span></a>
                </article>

                <article class="dashboard-panel growth-panel">
                    <div class="dashboard-section-heading">
                        <div><span class="section-label">PERSONAL GROWTH</span>
                            <h2>Refleksi belajarmu</h2>
                        </div>
                        <span class="panel-icon" aria-hidden="true">✳</span>
                    </div>
                    <p id="dashboardReflection" class="reflection-copy">Refleksi bersifat pribadi dan sukarela. Kamu boleh melewatinya, dan tidak ada diagnosis psikologis.</p>
                    <a href="assessment.php" class="panel-link">Tinjau refleksi opsional <span aria-hidden="true">→</span></a>
                </article>

                <article class="dashboard-panel career-panel">
                    <div class="dashboard-section-heading">
                        <div><span class="section-label">CAREER INTELLIGENCE</span>
                            <h2>Arah dan rencanamu</h2>
                        </div>
                        <span class="panel-icon" aria-hidden="true">↗</span>
                    </div>
                    <div id="dashboardCareer" class="career-summary"></div>
                    <p id="careerEmpty" class="dashboard-empty" hidden>Pilih bidang yang ingin kamu eksplorasi, lalu susun target mingguan yang realistis.</p>
                    <a href="index.php#pembelajaran" class="panel-link">Buka rencana pengembangan <span aria-hidden="true">→</span></a>
                </article>
            </section>

            <section class="dashboard-ai" aria-labelledby="aiDashboardTitle">
                <div class="ai-dashboard-symbol" aria-hidden="true">✦</div>
                <div>
                    <span class="section-label">AI COACH</span>
                    <h2 id="aiDashboardTitle">Pendamping langkah berikutnya</h2>
                    <p>Diskusikan target belajar, tanyakan materi pemrograman,
                        dan dapatkan arahan belajar bersama AI Coach JagatEDU.</p>
                </div>
                <a class="secondary-button" href="chatbot.php">Buka AI Coach <span aria-hidden="true">→</span></a>
            </section>

            <section class="dashboard-next" aria-labelledby="nextStepsTitle">
                <div>
                    <span class="section-label">LANGKAH BERIKUTNYA</span>
                    <h2 id="nextStepsTitle">Perkembangan dibangun sedikit demi sedikit.</h2>
                    <p>Ulangi latihan setelah belajar, bandingkan hasilnya, dan minta masukan guru atau pembimbing praktik untuk mengecek kemampuan nyata.</p>
                </div>
                <a class="primary-button" href="index.php#fitur">Mulai / ulangi asesmen <span aria-hidden="true">→</span></a>
            </section>
        </div>
    </main>

    <section class="roadmap-section" id="dashboardRoadmap">
        <div class="roadmap-header">
            <div>
                <span class="section-label">PERSONAL LEARNING PLAN</span>
                <h2>Roadmap Belajarmu</h2>
                <p>
                    Ikuti aktivitas belajar yang direkomendasikan AI
                    berdasarkan hasil assessment-mu.
                </p>
            </div>

            <a href="roadmap.php" class="roadmap-link">
                Lihat Roadmap <span aria-hidden="true">→</span>
            </a>
        </div>

        <div id="roadmapSummary">
            <p>Memuat roadmap belajar...</p>
        </div>
    </section>

    <script>
        window.JAGATEDU_CSRF = <?= json_encode(jagatedu_csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
    <script src="js/dashboard.js"></script>
</body>

</html>