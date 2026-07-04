<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
date_default_timezone_set('Asia/Jakarta');

// DATABASE LIKES SEDERHANA (BERBASIS FILE JSON)
$likesFile = 'likes_data.json';
if (!file_exists($likesFile) || filesize($likesFile) == 0) {
    file_put_contents($likesFile, json_encode([]));
}

// MEMASTIKAN HASIL DECODE SELALU BERBENTUK ARRAY
$likesData = json_decode(file_get_contents($likesFile), true);
if (!is_array($likesData)) {
    $likesData = [];
}

// HITUNG TOTAL LIKES KESELURUHAN UNTUK NAVIGASI BAWAH
$totalGlobalLikes = array_sum($likesData);

// PROSES FILTER LIKE VIA AJAX
if (isset($_POST['action']) && $_POST['action'] === 'like_photo') {
    $photoId = $_POST['photo_id'];
    if (!isset($likesData[$photoId])) {
        $likesData[$photoId] = 0;
    }
    $likesData[$photoId]++;
    file_put_contents($likesFile, json_encode($likesData));
    echo json_encode(['success' => true, 'new_likes' => $likesData[$photoId], 'total_global' => array_sum($likesData)]);
    exit;
}

$photoDir = 'backup_photographer/';
if (!file_exists($photoDir)) {
    mkdir($photoDir, 0755, true);
}

$allowedExtensions = '*.{jpg,jpeg,png,JPG,JPEG,PNG}';
$photos = glob($photoDir . $allowedExtensions, GLOB_BRACE);

