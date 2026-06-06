<?php
$pageTitle = 'Watch';

// Load configuration and auth logic first, before any HTML output
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!$auth->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

$videoId = $_GET['id'] ?? 0;
$videoDetails = $video->getVideoDetails($videoId);

if (!$videoDetails) {
    header('Location: ' . SITE_URL);
    exit();
}

$user           = $auth->getCurrentUser();
$subscription   = $user['subscription_status'] ?? 'free';
$isPremiumVideo = (($videoDetails['access_level'] ?? 'free') === 'premium');
$accessLimited  = ($isPremiumVideo && $subscription === 'free');

$video->addToWatchHistory($_SESSION['user_id'], $videoId, 0);
$video->incrementViews($videoId);

$isFavorite      = $video->isFavorite($_SESSION['user_id'], $videoId);
$recommendations = $video->getRecommendations($_SESSION['user_id']);

$SITE_URL = rtrim(SITE_URL, '/') . '/';

require_once 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($videoDetails['title']); ?> — StreamVault</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0 }
    :root {
      --bg: #080808;
      --surface: #111111;
      --surface2: #1a1a1a;
      --border: rgba(255,255,255,.07);
      --red: #e50914;
      --red-dim: #b8070f;
      --gold: #f5c518;
      --text: #f0f0f0;
      --muted: #888;
      --font-display: 'Bebas Neue', sans-serif;
      --font-body: 'DM Sans', sans-serif;
      --card-r: 12px;
      --trans: .3s cubic-bezier(.4,0,.2,1);
    }
    html { scroll-behavior: smooth }
    body { font-family: var(--font-body); background: var(--bg); color: var(--text); overflow-x: hidden; -webkit-font-smoothing: antialiased }
    a { text-decoration: none; color: inherit }
    img { display: block; width: 100%; object-fit: cover }
    ::-webkit-scrollbar { width: 6px }
    ::-webkit-scrollbar-track { background: var(--bg) }
    ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px }

    /* ── BACK BAR ── */
    .back-bar {
      padding: 20px 4vw 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      font-weight: 600;
      color: var(--muted);
      transition: color var(--trans);
      cursor: pointer;
    }
    .back-btn:hover { color: var(--text) }
    .back-btn i { font-size: 12px }
    .back-sep { color: var(--border); font-size: 16px }
    .back-title {
      font-size: 13px;
      color: var(--muted);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 300px;
    }

    /* ── WATCH LAYOUT ── */
    .watch-layout {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 0;
      padding: 24px 4vw 0;
      align-items: start;
    }

    /* ── PLAYER COLUMN ── */
    .player-col {}

    /* Player wrapper */
    .player-wrap {
      position: relative;
      width: 100%;
      aspect-ratio: 16/9;
      background: #000;
      border-radius: var(--card-r);
      overflow: hidden;
    }
    .player-wrap video {
      width: 100%;
      height: 100%;
      display: block;
    }

    /* Access overlay for free users */
    .access-overlay {
      position: absolute;
      inset: 0;
      background: rgba(8,8,8,.92);
      backdrop-filter: blur(12px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10;
      animation: fadeUp .4s ease both;
    }
    .access-card {
      text-align: center;
      padding: 48px 40px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
      max-width: 380px;
    }
    .access-icon {
      width: 64px; height: 64px;
      border-radius: 50%;
      background: rgba(229,9,20,.1);
      border: 1px solid rgba(229,9,20,.25);
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; color: var(--red);
      margin: 0 auto 20px;
    }
    .access-card h3 {
      font-family: var(--font-display);
      font-size: 28px;
      letter-spacing: 2px;
      margin-bottom: 10px;
    }
    .access-card p {
      font-size: 14px;
      color: var(--muted);
      line-height: 1.6;
      margin-bottom: 24px;
    }
    .btn-upgrade {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--red);
      color: #fff;
      font-weight: 700;
      font-size: 14px;
      padding: 13px 28px;
      border-radius: 8px;
      transition: background var(--trans), transform var(--trans), box-shadow var(--trans);
      box-shadow: 0 4px 20px rgba(229,9,20,.35);
    }
    .btn-upgrade:hover {
      background: var(--red-dim);
      transform: translateY(-2px);
      box-shadow: 0 8px 32px rgba(229,9,20,.5);
    }

    /* ── VIDEO INFO (below player) ── */
    .video-info {
      padding: 24px 0 0;
    }
    .info-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 20px;
      margin-bottom: 16px;
      flex-wrap: wrap;
    }
    .video-title {
      font-family: var(--font-display);
      font-size: clamp(28px, 4vw, 44px);
      letter-spacing: 2px;
      line-height: 1;
      flex: 1;
    }

    /* Action buttons */
    .action-group {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
    }
    .action-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 18px;
      border-radius: 8px;
      background: var(--surface2);
      border: 1px solid var(--border);
      color: var(--muted);
      font-family: var(--font-body);
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all var(--trans);
    }
    .action-btn:hover {
      background: var(--surface);
      border-color: rgba(255,255,255,.15);
      color: var(--text);
    }
    .action-btn.active {
      background: rgba(229,9,20,.12);
      border-color: rgba(229,9,20,.35);
      color: var(--red);
    }
    .action-btn i { font-size: 14px }

    /* Meta pills */
    .video-meta {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 20px;
    }
    .meta-pill {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 500;
    }
    .pill-rating {
      background: rgba(245,197,24,.1);
      border: 1px solid rgba(245,197,24,.2);
      color: var(--gold);
    }
    .pill-default {
      background: var(--surface2);
      border: 1px solid var(--border);
      color: var(--muted);
    }
    .pill-views {
      background: rgba(229,9,20,.08);
      border: 1px solid rgba(229,9,20,.15);
      color: rgba(229,9,20,.8);
    }
    .pill-category {
      background: var(--surface2);
      border: 1px solid var(--border);
      color: var(--text);
      font-weight: 600;
      letter-spacing: .5px;
      text-transform: uppercase;
      font-size: 11px;
    }

    /* Divider */
    .info-divider {
      height: 1px;
      background: var(--border);
      margin: 20px 0;
    }

    /* Description */
    .desc-label {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 12px;
    }
    .desc-text {
      font-size: 15px;
      line-height: 1.75;
      color: rgba(255,255,255,.7);
      max-width: 680px;
    }
    .desc-text.clamped {
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .desc-toggle {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      margin-top: 10px;
      font-size: 13px;
      font-weight: 600;
      color: var(--red);
      cursor: pointer;
      transition: opacity var(--trans);
    }
    .desc-toggle:hover { opacity: .8 }

    /* ── SIDEBAR COLUMN ── */
    .sidebar-col {
      padding-left: 28px;
      border-left: 1px solid var(--border);
      position: sticky;
      top: 24px;
      max-height: calc(100vh - 48px);
      overflow-y: auto;
      scrollbar-width: none;
    }
    .sidebar-col::-webkit-scrollbar { display: none }

    .sidebar-label {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .sidebar-label::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    /* Sidebar rec card */
    .rec-card {
      display: flex;
      gap: 12px;
      padding: 12px;
      border-radius: 10px;
      cursor: pointer;
      transition: background var(--trans);
      margin-bottom: 4px;
    }
    .rec-card:hover { background: var(--surface2) }
    .rec-thumb {
      flex-shrink: 0;
      width: 100px;
      aspect-ratio: 2/3;
      border-radius: 7px;
      overflow: hidden;
      background: var(--surface2);
      position: relative;
    }
    .rec-thumb img { height: 100%; transition: transform .4s ease }
    .rec-card:hover .rec-thumb img { transform: scale(1.05) }
    .rec-play {
      position: absolute;
      inset: 0;
      background: rgba(0,0,0,.4);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity var(--trans);
    }
    .rec-card:hover .rec-play { opacity: 1 }
    .rec-play i { font-size: 20px; color: #fff; filter: drop-shadow(0 0 10px rgba(229,9,20,.6)) }
    .rec-details { flex: 1; min-width: 0; padding-top: 2px }
    .rec-title {
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 6px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      line-height: 1.4;
    }
    .rec-meta {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .rec-rating {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 11px;
      font-weight: 600;
      color: var(--gold);
    }
    .rec-cat {
      font-size: 11px;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .4px;
    }

    /* ── FOOTER ── */
    .footer { padding: 48px 4vw 32px; border-top: 1px solid var(--border); margin-top: 60px }
    .footer-top { display: flex; flex-wrap: wrap; gap: 40px; margin-bottom: 40px }
    .footer-brand { flex: 1; min-width: 200px }
    .footer-logo { font-family: var(--font-display); font-size: 26px; letter-spacing: 2px; color: var(--red); margin-bottom: 12px }
    .footer-logo span { color: var(--text) }
    .footer-desc { font-size: 14px; color: var(--muted); line-height: 1.7; max-width: 280px }
    .footer-links { display: flex; flex-wrap: wrap; gap: 40px; flex: 2 }
    .footer-col h4 { font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--muted); margin-bottom: 16px }
    .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 10px }
    .footer-col a { font-size: 14px; color: rgba(255,255,255,.5); transition: color var(--trans) }
    .footer-col a:hover { color: var(--text) }
    .footer-bottom {
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: 12px; padding-top: 24px; border-top: 1px solid var(--border);
    }
    .footer-copy { font-size: 13px; color: var(--muted) }
    .footer-socials { display: flex; gap: 14px }
    .footer-socials a {
      width: 36px; height: 36px; border-radius: 50%;
      background: var(--surface); border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      color: var(--muted); font-size: 14px; transition: all var(--trans);
    }
    .footer-socials a:hover { background: var(--red); border-color: var(--red); color: #fff }

    /* ── TOAST ── */
    .toast {
      position: fixed;
      bottom: 32px; left: 50%; transform: translateX(-50%) translateY(20px);
      background: var(--surface2);
      border: 1px solid var(--border);
      color: var(--text);
      font-size: 14px;
      font-weight: 500;
      padding: 12px 24px;
      border-radius: 30px;
      display: flex;
      align-items: center;
      gap: 10px;
      opacity: 0;
      pointer-events: none;
      transition: opacity .3s ease, transform .3s ease;
      z-index: 9999;
      backdrop-filter: blur(12px);
    }
    .toast.show { opacity: 1; transform: translateX(-50%) translateY(0) }
    .toast i { color: var(--red) }

    /* ── ANIMATIONS ── */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px) }
      to   { opacity: 1; transform: translateY(0) }
    }
    .animate-in { animation: fadeUp .5s ease both }

    /* ── RESPONSIVE ── */
    @media(max-width:1024px) {
      .watch-layout { grid-template-columns: 1fr; gap: 0 }
      .sidebar-col {
        padding-left: 0;
        border-left: none;
        border-top: 1px solid var(--border);
        position: static;
        max-height: none;
        padding-top: 32px;
        margin-top: 32px;
      }
      .sidebar-recs {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 8px;
      }
    }
    @media(max-width:600px) {
      .watch-layout { padding: 16px 4vw 0 }
      .video-title { font-size: 28px }
      .action-group { width: 100% }
      .action-btn { flex: 1; justify-content: center }
      .info-top { flex-direction: column; gap: 16px }
    }
  </style>
