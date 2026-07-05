<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
date_default_timezone_set('Asia/Jakarta');

// DATABASE FILE JSON (LIKES & COMMENTS)
$likesFile = 'likes_data.json';
$commentsFile = 'comments_data.json';

if (!file_exists($likesFile) || filesize($likesFile) == 0) { 
    file_put_contents($likesFile, json_encode([])); 
}
if (!file_exists($commentsFile) || filesize($commentsFile) == 0) { 
    file_put_contents($commentsFile, json_encode([])); 
}

$likesData = json_decode(file_get_contents($likesFile), true) ?? [];
$commentsData = json_decode(file_get_contents($commentsFile), true) ?? [];

if (!is_array($likesData)) { $likesData = []; }
if (!is_array($commentsData)) { $commentsData = []; }

$totalGlobalLikes = array_sum($likesData);

// 1. AJAX: PROSES FILTER LIKE VIA AJAX
if (isset($_POST['action']) && $_POST['action'] === 'like_photo') {
    $photoId = $_POST['photo_id'];
    if (!isset($likesData[$photoId])) { $likesData[$photoId] = 0; }
    $likesData[$photoId]++;
    file_put_contents($likesFile, json_encode($likesData));
    echo json_encode(['success' => true, 'new_likes' => $likesData[$photoId], 'total_global' => array_sum($likesData)]);
    exit;
}

// 2. AJAX: PROSES SIMPAN KOMENTAR BARU
if (isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    $photoId = $_POST['photo_id'];
    $commentText = htmlspecialchars(trim($_POST['comment']));
    if (!empty($commentText)) {
        if (!isset($commentsData[$photoId])) { $commentsData[$photoId] = []; }
        $commentsData[$photoId][] = ['text' => $commentText, 'time' => date("H:i")];
        file_put_contents($commentsFile, json_encode($commentsData));
    }
    echo json_encode(['success' => true, 'comments' => $commentsData[$photoId] ?? []]);
    exit;
}

// 3. AJAX: PROSES AMBIL DATA KOMENTAR SECARA REAL-TIME
if (isset($_GET['action']) && $_GET['action'] === 'get_comments') {
    $photoId = $_GET['photo_id'];
    echo json_encode($commentsData[$photoId] ?? []);
    exit;
}

$photoDir = 'uploads_photographer/';
if (!file_exists($photoDir)) { mkdir($photoDir, 0755, true); }

$allowedExtensions = '*.{jpg,jpeg,png,JPG,JPEG,PNG}';
$photos = glob($photoDir . $allowedExtensions, GLOB_BRACE);

