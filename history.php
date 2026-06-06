<?php
$pageTitle = 'Watch History';
require_once 'includes/header.php';
require_once 'includes/functions.php';

if (!$auth->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

$watchHistory = $video->getWatchHistory($_SESSION['user_id']);
$SITE_URL = rtrim(SITE_URL, '/') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Watch History — StreamVault</title>
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

    /* ── PAGE BANNER ── */
    .page-banner {
      position: relative;
      padding: 120px 4vw 56px;
      overflow: hidden;
    }
    .page-banner::before {
      content: '';
      position: absolute; inset: 0;
      background:
        radial-gradient(ellipse 50% 70% at 90% 40%, rgba(229,9,20,.06) 0%, transparent 70%),
        linear-gradient(180deg, rgba(229,9,20,.03) 0%, transparent 100%);
      pointer-events: none;
    }
    .page-banner::after {
      content: ''; position: absolute;
      bottom: 0; left: 0; right: 0;
      height: 1px; background: var(--border);
    }
    .banner-eyebrow {
      display: inline-flex; align-items: center; gap: 8px;
      font-size: 11px; font-weight: 700;
      letter-spacing: 2px; text-transform: uppercase;
      color: var(--red); margin-bottom: 12px;
    }
    .banner-title {
      font-family: var(--font-display);
      font-size: clamp(44px, 6vw, 80px);
      letter-spacing: 3px; line-height: .92;
      margin-bottom: 14px;
    }
    .banner-title span { color: var(--red) }
    .banner-meta { display: flex; align-items: center; gap: 16px; flex-wrap: wrap }
    .banner-count { font-size: 14px; color: var(--muted) }
    .banner-count strong { color: var(--text); font-weight: 600 }

    /* ── TOOLBAR ── */
    .toolbar {
      padding: 20px 4vw;
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      flex-wrap: wrap;
      position: sticky; top: 0; z-index: 100;
      backdrop-filter: blur(20px);
    }

    /* Filter tabs */
    .filter-tabs { display: flex; align-items: center; gap: 6px }
    .filter-tab {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 8px 18px;
      border-radius: 30px;
      background: var(--surface2);
      border: 1px solid var(--border);
      font-size: 13px; font-weight: 500;
      color: var(--muted);
      cursor: pointer;
      transition: all var(--trans);
      white-space: nowrap;
    }
    .filter-tab:hover { border-color: rgba(229,9,20,.35); color: var(--text) }
    .filter-tab.active { background: var(--red); border-color: var(--red); color: #fff }
    .filter-tab .tab-count {
      background: rgba(255,255,255,.15);
      font-size: 11px; font-weight: 700;
      padding: 1px 6px; border-radius: 10px;
    }
    .filter-tab.active .tab-count { background: rgba(0,0,0,.2) }

    /* Clear all button */
    .btn-clear {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 9px 18px;
      border-radius: 8px;
      background: transparent;
      border: 1px solid var(--border);
      color: var(--muted);
      font-family: var(--font-body);
      font-size: 13px; font-weight: 600;
      cursor: pointer;
      transition: all var(--trans);
    }
    .btn-clear:hover {
      background: rgba(229,9,20,.08);
      border-color: rgba(229,9,20,.3);
      color: var(--red);
    }

    /* ── MAIN ── */
    .history-main { padding: 40px 4vw 80px }

    /* ── HISTORY GRID ── */
    .history-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
    }

    /* ── HISTORY CARD ── */
    .history-card {
      position: relative;
      border-radius: var(--card-r);
      background: var(--surface);
      border: 1px solid var(--border);
      overflow: hidden;
      transition: transform var(--trans), box-shadow var(--trans), border-color var(--trans);
    }
    .history-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 16px 48px rgba(0,0,0,.6);
      border-color: rgba(255,255,255,.1);
    }

    /* Thumbnail — 16:9 for history */
    .history-thumb {
      position: relative;
      aspect-ratio: 16/9;
      overflow: hidden;
      cursor: pointer;
      background: var(--surface2);
    }
    .history-thumb img {
      height: 100%;
      transition: transform .5s ease;
    }
    .history-card:hover .history-thumb img { transform: scale(1.06) }

    .play-overlay {
      position: absolute; inset: 0;
      background: rgba(0,0,0,.4);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; transition: opacity var(--trans);
    }
    .history-card:hover .play-overlay { opacity: 1 }
    .play-overlay i {
      font-size: 40px; color: #fff;
      filter: drop-shadow(0 0 16px rgba(229,9,20,.6));
      transition: transform var(--trans);
    }
    .history-card:hover .play-overlay i { transform: scale(1.1) }

    /* Progress bar */
    .progress-bar {
      position: absolute; bottom: 0; left: 0; right: 0;
      height: 3px; background: rgba(255,255,255,.15);
    }
    .progress-fill {
      height: 100%;
      background: var(--red);
      border-radius: 0 2px 2px 0;
      transition: width .4s ease;
    }

    /* Watched % badge */
    .watch-pct {
      position: absolute; bottom: 10px; right: 10px;
      background: rgba(0,0,0,.75);
      backdrop-filter: blur(6px);
      color: var(--text);
      font-size: 11px; font-weight: 700;
      padding: 3px 8px; border-radius: 4px;
    }

    /* Card body */
    .history-body {
      padding: 14px 16px 16px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .history-title-row {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 10px;
    }
    .history-title {
      font-size: 14px; font-weight: 600;
      line-height: 1.4;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      cursor: pointer;
      flex: 1;
      transition: color var(--trans);
    }
    .history-title:hover { color: var(--red) }

    /* Remove button */
    .remove-btn {
      flex-shrink: 0;
      width: 28px; height: 28px;
      border-radius: 50%;
      background: transparent;
      border: 1px solid var(--border);
      color: var(--muted);
      font-size: 11px;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer;
      transition: all var(--trans);
    }
    .remove-btn:hover {
      background: rgba(229,9,20,.12);
      border-color: rgba(229,9,20,.3);
      color: var(--red);
    }

    .history-meta {
      display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    }
    .meta-item {
      display: flex; align-items: center; gap: 5px;
      font-size: 12px; color: var(--muted);
    }
    .meta-item i { font-size: 11px }
    .meta-item.highlight { color: var(--gold) }

    /* ── EMPTY STATE ── */
    .empty-state {
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      gap: 16px; padding: 100px 20px; text-align: center;
    }
    .empty-icon {
      width: 80px; height: 80px; border-radius: 50%;
      background: var(--surface); border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      font-size: 28px; color: var(--muted);
    }
    .empty-state h2 { font-family: var(--font-display); font-size: 32px; letter-spacing: 2px }
    .empty-state p { font-size: 15px; color: var(--muted) }
    .btn-browse {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--red); color: #fff;
      font-weight: 700; font-size: 14px;
      padding: 12px 28px; border-radius: 8px; margin-top: 8px;
      transition: background var(--trans), transform var(--trans);
    }
    .btn-browse:hover { background: var(--red-dim); transform: translateY(-2px) }

    /* ── EMPTY FILTER STATE ── */
    .no-results {
      display: none;
      flex-direction: column; align-items: center;
      gap: 12px; padding: 60px 20px; text-align: center;
    }
    .no-results.visible { display: flex }
    .no-results i { font-size: 32px; color: var(--muted) }
    .no-results p { font-size: 15px; color: var(--muted) }

    /* ── CONFIRM MODAL ── */
    .modal-backdrop {
      position: fixed; inset: 0; z-index: 2000;
      background: rgba(0,0,0,.85);
      backdrop-filter: blur(12px);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; pointer-events: none;
      transition: opacity var(--trans);
    }
    .modal-backdrop.open { opacity: 1; pointer-events: all }
    .modal {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 36px 40px;
      max-width: 400px; width: 90%;
      text-align: center;
      transform: scale(.95);
      transition: transform var(--trans);
    }
    .modal-backdrop.open .modal { transform: scale(1) }
    .modal-icon {
      width: 56px; height: 56px; border-radius: 50%;
      background: rgba(229,9,20,.1); border: 1px solid rgba(229,9,20,.25);
      display: flex; align-items: center; justify-content: center;
      font-size: 20px; color: var(--red); margin: 0 auto 20px;
    }
    .modal h3 {
      font-family: var(--font-display); font-size: 26px;
      letter-spacing: 2px; margin-bottom: 10px;
    }
    .modal p { font-size: 14px; color: var(--muted); line-height: 1.6; margin-bottom: 28px }
    .modal-actions { display: flex; gap: 12px; justify-content: center }
    .btn-cancel {
      padding: 11px 24px; border-radius: 8px;
      background: var(--surface2); border: 1px solid var(--border);
      color: var(--muted); font-family: var(--font-body);
      font-size: 14px; font-weight: 600; cursor: pointer;
      transition: all var(--trans);
    }
    .btn-cancel:hover { color: var(--text); border-color: rgba(255,255,255,.15) }
    .btn-confirm {
      padding: 11px 24px; border-radius: 8px;
      background: var(--red); border: none; color: #fff;
      font-family: var(--font-body); font-size: 14px; font-weight: 700;
      cursor: pointer; transition: background var(--trans), transform var(--trans);
    }
    .btn-confirm:hover { background: var(--red-dim); transform: translateY(-1px) }

    /* ── TOAST ── */
    .toast {
      position: fixed; bottom: 32px; left: 50%;
      transform: translateX(-50%) translateY(20px);
      background: var(--surface2); border: 1px solid var(--border);
      color: var(--text); font-size: 14px; font-weight: 500;
      padding: 12px 24px; border-radius: 30px;
      display: flex; align-items: center; gap: 10px;
      opacity: 0; pointer-events: none;
      transition: opacity .3s ease, transform .3s ease;
      z-index: 9999; backdrop-filter: blur(12px);
    }
    .toast.show { opacity: 1; transform: translateX(-50%) translateY(0) }
    .toast i { color: var(--red) }

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

    /* ── ANIMATIONS ── */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px) }
      to   { opacity: 1; transform: translateY(0) }
    }
    .animate-in { animation: fadeUp .5s ease both }

    /* ── RESPONSIVE ── */
    @media(max-width:900px) {
      .history-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)) }
    }
    @media(max-width:600px) {
      .history-grid { grid-template-columns: 1fr; gap: 14px }
      .filter-tabs { gap: 4px }
      .filter-tab { padding: 7px 12px; font-size: 12px }
      .toolbar { flex-direction: column; align-items: flex-start; gap: 12px }
      .btn-clear { width: 100%; justify-content: center }
    }
  </style>