</head>
<body>

<!-- ── BACK BAR ── -->
<div class="back-bar animate-in">
  <a href="javascript:history.back()" class="back-btn">
    <i class="fas fa-chevron-left"></i> Back
  </a>
  <span class="back-sep">/</span>
  <span class="back-title"><?php echo htmlspecialchars($videoDetails['title']); ?></span>
</div>

<!-- ── WATCH LAYOUT ── -->
<div class="watch-layout">

  <!-- LEFT: Player + Info -->
  <div class="player-col animate-in">

    <!-- Player -->
    <div class="player-wrap">
      <?php if (!$accessLimited): ?>
      <video id="videoPlayer" controls preload="auto" playsinline>
        <source src="<?php echo $SITE_URL; ?>assets/uploads/videos/<?php echo htmlspecialchars($videoDetails['video_path']); ?>" type="video/mp4">
        Your browser does not support the video tag.
      </video>
      <?php endif; ?>

      <?php if ($accessLimited): ?>
      <div class="access-overlay">
        <div class="access-card">
          <div class="access-icon"><i class="fas fa-lock"></i></div>
          <h3>Premium Content</h3>
          <p>This video is for premium subscribers. Upgrade to watch unlimited full-length content.</p>
          <a href="<?php echo $SITE_URL; ?>subscription.php" class="btn-upgrade">
            <i class="fas fa-bolt"></i> Upgrade Now
          </a>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Info -->
    <div class="video-info">
      <div class="info-top">
        <h1 class="video-title"><?php echo htmlspecialchars($videoDetails['title']); ?></h1>
        <div class="action-group">
          <button class="action-btn <?php echo $isFavorite ? 'active' : ''; ?>" id="favoriteBtn" data-video="<?php echo $videoId; ?>">
            <i class="fas fa-heart"></i>
            <span><?php echo $isFavorite ? 'Saved' : 'My List'; ?></span>
          </button>
          <button class="action-btn" id="shareBtn">
            <i class="fas fa-share-nodes"></i>
            <span>Share</span>
          </button>
        </div>
      </div>

      <div class="video-meta">
        <?php if ($videoDetails['rating']): ?>
          <span class="meta-pill pill-rating">
            <i class="fas fa-star"></i> <?php echo $videoDetails['rating']; ?>
          </span>
        <?php endif; ?>
        <?php if ($videoDetails['release_year']): ?>
          <span class="meta-pill pill-default">
            <i class="fas fa-calendar"></i> <?php echo $videoDetails['release_year']; ?>
          </span>
        <?php endif; ?>
        <?php if ($videoDetails['duration']): ?>
          <span class="meta-pill pill-default">
            <i class="fas fa-clock"></i> <?php echo $videoDetails['duration']; ?>
          </span>
        <?php endif; ?>
        <?php if ($videoDetails['category_name']): ?>
          <span class="meta-pill pill-category"><?php echo htmlspecialchars($videoDetails['category_name']); ?></span>
        <?php endif; ?>
        <?php if ($videoDetails['views']): ?>
          <span class="meta-pill pill-views">
            <i class="fas fa-eye"></i> <?php echo number_format($videoDetails['views']); ?> views
          </span>
        <?php endif; ?>
      </div>

      <?php if (!empty($videoDetails['description'])): ?>
      <div class="info-divider"></div>
      <p class="desc-label">About</p>
      <p class="desc-text clamped" id="descText">
        <?php echo nl2br(htmlspecialchars($videoDetails['description'])); ?>
      </p>
      <span class="desc-toggle" id="descToggle">
        Show more <i class="fas fa-chevron-down"></i>
      </span>
      <?php endif; ?>
    </div>
  </div>

  <!-- RIGHT: Sidebar Recommendations -->
  <?php if (!empty($recommendations)): ?>
  <aside class="sidebar-col">
    <div class="sidebar-label">Up Next</div>
    <div id="sidebarRecs" class="sidebar-recs">
      <?php foreach (array_slice($recommendations, 0, 10) as $i => $rec): ?>
        <div class="rec-card"
             style="opacity:0;animation:fadeUp .4s ease <?php echo $i * 60; ?>ms both"
             onclick="location.href='<?php echo $SITE_URL; ?>watch.php?id=<?php echo $rec['id']; ?>'">
          <div class="rec-thumb">
            <img src="<?php echo $SITE_URL; ?>assets/uploads/thumbnails/<?php echo htmlspecialchars($rec['thumbnail_path']); ?>"
                 alt="<?php echo htmlspecialchars($rec['title']); ?>"
                 loading="lazy"
                 onerror="this.src='assets/placeholder.jpg'">
            <div class="rec-play"><i class="fas fa-play"></i></div>
          </div>
          <div class="rec-details">
            <p class="rec-title"><?php echo htmlspecialchars($rec['title']); ?></p>
            <div class="rec-meta">
              <?php if (!empty($rec['rating'])): ?>
                <span class="rec-rating"><i class="fas fa-star"></i> <?php echo $rec['rating']; ?></span>
              <?php endif; ?>
              <?php if (!empty($rec['category_name'])): ?>
                <span class="rec-cat"><?php echo htmlspecialchars($rec['category_name']); ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </aside>
  <?php endif; ?>