if ($photos && is_array($photos)) {
    usort($photos, function($a, $b) { return filemtime($b) - filemtime($a); });
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

        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            -webkit-tap-highlight-color: transparent; 
        }

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
        }

        /* Highlights */
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
            display: flex; 
            justify-content: center; 
            align-items: center; 
        }
        .highlight-item.active .highlight-circle { border-color: #a8a8a8; }
        
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
        }
        .highlight-label { 
            font-size: 11px; 
            max-width: 75px; 
            overflow: hidden; 
            text-overflow: ellipsis; 
            white-space: nowrap; 
        }

        .ig-tabs-divider { 
            display: flex; 
            justify-content: space-around; 
            align-items: center; 
            border-bottom: 1px solid var(--ig-border); 
        }
        .tab-icon-btn { 
            flex: 1; 
            padding: 12px 0; 
            text-align: center; 
            cursor: pointer; 
            opacity: 0.4; 
        }
        .tab-icon-btn.active { 
            opacity: 1; 
            border-bottom: 1px solid #ffffff; 
        }

        .grid-wrapper { max-width: 935px; margin: 0 auto; }
        .instagram-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 3px; }
        
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
            top: 8px; 
            right: 8px; 
            font-size: 11px; 
            background: rgba(0, 0, 0, 0.6); 
            padding: 2px 6px; 
            border-radius: 4px; 
            color: #fff; 
        }

        /* MODAL FULL-SCREEN LIGHTBOX */
        .lightbox-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #000000; display: none; justify-content: center; align-items: center; z-index: 2000;
        }
        .lightbox-card {
            position: relative; background: #000; width: 100%; height: 100%;
            max-width: 450px; display: flex; flex-direction: column; overflow: hidden;
        }
        .lightbox-header {
            position: absolute; top: 0; left: 0; width: 100%; padding: 20px;
            display: flex; justify-content: space-between; align-items: center;
            background: linear-gradient(to bottom, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%); z-index: 10;
        }
        .lightbox-close { font-size: 28px; cursor: pointer; color: #ffffff; text-shadow: 0 1px 4px rgba(0,0,0,0.5); }
        
        .lightbox-img-box { width: 100%; height: 100%; display: flex; align-items: center; background: #000; }
        .lightbox-img-box img { width: 100%; height: 100%; object-fit: cover; }

        /* Area Konten Kontainer di Bawah Gambar */
        .lightbox-overlay-content {
            position: absolute; bottom: 0; left: 0; width: 100%; 
            padding: 30px 20px 40px 20px;
            background: linear-gradient(to top, rgba(0,0,0,0.98) 0%, rgba(0,0,0,0.7) 50%, rgba(0,0,0,0) 100%);
            z-index: 10; display: flex; flex-direction: column; gap: 12px;
            max-height: 70vh; overflow-y: auto;
        }
        .lightbox-overlay-content::-webkit-scrollbar { display: none; }

        .lightbox-user-row { display: flex; align-items: center; gap: 8px; }
        .lightbox-user-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--luxury-gold); display: flex; justify-content: center; align-items: center; font-size: 12px; font-weight: bold; color: #000; }
        .lightbox-caption-txt { color: #ffffff; font-size: 14px; line-height: 1.4; text-shadow: 0 1px 3px rgba(0,0,0,0.6); }

        /* BARIS ACTION BERJEJER: LIKE -> KOMEN -> DOWNLOAD */
        .lightbox-actions-row { display: flex; align-items: center; justify-content: space-between; margin-top: 4px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 12px; }
        .left-action-group { display: flex; align-items: center; gap: 22px; }
        
        .like-heart-btn, .comment-trigger-btn { background: transparent; border: none; font-size: 28px; color: #ffffff; cursor: pointer; filter: drop-shadow(0 1px 3px rgba(0,0,0,0.6)); transition: transform 0.1s ease; }
        .like-heart-btn:active, .comment-trigger-btn:active { transform: scale(1.2); }
        .like-heart-btn.active { color: var(--love-red); }
        .download-btn-secure { text-decoration: none; font-size: 24px; color: #ffffff; filter: drop-shadow(0 1px 3px rgba(0,0,0,0.6)); display: flex; align-items: center; }
        .likes-count-txt { font-size: 13px; font-weight: 600; color: #e5e5e5; }

        /* INTEGRASI FITUR KOMENTAR */
        .embedded-comments-wrapper { display: none; flex-direction: column; gap: 10px; margin-top: 4px; }
        .embedded-comments-list { max-height: 150px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; }
        .embedded-comments-list::-webkit-scrollbar { display: none; }
        
        .comment-node { display: flex; gap: 8px; font-size: 12px; align-items: flex-start; }
        .comment-bubble { background: rgba(255, 255, 255, 0.08); padding: 6px 12px; border-radius: 12px; color: #eee; width: 100%; word-break: break-word; backdrop-filter: blur(4px); }
        .comment-time { font-size: 9px; color: var(--ig-gray); margin-top: 3px; display: block; }
        
        .comment-input-row { display: flex; gap: 8px; margin-top: 4px; }
        .comment-input-field { flex: 1; background: rgba(0, 0, 0, 0.5); border: 1px solid #444; color: #fff; padding: 8px 14px; border-radius: 20px; font-size: 12px; }
        .comment-input-field:focus { border-color: #777; outline: none; }
        .comment-input-submit { background: transparent; border: none; color: #0095f6; font-weight: 600; font-size: 12px; cursor: pointer; }

        /* NAVBAR BAWAH */
        .bottom-nav-bar { position: fixed; bottom: 0; left: 0; width: 100%; background: #000; border-top: 1px solid var(--ig-border); display: flex; justify-content: space-around; align-items: center; padding: 14px 0; z-index: 950; }
        .nav-icon { font-size: 22px; color: var(--ig-text); cursor: pointer; position: relative; }
        .like-badge-counter { position: absolute; top: -6px; right: -10px; background: var(--love-red); color: #fff; font-size: 9px; font-weight: 700; padding: 1px 5px; border-radius: 10px; border: 1px solid #000; }

        .toast-popup { position: fixed; bottom: 90px; left: 50%; transform: translateX(-50%) translateY(100px); background: #262626; color: #fff; padding: 10px 20px; border-radius: 20px; font-size: 12px; z-index: 5000; transition: transform 0.3s ease, opacity 0.3s; opacity: 0; white-space: nowrap; }
        .toast-popup.visible { transform: translateX(-50%) translateY(0); opacity: 1; }
        .empty-msg { grid-column: 1 / -1; text-align: center; color: var(--ig-gray); padding: 60px 20px; font-size: 13px; }
    </style>
</head>
<body>

    <!-- TOP HEADER BAR -->
    <div class="profile-top-bar">
        <div class="username-title">
            <span>Wedding Gallery</span>
            <span class="verified-badge">✓</span>
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

    <!-- DIRECT INSTAGRAM LIGHTBOX OVERLAY -->
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
                    <span style="font-weight: 700; font-size: 14px; color: #fff;">ilham dan tarisa</span>
                    <span style="color: #0095f6; font-size: 12px;">✓</span>
                </div>

                <div class="lightbox-caption-txt" id="modalDynamicCaption"></div>

                <!-- BARIS TOMBOL: LIKE -> KOMEN -> DOWNLOAD BERJEJER -->
                <div class="lightbox-actions-row">
                    <div class="left-action-group">
                        <!-- 1. TOMBOL LIKE -->
                        <button class="like-heart-btn" id="modalLikeBtn" onclick="sendLikeActionToServer()">♥</button>
                        
                        <!-- 2. TOMBOL KOMEN -->
                        <button class="comment-trigger-btn" onclick="toggleCommentsSection()">💬</button>
                        
                        <!-- 3. TOMBOL DOWNLOAD -->
                        <a href="" id="modalDownloadBtn" download="" class="download-btn-secure">📥</a>
                    </div>
                    <div class="likes-count-txt"><span id="modalLikesCount">0</span> likes</div>
                </div>

                <!-- BOX INPUT & AREA DAFTAR KOMENTAR -->
                <div class="embedded-comments-wrapper" id="commentsSectionWrapper">
                    <div class="embedded-comments-list" id="embeddedCommentsContainer">
                        <!-- Diisi data real-time JSON via AJAX -->
                    </div>
                    <div class="comment-input-row">
                        <input type="text" class="comment-input-field" id="inlineCommentInput" placeholder="Add a wedding wish...">
                        <button class="comment-input-submit" onclick="postCommentToServer()">Post</button>
                    </div>
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

    <div class="toast-popup" id="appToast">Notifikasi</div>

    <script>
        let currentActivePhotoId = '';

        // 100 BANK CAPTION VARIATIF (MURNI PER BARIS TANPA DIPANGKAS)
        const captionList = [
            "Baarakallahu laka wa baaraka 'alaika wa jama'a bainakuma fii khair. Selamat menempuh hidup baru Ilham & Tarisa! ✨",
            "Semoga Allah SWT menyempurnakan kebahagiaan kalian dan menjadikan pernikahan ini penuh ketenteraman serta keberkahan. 🤲❤️",
            "Momen penuh kesakralan yang diabadikan pada [TIME] WIB. Semoga menjadi keluarga sakinah, mawaddah, warahmah.",
            "Semoga awal perjalanan ibadah terpanjang ini senantiasa dituntun oleh ridha dan kasih sayang Allah SWT. 💍🕊️",
            "Satu akad, berjuta doa. Semoga ikatan suci ini kokoh terjalin hingga ke jannah-Nya nanti. Amin. ✨",
            "Wishing you a lifetime of love, laughter, and endless happiness. Happy Wedding Ilham & Tarisa! 🎉💖",
            "May your love story look as timeless and beautiful as this frozen moment in time. Captured at [TIME] WIB. 🌟",
            "Dua insan, satu tujuan, beribu kebahagiaan. Saling melengkapi dan menjaga dalam ikatan yang abadi. 💖",
            "Tatap penuh cinta yang terekam pada pukul [TIME] WIB. Bukti nyata bahwa jodoh terbaik selalu datang di waktu terbaik.",
            "Momen ini terlalu estetik untuk dilewatkan begitu saja! Ketuk ikon hati di bawah jika kamu setuju. ❤️",
            "Setiap detik di hari bahagia ini adalah rajutan doa dan harapan baru. Wilujeng sumping dina lambaran anyar! 🌹",
            "Selamat menempuh lembaran baru yang penuh keindahan. Doa kami menyertai setiap langkah kalian berdua. 🕊️✨",
            "Keindahan sejati dari sebuah janji suci yang terucap ikhlas pada jam [TIME] WIB. Sakral dan menyentuh hati.",
            "May Allah bless your union and shower you with His infinite mercy and continuous guidance. Happy Wedding! 🕌💝",
            "Cinta bukan tentang mencari kesempurnaan, tapi membangun kesempurnaan bersama. Selamat berbahagia! 💕",
            "Sebuah kehormatan bisa menyaksikan takdir indah menyatukan Ilham & Tarisa dalam bingkai keabadian. ✨",
            "Diabadikan tepat pada [TIME] WIB. Aura kebahagiaan terpancar nyata dari senyuman manis kedua mempelai. 😍",
            "Happy wedding! May your shared journey be paved with mutual respect, deep understanding, and pure love. 🥂✨",
            "Dua hati yang kini berdetak dalam satu harmoni ibadah. Selamat menempuh kehidupan pernikahan yang indah! 🌟",
            "Semoga hari ini menjadi pembuka gerbang rezeki, kesehatan, dan keturunan yang saleh-salehah. Amin. 🤲💍",
            "Love captured in its purest form. Sungguh momen yang sangat mengharukan dan penuh energi positif! ❤️",
            "Selamat atas bersatunya dua jiwa yang saling melengkapi. Jagalah komitmen suci ini dengan ketulusan hati. 💖",
            "Waktu menunjukkan pukul [TIME] WIB saat kebahagiaan ini membeku indah dalam bidikan lensa kamera. 📸✨",
            "Through life’s ups and downs, may your love grow stronger and deeper with every passing day. Best wishes! 🎉",
            "Selamat mengarungi bahtera rumah tangga. Jadikan sabar dan syukur sebagai kemudi utama perjalanan kalian. 🚢💞",
            "Pernikahan adalah seni menyatukan perbedaan menjadi simfoni yang indah. Selamat menempuh hidup baru! 🎵❤️",
            "Suasana penuh khidmat yang terekam pada [TIME] WIB. Sukses membuat siapa saja yang melihat ikut tersenyum haru.",
            "May the love you share today grow stronger as you grow old together. Congratulations Ilham & Tarisa! 💑🌟",
            "Barakallah untuk pernikahan kalian. Semoga dipenuhi keberkahan yang tak pernah putus dari langit dan bumi. 🤲",
            "Kombinasi sempurna antara kesakralan tradisi dan keanggunan modern. Sangat memukau mata! 😍✨",
            "Hari yang luar biasa untuk merayakan penyatuan dua manusia luar biasa. Titip doa terbaik dari kami semua! 🎈❤️",
            "Jodoh adalah rahasia langit yang paling indah, dan hari ini rahasia itu mewujud nyata pada [TIME] WIB. 🌤️💍",
            "Warmest congratulations on your special day! May your home always be a sanctuary of peace and love. 🏡💖",
            "Selamat menempuh ibadah terpanjang. Semoga cinta kalian selalu segar seperti embun pagi di setiap harinya. 🌿💞",
            "Momen emosional nan indah yang terekam abadi di pukul [TIME] WIB. Kebahagiaan sejati terpancar jelas di sini.",
            "May your life together be full of sweetness, care, and an unbreakable bond of loyalty. Happy Wedding! 🍯❤️",
            "Selamat meniti jalan baru. Saling menggenggam erat, saling mendoakan di setiap sujud malam kalian. 🌙🤲",
            "Keanggunan yang tak lekang oleh waktu. Selamat berbahagia untuk pasangan paling serasi tahun ini! 👑✨",
            "Cheering for your beautiful journey ahead! May every day feel like a sweet new chapter of a love story. 📖💖",
            "Tepat pada pukul [TIME] WIB, komitmen seumur hidup resmi dimulai. Selamat dunia akhirat Ilham & Tarisa! 🌟",
            "Semoga Allah menyatukan kalian dalam kebaikan dan menjauhkan dari segala ujian yang tak sanggup dipikul. 🤲",
            "A match made in heaven. Senang sekali melihat kalian berdua bersanding dalam balutan busana yang menawan ini. ✨",
            "Captured perfectly at [TIME] WIB. A simple gaze that speaks a thousand words of unspoken commitment. 💬❤️",
            "Congratulations to the newlywed! May the joy of today remain in your hearts forever and ever. 🎉🥂",
            "Selamat atas janji sucinya. Semoga cinta kalian abadi, melampaui batas waktu, hingga dipertemukan lagi di surga. 🌌",
            "Pernikahan yang penuh inspirasi dan kehangatan. Terima kasih telah berbagi kebahagiaan ini dengan kami. 🙏💖",
            "Waktu berputar, namun memori manis pada pukul [TIME] WIB ini akan selalu terkunci rapat dalam keindahan. ⏳✨",
            "May your bond be blessed with patience, deep loyalty, and unconditional affection. Congratulations! 🌹❤️",
            "Selamat memasuki dunia baru, dunia di mana satu tawa dibagi dua dan satu kesedihan ditanggung bersama. 💕",
            "Momen transisi terbaik yang tertangkap kamera pada [TIME] WIB. Elegansi dalam kesederhanaan yang hakiki.",
            "Selamat menempuh hidup baru! Semoga rida Allah selalu menyertai hari-hari baru kalian sebagai suami istri. 🤲",
            "Sending you endless love and blessings as you embark on this wonderful adventure of marriage! 🚀💖",
            "Sorot mata penuh ketenangan dan keyakinan pada pukul [TIME] WIB. Selamat melangkah ke masa depan! ✨",
            "May your home be blessed with laughter, your hearts with devotion, and your life with true contentment. 🥂",
            "Alhamdulillah, selamat atas ikatan sucinya. Semoga berkah pernikahannya menular kebaikan untuk semua orang. 🌟",
            "Sempurna! Tidak ada kata lain yang bisa menggambarkan keindahan visual yang terekam pada jam [TIME] WIB ini. 😍",
            "Two individual paths now walking together in perfect alignment. Wishing you the absolute best! 🐾❤️",
            "Selamat hari pernikahan! Semoga cinta kalian menjadi teladan dan sumber inspirasi bagi orang-orang sekitar. 💖",
            "Terpaku melihat keanggunan momen [TIME] WIB ini. Aura pengantin benar-benar memikat hati siapapun. 👑✨",
            "May the Almighty keep your love young and vibrant even when your hairs turn gray. Happy Wedding! 🧓👵❤️",
            "Selamat atas lembaran barunya. Semoga selalu ada jalan keluar yang penuh berkah di setiap dinamika hidup. 🤲",
            "Simfoni cinta yang termanifestasi indah dalam potret estetik pukul [TIME] WIB. Selamat berbahagia! 🎼💖",
            "Cheers to love, laughter, and happily ever after! So incredibly happy for both of you today. 🍾✨",
            "Selamat mengemban amanah baru sebagai pasangan hidup. Semoga menjadi pelindung dan penenang satu sama lain. 🛡️💕",
            "Detik-detik berharga yang diabadikan pada jam [TIME] WIB. Sebuah warisan visual yang akan dikenang selamanya. 📸",
            "May your love become a beautiful fortress that protects you from all the storms of life. Best wishes! 🏰❤️",
            "Selamat menempuh hidup baru. Semoga hari-hari kalian selalu dinaungi rasa syukur yang mendalam. 🤲✨",
            "Momen emas yang berhasil diabadikan pada pukul [TIME] WIB. Benar-benar memancarkan kebahagiaan murni! 🌟",
            "May the bond of your love look as beautiful as this frame forever. Congratulations on your wedding! 🥂❤️",
            "Selamat membina rumah tangga baru. Semoga selalu dipenuhi keberkahan dalam suka maupun duka. 💞",
            "Menatap potret pukul [TIME] WIB ini membuat kita percaya bahwa cinta sejati itu nyata adanya. 💖🕊️",
            "Congratulations! May you find in each other the greatest companionship, friendship, and eternal love. 💑",
            "Selamat atas hari bahagianya. Semoga cinta kalian menginspirasi banyak orang untuk terus percaya pada takdir. ✨",
            "Definisi kebahagiaan sejati yang terekam pada [TIME] WIB. Selamat menempuh ibadah terpanjang, Ilham & Tarisa! 💍",
            "May your marriage be a continuous celebration of love, loyalty, and wonderful shared memories. Happy Wedding! 🥳❤️",
            "Baarakallahu laka wa baaraka 'alaika. Doa terbaik untuk Ilham & Tarisa, bahagia dan menua bersama hingga jannah! 🤲✨",
            "Semoga langkah awal ini dipenuhi dengan kemudahan, keberkahan, serta perlindungan dari segala keburukan. 🕊️",
            "Keindahan yang hakiki terpancar dari kesetiaan yang tulus. Selamat mengukir cerita indah berdua! ✨💖",
            "Captured beautifully at [TIME] WIB. Momen sakral yang tak akan pernah pudar nilainya dimakan waktu. 🎞️",
            "Wishing you a wonderful lifetime of shared responsibilities, sweet kisses, and everlasting bond. 🥂",
            "Alhamdulillah, ikut bahagia melihat bersatunya dua pribadi yang seimbang ini. Menualah dengan bahagia! 💕",
            "Potret kehangatan pukul [TIME] WIB. Semoga sakinah selalu menyelimuti seisi rumah tangga kalian berdua. 🏡",
            "May your true love blossom forever and fill your life with the sweetest fragrances of heaven. 🌸❤️",
            "Selamat menempuh hidup baru! Tetaplah saling menggenggam tangan dalam kondisi apapun yang menghadang. 🤝",
            "Detail estetis nan anggun terekam jelas pada [TIME] WIB. Selamat merayakan hari paling bersejarah! 👑✨",
            "Cheers to the bride and groom! May your path ahead be bright, cheerful, and full of divine blessings. 🎉",
            "Selamat mengikat janji suci. Semoga menjadi ladang pahala dan kebaikan yang terus mengalir deras. 🕌💞",
            "Sempurna dalam segala aspek visual dan emosi yang tertangkap kamera pada pukul [TIME] WIB. Indah sekali! 😍",
            "Wishing you both a marriage filled with sweet surprises, profound growth, and unending loyalty. 💍✨",
            "Selamat menempuh lembaran baru. Saling sabar, saling mengerti, dan selalu mengutamakan ridha-Nya. 🤲🕊️",
            "Momen yang penuh dengan keajaiban cinta, dibekukan tepat pada pukul [TIME] WIB. Sangat berkesan! 🌟",
            "May Allah fill your marriage with the tranquility of faith and the sweetness of pure romantic love. ❤️",
            "Selamat berbahagia! Nikmati setiap proses mendewasa bersama dalam satu bahtera rumah tangga yang utuh. 🚢",
            "Kelembutan tatapan yang terdokumentasi pada pukul [TIME] WIB ini menceritakan segalanya tanpa suara. 💬💖",
            "Congratulations! May your love remain fresh, radiant, and resilient through every challenge of life. 🥂",
            "Selamat meniti takdir indah bersama. Doa terbaik kami kirimkan dari lubuk hati yang paling dalam. 🙏✨",
            "Visualisasi cinta murni yang tertangkap sempurna pada pukul [TIME] WIB. Selamat menempuh hidup baru! 🎉",
            "May you always look at each other with the same love and wonder as you did on this magical day. 💖",
            "Selamat menempuh ibadah terpanjang. Semoga cinta kalian terus bertumbuh subur dan berbuah kebahagiaan. 🌳",
            "Ditutup dengan kesempurnaan doa tepat pada pukul [TIME] WIB. Menualah bersama dengan penuh cinta, Ilham & Tarisa! 🥰💍"
        ];

        function getRandomCaption(time) {
            const randomIndex = Math.floor(Math.random() * captionList.length);
            return captionList[randomIndex].replace('[TIME]', time);
        }

        // NONAKTIFKAN KLIK KANAN TOTAL
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            triggerNotification("🔒 Copyright protected. Please use the official 📥 button.");
            return false;
        });

        // NONAKTIFKAN TOMBOL KEYBOARD INSPECT ELEMENT
        document.addEventListener('keydown', function(e) {
            if (e.keyCode == 123 || (e.ctrlKey && e.shiftKey && (e.keyCode == 73 || e.keyCode == 74)) || (e.ctrlKey && e.keyCode == 85)) {
                e.preventDefault();
                return false;
            }
        });

        function triggerNotification(msg) {
            const toast = document.getElementById('appToast');
            toast.innerText = msg; toast.classList.add('visible');
            setTimeout(() => { toast.classList.remove('visible'); }, 2500);
        }

        function showTotalEngagement() {
            const total = document.getElementById('globalLikeBadge').innerText;
            triggerNotification(`🎉 Beautiful responses! ${total} global appreciation points collected.`);
        }

        function applyGridFilter(category, element) {
            document.querySelectorAll('.highlight-item').forEach(i => i.classList.remove('active'));
            element.classList.add('active');
            document.querySelectorAll('.grid-post-item').forEach(post => {
                if (category === 'all' || post.getAttribute('data-category') === category) {
                    post.style.display = 'block';
                } else { post.style.display = 'none'; }
            });
        }

        function openImageDetail(src, photoId, likes, time, filename) {
            currentActivePhotoId = photoId;
            document.getElementById('modalTargetImg').src = src;
            document.getElementById('modalLikesCount').innerText = likes;
            document.getElementById('modalDynamicCaption').innerHTML = getRandomCaption(time);

            const dlBtn = document.getElementById('modalDownloadBtn');
            dlBtn.href = src; dlBtn.setAttribute('download', filename);

            const likeBtn = document.getElementById('modalLikeBtn');
            if (localStorage.getItem('liked_' + photoId)) {
                likeBtn.classList.add('active');
            } else {
                likeBtn.classList.remove('active');
            }

            // Sembunyikan kolom komentar tiap ganti foto baru sebelum dipicu tombol
            document.getElementById('commentsSectionWrapper').style.display = 'none';

            document.getElementById('detailLightbox').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeImageDetail(e) { if (e.target.id === 'detailLightbox') { forceCloseDetail(); } }
        function forceCloseDetail() {
            document.getElementById('detailLightbox').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function sendLikeActionToServer() {
            if (localStorage.getItem('liked_' + currentActivePhotoId)) {
                triggerNotification("❤️ You already loved this memory.");
                return;
            }
            const formData = new FormData();
            formData.append('action', 'like_photo');
            formData.append('photo_id', currentActivePhotoId);

            fetch(window.location.href, { method: 'POST', body: formData })
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

        // TAMPILKAN / SEMBUNYIKAN COMMENT SECTION
        function toggleCommentsSection() {
            const wrapper = document.getElementById('commentsSectionWrapper');
            if (wrapper.style.display === 'none' || wrapper.style.display === '') {
                wrapper.style.display = 'flex';
                loadCommentsRealtime();
            } else {
                wrapper.style.display = 'none';
            }
        }

        // AJAX AMBIL KOMENTAR
        function loadCommentsRealtime() {
            fetch(`?action=get_comments&photo_id=${currentActivePhotoId}`)
            .then(res => res.json())
            .then(comments => {
                const list = document.getElementById('embeddedCommentsContainer');
                if (comments.length === 0) {
                    list.innerHTML = `<p style="font-size:11px; color:var(--ig-gray); text-align:center; padding:10px 0;">No wishes yet. Write a wish!</p>`;
                    return;
                }
                list.innerHTML = comments.map(c => `
                    <div class="comment-node">
                        <div class="comment-bubble">
                            <strong>Guest</strong>
                            <p style="margin-top:2px;">${c.text}</p>
                            <span class="comment-time">${c.time} WIB</span>
                        </div>
                    </div>
                `).join('');
                list.scrollTop = list.scrollHeight;
            });
        }

        // AJAX POST KOMENTAR
        function postCommentToServer() {
            const input = document.getElementById('inlineCommentInput');
            const val = input.value.trim();
            if (!val) return;

            const formData = new FormData();
            formData.append('action', 'add_comment');
            formData.append('photo_id', currentActivePhotoId);
            formData.append('comment', val);

            fetch(window.location.href, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadCommentsRealtime();
                    triggerNotification("💬 Wish posted!");
                }
            });
        }

        setInterval(() => {
            const isModalOpen = document.getElementById('detailLightbox').style.display === 'flex';
            if (!isModalOpen) { window.location.reload(); }
        }, 40000);
    </script>
</body>
</html>