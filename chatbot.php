<?php

require_once __DIR__ . '/config/security.php';

jagatedu_start_session();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

?>  

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AI Coach JAGATEDU AI membantu siswa menyusun langkah belajar dan eksplorasi karier.">
    <meta name="theme-color" content="#0b1f3a">
    <meta name="csrf-token" content="<?= htmlspecialchars(jagatedu_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <title>AI Coach | JAGATEDU AI</title>
    <link rel="stylesheet" href="/style/chatbot.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>

<body class="chatbot-page">
    <a class="skip-link" href="#chatMain">Lewati ke konten</a>
    <header class="navbar">
        <div class="container navbar-content">
            <a href="index.php" class="brand" aria-label="JAGATEDU AI, ke beranda">
                <div class="brand-icon" aria-hidden="true">✿</div>
                <div>
                    <h1>JAGAT<span>EDU</span> AI</h1>
                    <p>Student Development Intelligence</p>
                </div>
            </a>
            <button class="menu-toggle" id="chatMenuToggle" type="button" aria-label="Buka menu" aria-controls="chatNav" aria-expanded="false">☰</button>
            <nav class="nav-menu" id="chatNav" aria-label="Navigasi AI Coach">
                <a href="dashboard.php">Dashboard</a>
                <a href="index.php#fitur">Asesmen</a>
                <a href="index.php#pembelajaran">Rencana</a>
                <a class="active" href="chatbot.php" aria-current="page">AI Coach</a>
                <button class="theme-button" id="chatThemeButton" type="button" aria-label="Aktifkan tema terang" aria-pressed="false">☾</button>
            </nav>
        </div>
    </header>

    <main id="chatMain" class="chat-main">
        <div class="container chat-layout">
            <aside class="chat-sidebar">
                <a class="chat-back-link" href="dashboard.php">← Kembali ke dashboard</a>
                <div class="coach-profile">
                    <span class="coach-profile-icon" aria-hidden="true">✦</span>
                    <div><span class="section-label">JAGATEDU AI</span>
                        <h2>AI Coach</h2>
                    </div>
                </div>
                <p class="chat-sidebar-intro">Teman untuk membantu kamu memahami hasil latihan dan membagi tujuan jadi langkah kecil.</p>

                <section class="chat-context" aria-labelledby="contextTitle">
                    <span class="section-label">KONTEKS BELAJARMU</span>
                    <h3 id="contextTitle">Ringkasan asesmen</h3>
                    <div id="chatContext">
                        <p class="context-empty">Belum ada ringkasan tersimpan. Coach tetap bisa memberi saran umum.</p>
                    </div>
                    <a class="context-link" href="index.php#fitur">Isi asesmen <span aria-hidden="true">→</span></a>
                </section>

                <div class="chat-history">
                    <div class="chat-history-header">
                        <span class="section-label">RIWAYAT</span>
                        <button id="newChatBtn" class="chat-history-new" type="button">+ Baru</button>
                    </div>
                    <div id="conversationList" class="conversation-list" aria-live="polite"></div>
                </div>

                <div class="chat-safety">
                    <strong>Ruang aman dan batasan</strong>
                    <p>Refleksi bersifat sukarela. Coach bukan psikolog, tidak mendiagnosis, dan tidak menggantikan guru BK atau tenaga profesional.</p>
                </div>
            </aside>

            <section class="chat-workspace" aria-labelledby="chatTitle">
                <div class="chat-workspace-header">
                    <div>
                        <span class="section-label">PENDAMPING BELAJAR</span>
                        <h1 id="chatTitle">Apa yang ingin kamu bahas?</h1>
                        <p>Mulai dari tujuan, keterampilan, atau tantangan belajar yang ingin diurai.</p>
                    </div>
                    <span class="coach-online"><span></span> AI aktif</span>
                </div>

                <div class="chat-suggestions" aria-label="Contoh pertanyaan">
                    <button type="button" class="chat-suggestion" data-chat-prompt="Bantu saya membagi target belajar menjadi langkah kecil.">Bagi target jadi langkah kecil</button>
                    <button type="button" class="chat-suggestion" data-chat-prompt="Keterampilan apa yang perlu saya latih lagi?">Keterampilan yang perlu dilatih</button>
                    <button type="button" class="chat-suggestion" data-chat-prompt="Ceritakan pilihan karier yang saya eksplorasi.">Bahas arah karier saya</button>
                </div>

                <div id="chatMessages" class="chat-message-list" role="log" aria-live="polite" aria-relevant="additions text">
                    <article class="chat-bubble coach-bubble">
                        <span class="bubble-avatar" aria-hidden="true">✦</span>
                        <div><strong>AI Coach <span>· Real-time</span></strong>
                            <p>Hai! Saya siap membantu menyusun langkah belajar dan mengevaluasi targetmu. Apa yang ingin kamu bahas?</p>
                        </div>
                    </article>
                </div>

                <form id="chatForm" class="chat-composer">
                    <label class="sr-only" for="chatInput">Tulis pesan untuk AI Coach</label>
                    <textarea id="chatInput" name="message" rows="2" maxlength="500" placeholder="Tulis pertanyaan atau target belajarmu..." required></textarea>
                    <div class="chat-composer-footer">
                        <span>Hindari membagikan nama, kontak, atau informasi pribadi.</span>
                        <button id="chatSend" class="chat-send-button" type="submit">Kirim <span aria-hidden="true">↑</span></button>
                    </div>
                </form>
                <p id="chatStatus" class="sr-only" role="status" aria-live="polite"></p>
            </section>
        </div>
    </main>
    <script>
        window.JAGATEDU_CSRF = <?= json_encode(jagatedu_csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
    <script src="js/chat.js"></script>
</body>

</html>