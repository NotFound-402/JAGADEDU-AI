<?php
require_once __DIR__ . '/config/security.php';

jagatedu_start_session();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | JAGATEDU AI</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="<?= htmlspecialchars(jagatedu_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <script>
        window.JAGATEDU_CSRF = <?= json_encode(jagatedu_csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>

    <!-- CSS -->
    <link rel="stylesheet" href="/style/login.css">

    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>

<body>

    <main class="login-page">

        <!-- BAGIAN KIRI -->
        <section class="login-left">

            <a href="index.php" class="brand">
                <div class="brand-icon">✿</div>

                <div>
                    <h1>JAGAT<span>EDU</span> AI</h1>
                    <p>Smart Learning Platform</p>
                </div>
            </a>

            <div class="welcome-content">

                <span class="welcome-label">
                    ✨ WELCOME TO JAGATEDU AI
                </span>

                <h2>
                    Belajar Cerdas,
                    <span>Raih Masa Depan Gemilang!</span>
                </h2>

                <p>
                    Temukan potensi terbaikmu melalui teknologi
                    Artificial Intelligence yang menggabungkan
                    inovasi pendidikan dengan kearifan lokal Bali.
                </p>

                <div class="welcome-features">

                    <div class="welcome-item">
                        <span>🧠</span>
                        <div>
                            <h3>Kenali Potensimu</h3>
                            <p>Temukan minat dan kemampuan terbaikmu.</p>
                        </div>
                    </div>

                    <div class="welcome-item">
                        <span>📚</span>
                        <div>
                            <h3>Belajar Lebih Terarah</h3>
                            <p>Dapatkan rekomendasi pembelajaran.</p>
                        </div>
                    </div>

                    <div class="welcome-item">
                        <span>🚀</span>
                        <div>
                            <h3>Persiapkan Masa Depan</h3>
                            <p>Kembangkan kompetensi dan keterampilan.</p>
                        </div>
                    </div>

                </div>

            </div>

            <div class="left-footer">
                © 2026 JAGATEDU AI | Education for Future
            </div>

        </section>


        <!-- BAGIAN KANAN -->
        <section class="login-right">

            <div class="login-card">

                <div class="mobile-brand">
                    <div class="brand-icon">✿</div>
                    <h2>JAGAT<span>EDU</span> AI</h2>
                </div>

                <div class="login-heading">

                    <h2>Selamat Datang!</h2>

                    <p>
                        Silakan masuk untuk memulai perjalanan
                        belajarmu bersama JAGATEDU AI.
                    </p>

                </div>


                <!-- FORM LOGIN -->
                <form id="loginForm" action="controllers/AuthController.php" method="POST">

                    <div class="form-group">

                        <label for="email">Email atau Username</label>

                        <div class="input-wrapper">
                            <span>✉</span>

                            <input
                                type="text"
                                id="email"
                                placeholder="Masukkan email atau username"
                                autocomplete="username"
                                required
                            >
                        </div>

                    </div>


                    <div class="form-group">

                        <div class="password-label">
                            <label for="password">Password</label>
                        </div>

                        <div class="input-wrapper">
                            <span>🔒</span>

                            <input
                                type="password"
                                id="password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                id="togglePassword"
                                class="toggle-password"
                            >
                                Lihat
                            </button>

                        </div>

                    </div>


                    <div class="form-options">

                        <label class="remember">
                            <input type="checkbox" id="remember">
                            Ingat saya
                        </label>

                        <a href="#" id="forgotPassword">
                            Lupa password?
                        </a>

                    </div>


                    <button type="submit" class="login-submit">
                        Masuk Sekarang
                        <span>→</span>
                    </button>

                    <div id="message" class="message"></div>

                </form>


                <div class="divider">
                    <span>ATAU</span>
                </div>


                <div class="register-text">
                    Belum memiliki akun?
                    <a href="#" id="registerLink">Daftar Sekarang</a>
                </div>


                <div class="login-note">
                    🌿 Belajar dengan Teknologi, Berkembang dengan Budaya.
                </div>

            </div>

        </section>

    </main>


    <!-- JAVASCRIPT -->
    <script src="/js/login.js"></script>

</body>
</html>