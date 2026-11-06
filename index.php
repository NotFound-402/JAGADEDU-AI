<?php

require_once __DIR__ . '/config/security.php';

jagatedu_start_session();
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>JAGATEDU AI | Smart Learning Platform</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/style/style.css">
    <link rel="stylesheet" href="/style/chat.css">

    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>

<body>

    <!-- NAVBAR -->
    <header class="navbar">
        <div class="container navbar-content">

            <a href="#beranda" class="brand">
                <div class="brand-icon">✿</div>

                <div>
                    <h1>JAGAT<span>EDU</span> AI</h1>
                    <p>Berakar pada Budaya, Berkembang dengan Teknologi</p>
                </div>
            </a>

            <button class="menu-toggle" id="menuToggle" aria-label="Buka menu">
                ☰
            </button>

            <nav class="nav-menu" id="navMenu">
                <a href="#beranda" class="active">Beranda</a>
                <a href="#fitur">Asesmen</a>
                <a href="#pembelajaran">Pembelajaran</a>
                <a href="#tentang">Tentang</a>
                <button class="theme-button" id="themeButton" aria-label="Ganti tema">☾</button>
                <a href="login.php" class="login-button">Masuk</a>
            </nav>

        </div>
    </header>


    <!-- HERO SECTION -->
    <main>

        <section class="hero" id="beranda">

            <div class="hero-overlay"></div>

            <div class="container hero-container">

                <div class="hero-content">

                    <div class="hero-label">
                        ✨ AI POWERED LEARNING PLATFORM
                    </div>

                    <h2>
                        Kenali Potensimu,
                        <span>Bangun Masa Depanmu!</span>
                    </h2>

                    <p>
                        Temukan kemampuan terbaikmu melalui teknologi
                        Artificial Intelligence yang menggabungkan
                        inovasi pendidikan dengan nilai kearifan lokal Bali.
                    </p>

                    <div class="hero-buttons">
                        <a href="assessment.php" class="primary-button">
                            🚀 Mulai Asesmen Sekarang
                            <span>→</span>
                        </a>
                    </div>

                    <div class="hero-info">
                        <div>
                            <strong>AI</strong>
                            <span>Smart Learning</span>
                        </div>

                        <div>
                            <strong>BALI</strong>
                            <span>Local Wisdom</span>
                        </div>

                        <div>
                            <strong>FUTURE</strong>
                            <span>Better Generation</span>
                        </div>
                    </div>

                </div>

                <div class="hero-decoration">
                    <div class="floating-card">
                        <div class="rocket-icon">🚀</div>
                        <h3>Your Future Starts Here</h3>
                        <p>
                            Temukan potensi dan kemampuan terbaikmu
                            untuk masa depan yang lebih cerah.
                        </p>
                    </div>
                </div>

            </div>

            <div class="hero-bottom"></div>

        </section>


        <!-- FEATURES SECTION -->
        <section class="features-section" id="fitur">

            <div class="container">

                <div class="section-heading">
                    <span>FITUR UNGGULAN</span>
                    <h2>Kenali Dirimu Lebih Dalam</h2>
                    <p>
                        Manfaatkan teknologi AI untuk menemukan
                        kemampuan dan mengembangkan potensimu.
                    </p>
                </div>

                <div class="features-grid">

                    <div class="feature-card">
                        <div class="feature-icon">🧠</div>
                        <h3>Asesmen Potensi</h3>
                        <p>
                            Kenali minat, bakat, dan kemampuanmu
                            melalui asesmen berbasis AI.
                        </p>
                        <a href="#masuk">Pelajari lebih lanjut →</a>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">📚</div>
                        <h3>Rekomendasi Belajar</h3>
                        <p>
                            Dapatkan rekomendasi jurusan dan
                            kompetensi sesuai hasil asesmen.
                        </p>
                        <a href="#pembelajaran">Pelajari lebih lanjut →</a>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🏯</div>
                        <h3>Nilai Budaya Bali</h3>
                        <p>
                            Pelajari filosofi dan kearifan lokal
                            Bali sebagai bagian dari pendidikan.
                        </p>
                        <a href="#tentang">Pelajari lebih lanjut →</a>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">📈</div>
                        <h3>Menuju Masa Depan</h3>
                        <p>
                            Tingkatkan keterampilan untuk
                            meraih cita-cita dan masa depanmu.
                        </p>
                        <a href="#pembelajaran">Pelajari lebih lanjut →</a>
                    </div>

                </div>

            </div>

        </section>


        <!-- PEMBELAJARAN SECTION -->
        <section class="learning-section" id="pembelajaran">

            <div class="container learning-content">

                <div class="learning-text">
                    <span class="section-label">PEMBELAJARAN CERDAS</span>

                    <h2>
                        Belajar Lebih Terarah
                        dengan Teknologi AI
                    </h2>

                    <p>
                        JAGATEDU AI membantu siswa memahami kemampuan
                        diri dan mendapatkan arahan pembelajaran
                        yang sesuai dengan kebutuhan masing-masing.
                    </p>

                    <ul>
                        <li>✓ Analisis kemampuan siswa</li>
                        <li>✓ Rekomendasi kompetensi</li>
                        <li>✓ Pengembangan potensi diri</li>
                        <li>✓ Integrasi kearifan lokal Bali</li>
                    </ul>

                    <a href="assessment.php" class="secondary-button">
                        Jelajahi Fitur →
                    </a>
                </div>

                <div class="learning-visual">
                    <div class="learning-card">
                        <div class="learning-card-icon">✨</div>
                        <span>AI LEARNING ASSISTANT</span>
                        <h3>Potensi Besarmu Dimulai dari Sini</h3>
                        <p>
                            Setiap siswa memiliki kemampuan
                            dan peluang untuk berkembang.
                        </p>

                        <div class="progress-item">
                            <span>Pengembangan Kompetensi</span>
                            <div class="progress-bar">
                                <div></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </section>


        <!-- LOCAL WISDOM SECTION -->
        <section class="wisdom-section" id="tentang">

            <div class="container wisdom-content">

                <span class="section-label">KEARIFAN LOKAL BALI</span>

                <h2>
                    Teknologi Modern,
                    <span>Nilai Budaya Tetap Terjaga.</span>
                </h2>

                <p>
                    JAGATEDU AI mengangkat nilai kearifan lokal Bali
                    sebagai inspirasi dalam menciptakan pendidikan
                    yang inovatif, berkarakter, dan berbudaya.
                </p>

                <div class="wisdom-grid">

                    <div class="wisdom-card">
                        <div>🌿</div>
                        <h3>Tri Hita Karana</h3>
                        <p>
                            Mengajarkan keharmonisan dalam kehidupan
                            manusia dan lingkungan.
                        </p>
                    </div>

                    <div class="wisdom-card">
                        <div>🪷</div>
                        <h3>Taksu</h3>
                        <p>
                            Mengembangkan potensi dan kualitas
                            diri untuk mencapai hasil terbaik.
                        </p>
                    </div>

                    <div class="wisdom-card">
                        <div>📖</div>
                        <h3>Widya</h3>
                        <p>
                            Menjadikan ilmu pengetahuan sebagai
                            dasar membangun generasi masa depan.
                        </p>
                    </div>

                </div>

            </div>

        </section>


        <!-- CTA SECTION -->
        <section class="cta-section" id="masuk">

            <div class="container cta-content">

                <span>YOUR JOURNEY STARTS HERE</span>

                <h2>
                    Siap Mengenali Potensi Terbaikmu?
                </h2>

                <p>
                    Mulai perjalanan belajarmu bersama JAGATEDU AI
                    dan persiapkan masa depan yang lebih baik.
                </p>

                <a href="#beranda" class="primary-button">
                    Mulai Sekarang →
                </a>

            </div>


            <!-- JAGATEDU AI CHATBOT -->
            <div class="chatbot-widget">

                <button id="chatToggle" class="chat-toggle">
                    ✨
                </button>

                <div id="chatWindow" class="chat-window">

                    <div class="chat-header">
                        <div>
                            <h3>JagatEDU AI</h3>
                            <span>● AI Assistant</span>
                        </div>

                        <button id="chatClose">&times;</button>
                    </div>

                    <div id="chatMessages" class="chat-messages">
                        <div class="chat-message ai-message">
                            Halo! 👋 Aku JagatEDU AI.

                            Ada yang ingin kamu diskusikan
                            tentang kemampuan, pembelajaran,
                            atau rencana masa depanmu?
                        </div>
                    </div>

                    <form id="chatForm" class="chat-input-area">

                        <input
                            type="text"
                            id="chatInput"
                            placeholder="Tulis pesanmu..."
                            maxlength="2000"
                            autocomplete="off"
                            required>

                        <button type="submit" id="chatSend">
                            ➤
                        </button>

                    </form>

                </div>

            </div>


        </section>

    </main>


    <!-- FOOTER -->
    <footer class="footer">

        <div class="container footer-content">

            <div>
                <h2>JAGAT<span>EDU</span> AI</h2>
                <p>
                    Berakar pada Budaya, Berkembang dengan Teknologi.
                </p>
            </div>

            <div class="footer-copy">
                © 2026 JAGATEDU AI. All Rights Reserved.
            </div>

        </div>

    </footer>


    <!-- JAVASCRIPT -->
    <script src="/js/script.js"></script>
    <script src="/js/chat.js"></script>

</body>

</html>