</head>
<body>

<!-- ── PAGE BANNER ── -->
<div class="page-banner animate-in">
  <div class="banner-eyebrow"><i class="fas fa-clock-rotate-left"></i> My Account</div>
  <h1 class="banner-title">WATCH <span>HISTORY</span></h1>
  <div class="banner-meta">
    <span class="banner-count">
      <strong><?php echo count($watchHistory); ?></strong> title<?php echo count($watchHistory) !== 1 ? 's' : ''; ?> watched
    </span>
  </div>
</div>

<?php if (!empty($watchHistory)): ?>

<!-- ── TOOLBAR ── -->
<div class="toolbar">
  <div class="filter-tabs">
    <button class="filter-tab active" data-filter="all">
      All <span class="tab-count" id="cnt-all"><?php echo count($watchHistory); ?></span>
    </button>
    <button class="filter-tab" data-filter="today">
      Today <span class="tab-count" id="cnt-today">0</span>
    </button>
    <button class="filter-tab" data-filter="week">
      This Week <span class="tab-count" id="cnt-week">0</span>
    </button>
    <button class="filter-tab" data-filter="month">
      This Month <span class="tab-count" id="cnt-month">0</span>
    </button>
  </div>
  <button class="btn-clear" id="clearHistoryBtn">
    <i class="fas fa-trash-can"></i> Clear All History
  </button>