</div>

<!-- ── TOAST ── -->
<div class="toast" id="toast">
  <i class="fas fa-check-circle"></i>
  <span id="toastMsg">Copied to clipboard!</span>
</div>

<!-- ── FOOTER ── -->
<footer class="footer">
  <div class="footer-top">
    <div class="footer-brand">
      <div class="footer-logo">STREAM<span>VAULT</span></div>
      <p class="footer-desc">Your premium destination for movies, TV shows, and original content. Unlimited entertainment.</p>
    </div>
    <div class="footer-links">
      <div class="footer-col">
        <h4>Browse</h4>
        <ul>
          <li><a href="<?php echo $SITE_URL; ?>">Home</a></li>
          <li><a href="<?php echo $SITE_URL; ?>movies.php">Movies</a></li>
          <li><a href="<?php echo $SITE_URL; ?>series.php">TV Shows</a></li>
          <li><a href="#">New &amp; Hot</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Account</h4>
        <ul>
          <li><a href="#">My List</a></li>
          <li><a href="#">Settings</a></li>
          <li><a href="#">Subscription</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Legal</h4>
        <ul>
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Terms of Use</a></li>
          <li><a href="#">Contact</a></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <p class="footer-copy">© 2025 StreamVault. All rights reserved.</p>
    <div class="footer-socials">
      <a href="#"><i class="fab fa-instagram"></i></a>
      <a href="#"><i class="fab fa-twitter"></i></a>
      <a href="#"><i class="fab fa-youtube"></i></a>
      <a href="#"><i class="fab fa-facebook-f"></i></a>
    </div>
  </div>