if ($photos && is_array($photos)) {
    usort($photos, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
} else {
    $photos = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>The Eternal Vows — Ilham & Tarisa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,600;1,400&display=swap" rel="stylesheet">

    <style>
        :root {
            --ig-bg: #000000;
            --ig-text: #f5f5f5;
            --ig-gray: #737373;
            --ig-border: #262626;
            --love-red: #ff3040;
            --luxury-gold: #d4af37;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--ig-bg);
            color: var(--ig-text);
            min-height: 100vh;
            padding-bottom: 80px;
            overflow-x: hidden;
            -webkit-user-select: none;
            user-select: none;
        }

        /* Top Bar */
        .profile-top-bar {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--ig-border);
            position: sticky;
            top: 0;
            background: rgba(0,0,0,0.95);
            backdrop-filter: blur(12px);
            z-index: 900;
        }

        .profile-top-bar .username-title {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: -0.2px;
            color: #ffffff;
        }

        .profile-top-bar .verified-badge {
            color: #0095f6;
            font-size: 13px;
        }

        .profile-top-bar .luxury-tag {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 13px;
            color: var(--luxury-gold);
            letter-spacing: 0.5px;
        }

        /* Highlights / Stories Section */
        .highlights-container {
            display: flex;
            gap: 18px;
            padding: 20px 15px;
            overflow-x: auto;
            border-bottom: 1px solid var(--ig-border);
            scroll-behavior: smooth;
        }
        .highlights-container::-webkit-scrollbar { display: none; }

        .highlight-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            flex-shrink: 0;
        }

        .highlight-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            border: 2px solid var(--ig-border);
            padding: 3px;
            background: var(--ig-bg);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .highlight-item.active .highlight-circle {
            border-color: #a8a8a8;
        }

        .highlight-inner-art {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: #161616;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 16px;
            color: #fff;
            object-fit: cover;
        }

        .highlight-label {
            font-size: 11px;
            color: var(--ig-text);
            max-width: 75px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Tab Switcher Icons */
        .ig-tabs-divider {
            display: flex;
            justify-content: space-around;
            align-items: center;
            border-bottom: 1px solid var(--ig-border);
            background: var(--ig-bg);
            margin-bottom: 2px;
        }

        .tab-icon-btn {
            flex: 1;
            padding: 12px 0;
            text-align: center;
            cursor: pointer;
            opacity: 0.4;
            border-bottom: 1px solid transparent;
        }

        .tab-icon-btn.active {
            opacity: 1;
            border-bottom: 1px solid #ffffff;
        }

        /* RESPONSIVE LAYOUT CONTAINER */
        .grid-wrapper {
            max-width: 935px;
            margin: 0 auto;
            padding: 0;
        }

        /* Grid Otomatis */
        .instagram-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 3px;
            padding: 0;
        }

        .grid-post-item {
            position: relative;
            width: 100%;
            aspect-ratio: 1 / 1;
            background-color: #121212;
            cursor: pointer;
            overflow: hidden;
        }

        .grid-post-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .grid-badge {
            position: absolute;
            top: 8px; right: 8px;
            font-size: 11px;
            background: rgba(0, 0, 0, 0.6);
            padding: 2px 6px;
            border-radius: 4px;
            color: #fff;
        }

        /* MODAL GAYA FULL-SCREEN SEPERTI REELS */
        .lightbox-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: #000000;
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .lightbox-card {
            position: relative;
            background: #000;
            width: 100%;
            height: 100%;
            max-width: 450px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .lightbox-header {
            position: absolute;
            top: 0; left: 0; width: 100%;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(to bottom, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);
            z-index: 10;
        }

        .lightbox-close {
            font-size: 28px;
            cursor: pointer;
            color: #ffffff;
            line-height: 1;
            text-shadow: 0 1px 4px rgba(0,0,0,0.5);
        }

        .lightbox-img-box {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            background: #000;
        }

        .lightbox-img-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Bagian Konten & Tombol Melayang di Bawah */
        .lightbox-overlay-content {
            position: absolute;
            bottom: 0; left: 0; width: 100%;
            padding: 30px 20px 40px 20px;
            background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.6) 50%, rgba(0,0,0,0) 100%);
            z-index: 10;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .lightbox-user-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .lightbox-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--luxury-gold);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
            font-weight: bold;
            color: #000;
        }

        .lightbox-caption-txt {
            color: #ffffff;
            font-size: 14px;
            line-height: 1.4;
            text-shadow: 0 1px 3px rgba(0,0,0,0.6);
        }

        .lightbox-caption-txt strong {
            margin-right: 6px;
        }

        .lightbox-actions-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 4px;
        }

        .left-action-group {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .like-heart-btn {
            background: transparent;
            border: none;
            font-size: 28px;
            color: #ffffff;
            cursor: pointer;
            filter: drop-shadow(0 1px 3px rgba(0,0,0,0.6));
            transition: transform 0.1s ease;
        }
        .like-heart-btn:active { transform: scale(1.2); }

        .like-heart-btn.active {
            color: var(--love-red);
        }

        .download-btn-secure {
            text-decoration: none;
            font-size: 24px;
            color: #ffffff;
            filter: drop-shadow(0 1px 3px rgba(0,0,0,0.6));
        }

        .likes-count-txt {
            font-size: 13px;
            font-weight: 600;
            color: #e5e5e5;
        }

        /* FIXED BOTTOM NAVBAR */
        .bottom-nav-bar {
            position: fixed;
            bottom: 0; left: 0; width: 100%;
            background: #000;
            border-top: 1px solid var(--ig-border);
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 14px 0;
            z-index: 950;
        }

        .nav-icon {
            font-size: 22px;
            color: var(--ig-text);
            cursor: pointer;
            position: relative;
        }

        .like-badge-counter {
            position: absolute;
            top: -6px; right: -10px;
            background: var(--love-red);
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 10px;
            border: 1px solid #000;
        }

        /* Toast Popup */
        .toast-popup {
            position: fixed;
            bottom: 90px; left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #262626;
            color: #fff; padding: 10px 20px;
            border-radius: 20px; font-size: 12px; z-index: 5000;
            transition: transform 0.3s ease, opacity 0.3s;
            opacity: 0;
            white-space: nowrap;
        }
        .toast-popup.visible { transform: translateX(-50%) translateY(0); opacity: 1; }

        .empty-msg {
            grid-column: 1 / -1;
            text-align: center;
            color: var(--ig-gray);
            padding: 60px 20px;
            font-size: 13px;
        }
    </style>
</head>
<body>

    <!-- TOP HEADER BAR -->
    <div class="profile-top-bar">
        <div class="username-title">
            <span>Wedding Gallery</span>
            <span class="verified-badge"></span>
        </div>
        <div class="luxury-tag">The Eternal Vows</div>
    </div>

    <div class="grid-wrapper">
        <!-- HIGHLIGHTS FILTER CATEGORY -->
        <div class="highlights-container">
            <div class="highlight-item active" onclick="applyGridFilter('all', this)">
                <div class="highlight-circle"><div class="highlight-inner-art" style="font-size: 10px; font-weight:700; letter-spacing: 0.5px;">ALL</div></div>
                <div class="highlight-label">All Moments</div>
            </div>
            <div class="highlight-item" onclick="applyGridFilter('akad', this)">
                <div class="highlight-circle"><div class="highlight-inner-art">💍</div></div>
                <div class="highlight-label">The Solemnity</div>
            </div>
            <div class="highlight-item" onclick="applyGridFilter('resepsi', this)">
                <div class="highlight-circle"><div class="highlight-inner-art">✨</div></div>
                <div class="highlight-label">The Reception</div>
            </div>
            <div class="highlight-item" onclick="applyGridFilter('after', this)">
                <div class="highlight-circle"><div class="highlight-inner-art">🎉</div></div>
                <div class="highlight-label">The After-Party</div>
            </div>
        </div>

        <!-- TABS DIVIDER -->
        <div class="ig-tabs-divider">
            <div class="tab-icon-btn active">🔳</div>
            <div class="tab-icon-btn">🎬</div>
            <div class="tab-icon-btn">👤</div>
        </div>

        <!-- MAIN RESPONSIVE GRID -->
        <div class="instagram-grid" id="photoGridContainer">
            <?php if (empty($photos)): ?>
                <div class="empty-msg">Awaiting the digital frames to load...</div>
            <?php else: ?>
                <?php foreach ($photos as $index => $photo): ?>
                    <?php 
                        $photoId = md5($photo);
                        $currentLikes = isset($likesData[$photoId]) ? $likesData[$photoId] : 0;
                        $time = date("H:i", filemtime($photo));
                        $hour = (int)date("H", filemtime($photo));

                        if ($hour >= 6 && $hour <= 11) { $tag = 'akad'; }
                        elseif ($hour > 11 && $hour <= 17) { $tag = 'resepsi'; }
                        else { $tag = 'after'; }

                        $webPath = htmlspecialchars($photo);
                        $fileName = basename($photo);
                    ?>
                    <div class="grid-post-item" data-category="<?= $tag ?>" onclick="openImageDetail('<?= $webPath ?>', '<?= $photoId ?>', '<?= $currentLikes ?>', '<?= $time ?>', '<?= $fileName ?>')">
                        <img src="<?= $webPath ?>" alt="Gallery Image">
                        <div class="grid-badge">🕒 <?= $time ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- NEW FULL-SCREEN MODAL LIKE INSTAGRAM-TESTS-FULL-SCREEN-SCROLLABLE-FEED.JPG -->
    <div class="lightbox-overlay" id="detailLightbox" onclick="closeImageDetail(event)">
        <div class="lightbox-card">
            <div class="lightbox-header">
                <span style="font-size: 14px; font-weight:700; color: #fff; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">Exclusive View</span>
                <span class="lightbox-close" onclick="forceCloseDetail()">&times;</span>
            </div>

            <div class="lightbox-img-box">
                <img id="modalTargetImg" src="" alt="View Post">
            </div>

            <div class="lightbox-overlay-content">
                <div class="lightbox-user-row">
                    <div class="lightbox-user-avatar">I</div>
                    <span style="font-weight: 700; font-size: 14px; color: #fff;">ilham.tarisa.chronicles</span>
                    <span style="color: #0095f6; font-size: 12px;">✓</span>
                </div>

                <div class="lightbox-caption-txt" id="modalDynamicCaption">
                    <!-- Teks diisi otomatis oleh JS -->
                </div>

                <div class="lightbox-actions-row">
                    <div class="left-action-group">
                        <button class="like-heart-btn" id="modalLikeBtn" onclick="sendLikeActionToServer()">♥</button>
                        <a href="" id="modalDownloadBtn" download="" class="download-btn-secure">📥</a>
                    </div>
                    <div class="likes-count-txt"><span id="modalLikesCount">0</span> likes</div>
                </div>
            </div>
        </div>
    </div>

    <!-- FIXED BOTTOM NAVBAR -->
    <div class="bottom-nav-bar">
        <div class="nav-icon" onclick="window.scrollTo({top:0, behavior:'smooth'})">🏠</div>
        <div class="nav-icon" onclick="triggerNotification('🔍 Search system is locked.')">🔍</div>
        <div class="nav-icon" onclick="triggerNotification('📸 Exclusive access for official photographer.')">＋</div>
        <div class="nav-icon" onclick="showTotalEngagement()">❤️ <span class="like-badge-counter" id="globalLikeBadge"><?= $totalGlobalLikes ?></span></div>
        <div class="nav-icon" onclick="triggerNotification('✨ The Celebration of Ilham & Tarisa')">👤</div>
    </div>

    <!-- NOTIFICATION SYSTEM -->
    <div class="toast-popup" id="appToast">Notifikasi</div>

    <script>
        let currentActivePhotoId = '';

        // 100 BANK CAPTION SUPER ULTRA VARIATIF (ENGGAK PELIT LAGI BRO!)
        const captionList = [
            // --- KATEGORI 1: DOA ISLAMI (1-20) ---
            "Baarakallahu laka wa baaraka 'alaika wa jama'a bainakuma fii khair. Selamat menempuh hidup baru Ilham & Tarisa! ✨",
            "Semoga Allah SWT menyempurnakan kebahagiaan kalian dan menjadikan pernikahan ini penuh ketenteraman serta keberkahan. 🤲❤️",
            "Momen penuh kesakralan yang diabadikan pada [TIME] WIB. Semoga menjadi keluarga sakinah, mawaddah, warahmah.",
            "Semoga awal perjalanan ibadah terpanjang ini senantiasa dituntun oleh ridha dan kasih sayang Allah SWT. 💍🕊️",
            "Satu akad, berjuta doa. Semoga ikatan suci ini kokoh terjalin hingga ke jannah-Nya nanti. Amin. ✨",
            "Masya Allah, indahnya janji suci yang terucap. Semoga Allah melimpahkan rezeki batin dan lahir yang berkah.",
            "Selamat menempuh lembaran baru yang halal. Semoga Allah menyatukan hati kalian dalam ketaatan yang tulus. 🤲",
            "Doa terbaik untuk Ilham & Tarisa. Semoga cinta kalian senantiasa bernilai ibadah di hadapan Allah SWT.",
            "Barakallah. Semoga hari-hari esok diisi dengan kesabaran, kedamaian, dan limpahan rahmat-Nya yang tak putus.",
            "Diabadikan pada pukul [TIME] WIB, sebuah ikatan suci kokoh terucap demi menyempurnakan separuh agama.",
            "Semoga Allah memberkahi keturunan kalian kelak dan menjadikan kalian pasangan yang saling menuntun ke surga.",
            "Selamat menempuh hidup baru. Jadikan Al-Quran dan Sunnah sebagai fondasi utama nahkoda rumah tangga kalian.",
            "Ikatan suci ini telah sah. Semoga cinta kalian kuat melewati badai dan manisnya kehidupan dengan penuh syukur. ❤️",
            "Pernikahan yang berkah dimulai dengan niat yang suci. Selamat atas bersatunya Ilham & Tarisa!",
            "Semoga Allah melembutkan hati kalian satu sama lain dan meluaskan kesabaran dalam meniti jalan takdir.",
            "Aura kebahagiaan pengantin baru yang terekam pada pukul [TIME] WIB. Semoga sakinah menyelimuti hati selalu.",
            "Selamat menempuh perjalanan cinta yang diridhai Allah SWT. Semoga selalu harmonis dan penuh berkah. ✨",
            "Sebuah komitmen suci di hadapan Sang Pencipta. Semoga cinta ini bertahan, tumbuh, dan mekar selamanya.",
            "Semoga rezeki mengalir deras seiring bersatunya dua hati yang siap saling melengkapi dalam ibadah.",
            "Selamat meniti hari-hari baru bersama pasangan halal pilihan hati. Semoga Allah menjaga keharmonisan kalian.",

            // --- KATEGORI 2: MODERN & INTERNATIONAL WISHES (21-40) ---
            "Wishing you a lifetime of love, laughter, and endless happiness. Happy Wedding Ilham & Tarisa! 🎉💖",
            "May your love story look as timeless and beautiful as this frozen moment in time. Captured at [TIME] WIB. 🌟",
            "Here’s to love, laughter, and happily ever after! Congratulations on your special day! 🥂✨",
            "Two hearts blending into one beautiful journey. Cheers to a wonderful future together!",
            "May the love you share today grow deeper and stronger as you grow old together. Happy Wedding! 🕊️",
            "Congratulations to the perfect match! May your days be filled with joy and pure magic.",
            "To love, honor, and cherish. Wishing you both a spectacular life journey together as husband and wife.",
            "Spotted at [TIME] WIB, a glimpse of pure elegance and genuine love. Congratulations, you two!",
            "May your home be filled with sunshine, your hearts filled with love, and your life filled with joy.",
            "Best wishes on this wonderful journey, as you build your new lives together. Stay beautiful!",
            "May this new chapter of your life brings you ultimate professional success and profound personal happiness. 🥂",
            "Two beautiful souls, one magnificent bond. Wishing you nothing but endless memories from this day forward.",
            "Warmest congratulations on your wedding day. You guys look absolutely stunning together! 😍🌟",
            "A magnificent view captured nicely at [TIME] WIB. May the spark of your love burn brightly forever.",
            "Congratulations on tying the knot! May your life together be a great adventure filled with laughter.",
            "Cheers to the newlyweds! Wishing you a life filled with love, prosperity, and endless companionship.",
            "May your wedding day be just the beginning of a lifetime full of happy memories and shared dreams.",
            "Sending you lots of love and good vibes on your wedding day. You deserve all the happiness in the world!",
            "You two make magic together. May this magical love never fade away. Happy Wedding! ✨💎",
            "Through every season of life, may your love for each other shine brighter than any star in the sky.",

            // --- KATEGORI 3: PUITIS, ROMANTIS & MENYENTUH (41-60) ---
            "Dua insan, satu tujuan, beribu kebahagiaan. Saling melengkapi dan menjaga dalam ikatan yang abadi. 💖",
            "Hari bahagia yang menjadi gerbang pembuka dari jutaan lembar cerita indah selamanya. 📖✨",
            "Cinta sejati tidak dihitung dari lamanya waktu, melainkan dari kedalaman komitmen yang diikrarkan hari ini.",
            "Tatap penuh cinta yang terekam pada pukul [TIME] WIB. Bukti nyata bahwa jodoh terbaik selalu datang di waktu terbaik.",
            "Menatap masa depan bersama dengan penuh keyakinan. Selamat mengarungi samudera kehidupan baru!",
            "Biarkan foto ini berbicara tentang bahagia yang tak sempat terucap oleh kata-kata. 💫",
            "Satu detak jantung, dua jiwa yang selaras. Selamat menempuh perjalanan panjang yang penuh keindahan.",
            "Takdir mempertemukan, cinta menyatukan, dan komitmen mengabadikan. Happy Wedding Ilham & Tarisa!",
            "Senyuman tulus yang terbingkai rapi pada [TIME] WIB, melukiskan ketenangan jiwa yang telah menemukan pelabuhannya.",
            "Di bawah langit saksi sejarah, dua cerita kini melebur menjadi satu kisah yang epik dan romantis.",
            "Sebab dicintai adalah hadiah, dan mencintai adalah komitmen. Selamat menjaga hadiah terindah dari Tuhan.",
            "Setiap detik yang berputar sejak pukul [TIME] WIB hari ini, menjadi saksi lahirnya legenda cinta baru.",
            "Semoga cinta kalian seperti lingkaran cincin pernikahan; tak berujung, tak berpangkal, dan selalu bernilai tinggi.",
            "Menemukan seseorang yang memahami duniamu adalah berkah terindah. Jaga erat tangan satu sama lain, ya!",
            "Selamat menulis bab pertama dari buku kehidupan kalian yang luar biasa. Selamat berbahagia!",
            "Sebuah tatapan yang mengandung sejuta arti kebahagiaan mendalam. Langgeng sampai kakek nenek! 💕",
            "Cinta bukan tentang mencari kesempurnaan, tapi tentang menerima ketidaksempurnaan bersama dengan indah.",
            "Sejak waktu menunjukkan [TIME] WIB, dunia tahu bahwa kalian diciptakan untuk saling melengkapi satu sama lain.",
            "Bukan sekadar pesta satu hari, melainkan perjalanan saling menyayangi seumur hidup. Selamat berbahagia!",
            "Meniti jalan takdir bergandengan tangan, melangkah selaras menciptakan harmoni melodi cinta yang syahdu.",

            // --- KATEGORI 4: INTERAKTIF, KASUAL & PUJI ESTETIKA (61-80) ---
            "Momen ini terlalu estetik untuk dilewatkan begitu saja! Ketuk ikon hati di bawah jika kamu setuju. ❤️",
            "Keindahan sudut kamera berpadu sempurna dengan rona bahagia pengantin. Sempurna! ✨",
            "Satu kata buat frame ini: Magis! Abadikan momen indah ini dengan menekan tombol unduh. 📥",
            "Terekam apik pada [TIME] WIB. Terima kasih telah membagikan energi kebahagiaan luar biasa ini kepada kami.",
            "Setiap pixel dari foto ini memancarkan aura cinta yang mahal dan berkelas. Gorgeous! 😍🏆",
            "Bantu kami mengumpulkan energi apresiasi cinta! Leave a token of love by tapping the heart icon.",
            "Memori berharga yang pantas disimpan selamanya. Sentuh tanda cinta dan simpan ceritanya. ✨",
            "Jujur, foto yang ini cakepnya keterlaluan! Sudut cahayanya pas banget merekam senyum bahagia mereka. 🔥",
            "Visual pengantin hari ini bener-bener gak ada tandingan. Anggun, gagah, dan penuh dengan aura kemewahan.",
            "Frame favorit sejauh ini! Berapa rating yang pas buat foto estetik di jam [TIME] WIB ini menurut kalian? ⭐",
            "Definisi 'King and Queen of the Day'. Tombol download aktif buat kamu yang mau simpan foto berkualitas tinggi ini.",
            "Photographer-nya pinter banget ambil momen krusial ini. Jangan lupa tinggalkan jejak suka di bawah, ya!",
            "Suasana hangat, vibes mahal, dan senyuman manis terekam sempurna. Gak bosen-bosen dilihatnya!",
            "Stop scrolling! Mari sejenak mengagumi keindahan estetika visual pernikahan Ilham & Tarisa. ✨👑",
            "Foto ini mengandung kadar kebahagiaan yang sangat tinggi. Awas ikutan senyum-senyum sendiri melihatnya!",
            "Detail busana, riasan wajah, dan tatapan mata semuanya berpadu dengan sangat estetik di jam [TIME] WIB.",
            "Siap-siap penuhi galeri handphone kalian dengan foto-foto sekelas majalah internasional ini. Unduh sekarang!",
            "Siapa sih yang gak baper lihat frame se-uwu ini? Kirimkan cinta kalian sekarang juga lewat tombol di bawah. ❤️",
            "Estetika kelas tinggi yang abadi. Sebuah mahakarya dokumentasi pernikahan modern abad ini.",
            "Tertangkap kamera di pukul [TIME] WIB. Pancaran kebahagiaan murni yang bikin siapa saja adem melihatnya.",

            // --- KATEGORI 5: HARAPAN BAIK & EMOSIONAL (81-100) ---
            "Selamat mengemban amanah baru sebagai suami istri. Semoga saling menguatkan di kala suka maupun duka. 🤝✨",
            "Semoga setiap langkah kaki kalian selalu membawa keberkahan dan kebaikan bagi orang-orang di sekitar.",
            "Selamat atas pernikahan kalian. Harapan terbaik kami agar kebahagiaan hari ini terus bertahan selamanya.",
            "Hari ini adalah awal dari selamanya. Semoga cinta kalian tidak pernah mengenal kata bosan dan lelah.",
            "Terabadikan indah di jam [TIME] WIB. Semoga rumah tangga kalian dihiasi tawa anak-anak yang shalih/shalihah kelak.",
            "Selamat menempuh hidup baru! Semoga kalian menjadi rekan satu tim terbaik dalam menghadapi petualangan hidup.",
            "Harapan kami, semoga cinta Ilham & Tarisa menjadi inspirasi bagi banyak orang tentang kesetiaan sejati.",
            "Semoga rumah baru kalian dipenuhi dengan kedamaian, pengertian, dan rasa saling menghargai yang tinggi.",
            "Selamat menempuh babak baru. Semoga cinta kalian selalu segar seperti embun pagi hari. 🌸✨",
            "Di balik foto menakjubkan pada pukul [TIME] WIB ini, tersimpan harapan jutaan doa dari kami semua untuk kalian.",
            "Semoga kalian berdua bisa menua bersama dalam pelukan kasih sayang yang tidak pernah berkurang sedikit pun.",
            "Selamat atas komitmen seumur hidup ini. Semoga selalu ada jalan keluar yang penuh kelembutan di setiap masalah.",
            "Jadikan pernikahan ini tempat ternyaman untuk pulang dari segala lelahnya dunia luar. Selamat berbahagia!",
            "Semoga tawa riang yang terekam pada jam [TIME] WIB ini terus bergema di ruang tamu rumah kalian selamanya.",
            "Selamat menikah, sahabat terbaik! Doa kami menyertai setiap jengkal langkah perjalanan barumu.",
            "Semoga badai sekencang apa pun tak mampu menggoyahkan pondasi cinta yang kalian bangun hari ini.",
            "Selamat mengarungi samudera pernikahan. Jadilah kompas terbaik bagi satu sama lain saat arah mulai buram.",
            "Keindahan yang hakiki adalah saat dua orang berkomitmen menjaga satu janji suci. Salut untuk Ilham & Tarisa! 👏❤️",
            "Semoga pernikahan ini membawa ketenteraman jiwa yang belum pernah kalian rasakan sebelumnya. Amin.",
            "Captured beautifully at [TIME] WIB. Selamat menikmati masa-masa indah menjadi sepasang pengantin baru!"
        ];

        function getRandomCaption(time) {
            const randomIndex = Math.floor(Math.random() * captionList.length);
            // Mengganti [TIME] dengan waktu real jam menit file foto
            return captionList[randomIndex].replace('[TIME]', time);
        }

        // PROTEKSI LOCK SYSTEM ANTI KLIK KANAN
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            triggerNotification("🔒 Copyright protected. Please use the official 📥 button.");
        });

        document.addEventListener('keydown', function(e) {
            if (e.keyCode == 123 || (e.ctrlKey && e.shiftKey && (e.keyCode == 73 || e.keyCode == 74)) || (e.ctrlKey && e.keyCode == 85)) {
                e.preventDefault();
            }
        });

        function triggerNotification(msg) {
            const toast = document.getElementById('appToast');
            toast.innerText = msg;
            toast.classList.add('visible');
            setTimeout(() => { toast.classList.remove('visible'); }, 2500);
        }

        function showTotalEngagement() {
            const total = document.getElementById('globalLikeBadge').innerText;
            triggerNotification(`🎉 Beautiful responses! ${total} global appreciation points collected.`);
        }

        // STORIES FILTER ENGINE
        function applyGridFilter(category, element) {
            const items = document.querySelectorAll('.highlight-item');
            items.forEach(i => i.classList.remove('active'));
            element.classList.add('active');

            const posts = document.querySelectorAll('.grid-post-item');
            posts.forEach(post => {
                if (category === 'all' || post.getAttribute('data-category') === category) {
                    post.style.display = 'block';
                } else {
                    post.style.display = 'none';
                }
            });
        }

        // DETAILED LIGHTBOX MANAGER
        function openImageDetail(src, photoId, likes, time, filename) {
            currentActivePhotoId = photoId;
            document.getElementById('modalTargetImg').src = src;
            document.getElementById('modalLikesCount').innerText = likes;

            // Pengacakan dari 100 array caption di atas dilakukan secara berkala saat foto di-klik
            document.getElementById('modalDynamicCaption').innerHTML = getRandomCaption(time);

            const dlBtn = document.getElementById('modalDownloadBtn');
            dlBtn.href = src;
            dlBtn.setAttribute('download', filename);

            const likeBtn = document.getElementById('modalLikeBtn');
            if (localStorage.getItem('liked_' + photoId)) {
                likeBtn.classList.add('active');
            } else {
                likeBtn.classList.remove('active');
            }

            document.getElementById('detailLightbox').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeImageDetail(e) {
            if (e.target.id === 'detailLightbox') { forceCloseDetail(); }
        }
        function forceCloseDetail() {
            document.getElementById('detailLightbox').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // SERVER AJAX INTERACTION LIKES
        function sendLikeActionToServer() {
            if (localStorage.getItem('liked_' + currentActivePhotoId)) {
                triggerNotification("❤️ You already loved this memory.");
                return;
            }

            const formData = new FormData();
            formData.append('action', 'like_photo');
            formData.append('photo_id', currentActivePhotoId);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modalLikesCount').innerText = data.new_likes;
                    document.getElementById('globalLikeBadge').innerText = data.total_global;
                    document.getElementById('modalLikeBtn').classList.add('active');
                    localStorage.setItem('liked_' + currentActivePhotoId, 'true');
                    triggerNotification("✨ Frame appreciated!");
                }
            });
        }

        // BACKGROUND LIVE RELOAD
        setInterval(() => {
            const isModalOpen = document.getElementById('detailLightbox').style.display === 'flex';
            if (!isModalOpen) {
                window.location.reload();
            }
        }, 40000);
    </script>
</body>
</html>