</div>

<!-- ── HISTORY GRID ── -->
<main class="history-main">
  <div class="history-grid" id="historyGrid">
    <?php foreach ($watchHistory as $i => $h): ?>
      <?php
        $watchPct = $h['watch_time'] > 0 ? min(100, (int)$h['watch_time']) : 0;
        $lastWatched = strtotime($h['last_watched']);
        $dateFormatted = date('M d, Y', $lastWatched);
        $timeFormatted = date('g:i A', $lastWatched);
      ?>
      <div class="history-card"
           data-date="<?php echo htmlspecialchars($h['last_watched']); ?>"
           style="opacity:0;animation:fadeUp .4s ease <?php echo ($i % 8) * 50; ?>ms both">

        <!-- Thumbnail -->
        <div class="history-thumb"
             onclick="location.href='<?php echo $SITE_URL; ?>watch.php?id=<?php echo $h['id']; ?>'">
          <img src="<?php echo $SITE_URL; ?>assets/uploads/thumbnails/<?php echo htmlspecialchars($h['thumbnail_path']); ?>"
               alt="<?php echo htmlspecialchars($h['title']); ?>"
               loading="lazy"
               onerror="this.src='assets/placeholder.jpg'">
          <div class="play-overlay"><i class="fas fa-play"></i></div>

          <?php if ($watchPct > 0): ?>
            <div class="watch-pct"><?php echo $watchPct; ?>%</div>
            <div class="progress-bar">
              <div class="progress-fill" style="width:<?php echo $watchPct; ?>%"></div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Body -->
        <div class="history-body">
          <div class="history-title-row">
            <p class="history-title"
               onclick="location.href='<?php echo $SITE_URL; ?>watch.php?id=<?php echo $h['id']; ?>'">
              <?php echo htmlspecialchars($h['title']); ?>
            </p>
            <button class="remove-btn" data-id="<?php echo $h['id']; ?>" title="Remove from history">
              <i class="fas fa-xmark"></i>
            </button>
          </div>

          <div class="history-meta">
            <span class="meta-item">
              <i class="fas fa-calendar"></i> <?php echo $dateFormatted; ?>
            </span>
            <span class="meta-item">
              <i class="fas fa-clock"></i> <?php echo $timeFormatted; ?>
            </span>
            <?php if (!empty($h['duration'])): ?>
              <span class="meta-item highlight">
                <i class="fas fa-film"></i> <?php echo htmlspecialchars($h['duration']); ?>
              </span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- No results for this filter -->
  <div class="no-results" id="noResults">
    <i class="fas fa-calendar-xmark"></i>
    <p>No videos watched in this time period.</p>
  </div>