</footer>

<script src="<?php echo $SITE_URL; ?>assets/js/player.js"></script>
<script>
  /* ── FAVOURITE TOGGLE ── */
  document.getElementById('favoriteBtn').addEventListener('click', function () {
    const btn = this;
    fetch('<?php echo $SITE_URL; ?>ajax/toggle-favorite.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'video_id=' + btn.dataset.video
    })
    .then(r => r.json())
    .then(data => {
      const added = data.action === 'added';
      btn.classList.toggle('active', added);
      btn.querySelector('span').textContent = added ? 'Saved' : 'My List';
      showToast(added ? 'Added to your list' : 'Removed from list', added ? 'fa-heart' : 'fa-heart-crack');
    })
    .catch(() => showToast('Something went wrong', 'fa-triangle-exclamation'));
  });

  /* ── SHARE ── */
  document.getElementById('shareBtn').addEventListener('click', function () {
    const url = window.location.href;
    if (navigator.share) {
      navigator.share({ title: document.title, url });
    } else {
      navigator.clipboard.writeText(url).then(() => showToast('Link copied to clipboard!', 'fa-link'));
    }
  });

  /* ── TOAST ── */
  function showToast(msg, icon = 'fa-check-circle') {
    const toast = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    toast.querySelector('i').className = 'fas ' + icon;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
  }

  /* ── DESCRIPTION TOGGLE ── */
  const descText   = document.getElementById('descText');
  const descToggle = document.getElementById('descToggle');
  if (descToggle) {
    let expanded = false;
    descToggle.addEventListener('click', () => {
      expanded = !expanded;
      descText.classList.toggle('clamped', !expanded);
      descToggle.innerHTML = expanded
        ? 'Show less <i class="fas fa-chevron-up"></i>'
        : 'Show more <i class="fas fa-chevron-down"></i>';
    });
  }

  /* ── WATCH TIME TRACKING ── */
  const videoEl = document.getElementById('videoPlayer');
  videoEl.addEventListener('timeupdate', function () {
    const t = Math.floor(this.currentTime);
    if (t > 0 && t % 30 === 0) {
      fetch('<?php echo $SITE_URL; ?>ajax/update-watch-time.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'video_id=<?php echo (int)$videoId; ?>&watch_time=' + t
      });
    }
  });
</script>

</body>
</html>
<?php require_once 'includes/footer.php'; ?>