</main>

<?php else: ?>

<!-- ── EMPTY STATE ── -->
<main class="history-main">
  <div class="empty-state animate-in">
    <div class="empty-icon"><i class="fas fa-clock-rotate-left"></i></div>
    <h2>No History Yet</h2>
    <p>Start watching videos to build your history.</p>
    <a href="<?php echo $SITE_URL; ?>" class="btn-browse">
      <i class="fas fa-play"></i> Browse Videos
    </a>
  </div>
</main>

<?php endif; ?>

<!-- ── CONFIRM MODAL ── -->
<div class="modal-backdrop" id="confirmModal">
  <div class="modal">
    <div class="modal-icon"><i class="fas fa-trash-can"></i></div>
    <h3 id="modalTitle">Clear History</h3>
    <p id="modalDesc">Are you sure you want to clear your entire watch history? This cannot be undone.</p>
    <div class="modal-actions">
      <button class="btn-cancel" id="modalCancel">Cancel</button>
      <button class="btn-confirm" id="modalConfirm">Yes, Clear All</button>
    </div>
  </div>
</div>

<!-- ── TOAST ── -->
<div class="toast" id="toast">
  <i class="fas fa-check-circle"></i>
  <span id="toastMsg">Done!</span>
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

<script>
const SITE_URL = '<?php echo $SITE_URL; ?>';

/* ── COUNT ITEMS PER FILTER ── */
function countByFilter(filter) {
  const now = new Date();
  const items = document.querySelectorAll('.history-card');
  let count = 0;
  items.forEach(item => {
    const date = new Date(item.dataset.date);
    const diffDays = Math.floor((now - date) / 864e5);
    if (filter === 'all') count++;
    else if (filter === 'today' && diffDays === 0) count++;
    else if (filter === 'week'  && diffDays <= 7)  count++;
    else if (filter === 'month' && diffDays <= 30)  count++;
  });
  return count;
}

/* ── INIT COUNTS ── */
['today','week','month'].forEach(f => {
  const el = document.getElementById('cnt-' + f);
  if (el) el.textContent = countByFilter(f);
});

/* ── FILTER TABS ── */
document.querySelectorAll('.filter-tab').forEach(tab => {
  tab.addEventListener('click', function () {
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    this.classList.add('active');

    const filter = this.dataset.filter;
    const now = new Date();
    const items = document.querySelectorAll('.history-card');
    let visible = 0;

    items.forEach(item => {
      const date = new Date(item.dataset.date);
      const diff = Math.floor((now - date) / 864e5);
      let show = false;
      if (filter === 'all')   show = true;
      else if (filter === 'today' && diff === 0) show = true;
      else if (filter === 'week'  && diff <= 7)  show = true;
      else if (filter === 'month' && diff <= 30)  show = true;

      item.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    document.getElementById('noResults').classList.toggle('visible', visible === 0);
  });
});

/* ── REMOVE INDIVIDUAL ── */
document.querySelectorAll('.remove-btn').forEach(btn => {
  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    const card  = this.closest('.history-card');
    const vidId = this.dataset.id;

    fetch(SITE_URL + 'ajax/remove-history.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'video_id=' + vidId
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        card.style.transition = 'opacity .3s ease, transform .3s ease';
        card.style.opacity = '0';
        card.style.transform = 'scale(.95)';
        setTimeout(() => {
          card.remove();
          updateAllCount();
          showToast('Removed from history', 'fa-check-circle');
        }, 300);
      }
    })
    .catch(() => showToast('Something went wrong', 'fa-triangle-exclamation'));
  });
});

function updateAllCount() {
  const el = document.getElementById('cnt-all');
  if (el) el.textContent = document.querySelectorAll('.history-card').length;
}

/* ── CLEAR ALL ── */
let clearTarget = null;

document.getElementById('clearHistoryBtn')?.addEventListener('click', () => {
  clearTarget = 'all';
  document.getElementById('modalTitle').textContent = 'Clear All History';
  document.getElementById('modalDesc').textContent  = 'Are you sure you want to clear your entire watch history? This cannot be undone.';
  document.getElementById('modalConfirm').textContent = 'Yes, Clear All';
  document.getElementById('confirmModal').classList.add('open');
});

document.getElementById('modalCancel')?.addEventListener('click', () => {
  document.getElementById('confirmModal').classList.remove('open');
  clearTarget = null;
});

document.getElementById('modalConfirm')?.addEventListener('click', () => {
  document.getElementById('confirmModal').classList.remove('open');
  if (clearTarget !== 'all') return;

  fetch(SITE_URL + 'ajax/clear-history.php', { method: 'POST' })
    .then(r => r.json())
    .then(data => { if (data.success) location.reload(); })
    .catch(() => showToast('Something went wrong', 'fa-triangle-exclamation'));
});

/* Close modal on backdrop click */
document.getElementById('confirmModal')?.addEventListener('click', function(e) {
  if (e.target === this) this.classList.remove('open');
});

/* ── TOAST ── */
function showToast(msg, icon = 'fa-check-circle') {
  const toast = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  toast.querySelector('i').className = 'fas ' + icon;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3000);
}
</script>

</body>
</html>

<?php require_once 'includes/footer.php'; ?>