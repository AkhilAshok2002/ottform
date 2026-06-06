<?php
$pageTitle = 'My List';
require_once 'includes/header.php';
require_once 'includes/functions.php';

if (!$auth->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

// Handle remove
if (isset($_POST['remove'])) {
    $video->toggleFavorite($_SESSION['user_id'], (int)$_POST['video_id']);
    header('Location: mylist.php');
    exit();
}

$favorites = $video->getFavorites($_SESSION['user_id']);
$SITE_URL  = rtrim(SITE_URL, '/') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My List — StreamVault</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0 }
    :root {
      --bg: #080808; --surface: #111111; --surface2: #1a1a1a;
      --border: rgba(255,255,255,.07); --red: #e50914; --red-dim: #b8070f;
      --gold: #f5c518; --text: #f0f0f0; --muted: #888;
      --font-display: 'Bebas Neue', sans-serif;
      --font-body: 'DM Sans', sans-serif;
      --card-r: 12px; --trans: .3s cubic-bezier(.4,0,.2,1);
    }
    html { scroll-behavior: smooth }
    body { font-family: var(--font-body); background: var(--bg); color: var(--text); overflow-x: hidden; -webkit-font-smoothing: antialiased }
    a { text-decoration: none; color: inherit }
    img { display: block; width: 100%; object-fit: cover }
    ::-webkit-scrollbar { width: 6px } ::-webkit-scrollbar-track { background: var(--bg) } ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px }

    /* ── PAGE BANNER ── */
    .page-banner {
      position: relative; padding: 120px 4vw 56px; overflow: hidden;
    }
    .page-banner::before {
      content: ''; position: absolute; inset: 0;
      background: radial-gradient(ellipse 55% 80% at 15% 50%, rgba(229,9,20,.07) 0%, transparent 65%);
      pointer-events: none;
    }
    .page-banner::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 1px; background: var(--border) }
    .banner-eyebrow { display: inline-flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--red); margin-bottom: 12px }
    .banner-title { font-family: var(--font-display); font-size: clamp(44px, 7vw, 84px); letter-spacing: 3px; line-height: .92; margin-bottom: 14px }
    .banner-title span { color: var(--red) }
    .banner-meta { display: flex; align-items: center; gap: 16px }
    .banner-count { font-size: 14px; color: var(--muted) }
    .banner-count strong { color: var(--text); font-weight: 600 }

    /* ── TOOLBAR ── */
    .toolbar {
      padding: 18px 4vw;
      background: var(--surface); border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;
      position: sticky; top: 0; z-index: 100; backdrop-filter: blur(20px);
    }
    .view-toggle { display: flex; align-items: center; gap: 6px }
    .view-btn {
      width: 36px; height: 36px; border-radius: 8px;
      background: var(--surface2); border: 1px solid var(--border);
      color: var(--muted); font-size: 14px;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: all var(--trans);
    }
    .view-btn.active { background: var(--red); border-color: var(--red); color: #fff }
    .view-btn:hover:not(.active) { border-color: rgba(255,255,255,.15); color: var(--text) }
    .sort-wrap { display: flex; align-items: center; gap: 10px; margin-left: auto }
    .sort-label { font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--muted) }
    .sort-select {
      appearance: none; background: var(--surface2); border: 1px solid var(--border);
      color: var(--text); font-family: var(--font-body); font-size: 13px;
      padding: 8px 34px 8px 14px; border-radius: 8px; cursor: pointer; transition: border-color var(--trans);
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 10px center;
    }
    .sort-select:focus { outline: none; border-color: var(--red) }
    .sort-select option { background: #1a1a1a }

    /* ── MAIN ── */
    .list-main { padding: 40px 4vw 80px }

    /* ── GRID VIEW ── */
    .list-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 20px;
    }
    .list-grid.hidden { display: none }

    /* ── VIDEO CARD (matches index.php) ── */
    .video-card {
      position: relative; border-radius: var(--card-r); overflow: hidden;
      background: var(--surface); cursor: pointer;
      transition: transform var(--trans), box-shadow var(--trans);
    }
    .video-card:hover {
      transform: scale(1.04) translateY(-4px);
      box-shadow: 0 20px 60px rgba(0,0,0,.7), 0 0 0 1px rgba(255,255,255,.08);
      z-index: 2;
    }
    .card-img { position: relative; overflow: hidden; aspect-ratio: 2/3 }
    .card-img img { height: 100%; transition: transform .5s ease }
    .video-card:hover .card-img img { transform: scale(1.08) }
    .play-overlay {
      position: absolute; inset: 0; background: rgba(0,0,0,.45);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; transition: opacity var(--trans);
    }
    .video-card:hover .play-overlay { opacity: 1 }
    .play-overlay i { font-size: 44px; color: #fff; filter: drop-shadow(0 0 20px rgba(229,9,20,.6)); transition: transform var(--trans) }
    .video-card:hover .play-overlay i { transform: scale(1.1) }
    /* Remove button overlaid on card */
    .card-remove {
      position: absolute; top: 10px; right: 10px;
      width: 30px; height: 30px; border-radius: 50%;
      background: rgba(0,0,0,.65); backdrop-filter: blur(6px);
      border: 1px solid rgba(255,255,255,.15);
      color: var(--muted); font-size: 12px;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: all var(--trans);
      opacity: 0; z-index: 3;
    }
    .video-card:hover .card-remove { opacity: 1 }
    .card-remove:hover { background: var(--red); border-color: var(--red); color: #fff }
    .card-info { padding: 12px 14px 14px }
    .card-info h3 { font-size: 14px; font-weight: 600; margin-bottom: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
    .meta { display: flex; align-items: center; gap: 10px }
    .rating { display: flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600; color: var(--gold) }
    .year { font-size: 12px; color: var(--muted) }
    .card-category { font-size: 11px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: rgba(229,9,20,.8); margin-top: 6px; display: block }

    /* ── LIST VIEW ── */
    .list-rows { display: flex; flex-direction: column; gap: 14px }
    .list-rows.hidden { display: none }

    .list-row {
      display: flex; align-items: stretch; gap: 0;
      background: var(--surface); border: 1px solid var(--border);
      border-radius: var(--card-r); overflow: hidden;
      transition: border-color var(--trans), box-shadow var(--trans);
    }
    .list-row:hover {
      border-color: rgba(255,255,255,.1);
      box-shadow: 0 8px 32px rgba(0,0,0,.5);
    }
    .row-thumb {
      flex-shrink: 0; width: 110px;
      position: relative; cursor: pointer; overflow: hidden;
      background: var(--surface2);
    }
    .row-thumb img { height: 100%; object-fit: cover; transition: transform .5s ease }
    .list-row:hover .row-thumb img { transform: scale(1.06) }
    .row-play {
      position: absolute; inset: 0; background: rgba(0,0,0,.4);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; transition: opacity var(--trans);
    }
    .list-row:hover .row-play { opacity: 1 }
    .row-play i { font-size: 24px; color: #fff; filter: drop-shadow(0 0 10px rgba(229,9,20,.6)) }

    .row-body {
      flex: 1; padding: 16px 20px;
      display: flex; flex-direction: column; justify-content: center; min-width: 0;
    }
    .row-title {
      font-size: 16px; font-weight: 700;
      margin-bottom: 8px; cursor: pointer;
      transition: color var(--trans);
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .row-title:hover { color: var(--red) }
    .row-meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 10px }
    .row-meta .rating { display: flex; align-items: center; gap: 4px; font-size: 13px; font-weight: 600; color: var(--gold) }
    .row-meta .year, .row-meta .duration { font-size: 13px; color: var(--muted) }
    .row-meta .cat-tag { font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: rgba(229,9,20,.8) }
    .row-desc { font-size: 13px; color: rgba(255,255,255,.55); line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden }

    .row-actions {
      flex-shrink: 0; display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      gap: 10px; padding: 16px 20px;
      border-left: 1px solid var(--border);
    }
    .btn-watch {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--red); color: #fff;
      font-weight: 700; font-size: 13px;
      padding: 10px 20px; border-radius: 8px; border: none;
      cursor: pointer; white-space: nowrap;
      transition: background var(--trans), transform var(--trans);
      box-shadow: 0 4px 16px rgba(229,9,20,.3);
    }
    .btn-watch:hover { background: var(--red-dim); transform: translateY(-1px) }
    .btn-remove {
      display: inline-flex; align-items: center; gap: 7px;
      background: transparent; color: var(--muted);
      font-weight: 600; font-size: 13px;
      padding: 9px 16px; border-radius: 8px;
      border: 1px solid var(--border); cursor: pointer; white-space: nowrap;
      transition: all var(--trans);
    }
    .btn-remove:hover { background: rgba(229,9,20,.08); border-color: rgba(229,9,20,.3); color: var(--red) }

    /* ── EMPTY STATE ── */
    .empty-state {
      display: flex; flex-direction: column; align-items: center; gap: 16px;
      padding: 100px 20px; text-align: center;
    }
    .empty-icon {
      width: 88px; height: 88px; border-radius: 50%;
      background: var(--surface); border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      font-size: 32px; color: var(--muted);
    }
    .empty-state h2 { font-family: var(--font-display); font-size: 34px; letter-spacing: 2px }
    .empty-state p { font-size: 15px; color: var(--muted); max-width: 360px; line-height: 1.6 }
    .btn-browse {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--red); color: #fff; font-weight: 700; font-size: 14px;
      padding: 13px 28px; border-radius: 8px; margin-top: 8px;
      transition: background var(--trans), transform var(--trans);
    }
    .btn-browse:hover { background: var(--red-dim); transform: translateY(-2px) }

    /* ── CONFIRM MODAL ── */
    .modal-backdrop {
      position: fixed; inset: 0; z-index: 2000;
      background: rgba(0,0,0,.85); backdrop-filter: blur(12px);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; pointer-events: none; transition: opacity var(--trans);
    }
    .modal-backdrop.open { opacity: 1; pointer-events: all }
    .modal {
      background: var(--surface); border: 1px solid var(--border);
      border-radius: 16px; padding: 36px 40px; max-width: 380px; width: 90%;
      text-align: center; transform: scale(.95); transition: transform var(--trans);
    }
    .modal-backdrop.open .modal { transform: scale(1) }
    .modal-icon { width: 56px; height: 56px; border-radius: 50%; background: rgba(229,9,20,.1); border: 1px solid rgba(229,9,20,.25); display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--red); margin: 0 auto 20px }
    .modal h3 { font-family: var(--font-display); font-size: 26px; letter-spacing: 2px; margin-bottom: 10px }
    .modal p { font-size: 14px; color: var(--muted); line-height: 1.6; margin-bottom: 28px }
    .modal-actions { display: flex; gap: 12px; justify-content: center }
    .btn-cancel { padding: 11px 24px; border-radius: 8px; background: var(--surface2); border: 1px solid var(--border); color: var(--muted); font-family: var(--font-body); font-size: 14px; font-weight: 600; cursor: pointer; transition: all var(--trans) }
    .btn-cancel:hover { color: var(--text); border-color: rgba(255,255,255,.15) }
    .btn-confirm { padding: 11px 24px; border-radius: 8px; background: var(--red); border: none; color: #fff; font-family: var(--font-body); font-size: 14px; font-weight: 700; cursor: pointer; transition: background var(--trans), transform var(--trans) }
    .btn-confirm:hover { background: var(--red-dim); transform: translateY(-1px) }

    /* ── TOAST ── */
    .toast {
      position: fixed; bottom: 32px; left: 50%; transform: translateX(-50%) translateY(20px);
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
    .footer { padding: 48px 4vw 32px; border-top: 1px solid var(--border); margin-top: 40px }
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
    .footer-bottom { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding-top: 24px; border-top: 1px solid var(--border) }
    .footer-copy { font-size: 13px; color: var(--muted) }
    .footer-socials { display: flex; gap: 14px }
    .footer-socials a { width: 36px; height: 36px; border-radius: 50%; background: var(--surface); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--muted); font-size: 14px; transition: all var(--trans) }
    .footer-socials a:hover { background: var(--red); border-color: var(--red); color: #fff }

    /* ── ANIMATIONS ── */
    @keyframes fadeUp { from { opacity: 0; transform: translateY(20px) } to { opacity: 1; transform: translateY(0) } }
    .animate-in { animation: fadeUp .5s ease both }

    /* ── RESPONSIVE ── */
    @media(max-width:900px) {
      .list-grid { grid-template-columns: repeat(auto-fill, minmax(155px, 1fr)) }
      .row-desc { display: none }
    }
    @media(max-width:600px) {
      .list-grid { grid-template-columns: repeat(2, 1fr); gap: 12px }
      .toolbar { flex-direction: column; align-items: flex-start; gap: 10px }
      .sort-wrap { margin-left: 0; width: 100% }
      .sort-select { width: 100% }
      .row-thumb { width: 88px }
      .row-actions { padding: 12px 14px }
      .btn-watch span, .btn-remove span { display: none }
    }
  </style>
</head>
<body>

<!-- ── PAGE BANNER ── -->
<div class="page-banner animate-in">
  <div class="banner-eyebrow"><i class="fas fa-heart"></i> My Account</div>
  <h1 class="banner-title">MY <span>LIST</span></h1>
  <div class="banner-meta">
    <span class="banner-count">
      <strong><?php echo count($favorites); ?></strong>
      title<?php echo count($favorites) !== 1 ? 's' : ''; ?> saved
    </span>
  </div>
</div>

<?php if (!empty($favorites)): ?>

<!-- ── TOOLBAR ── -->
<div class="toolbar">
  <!-- View toggle -->
  <div class="view-toggle">
    <button class="view-btn active" id="gridBtn" title="Grid view"><i class="fas fa-grip"></i></button>
    <button class="view-btn" id="listBtn" title="List view"><i class="fas fa-list"></i></button>
  </div>
  <!-- Sort -->
  <div class="sort-wrap">
    <span class="sort-label">Sort</span>
    <select class="sort-select" id="sortSelect">
      <option value="default">Date Added</option>
      <option value="title">Title A–Z</option>
      <option value="rating">Highest Rated</option>
      <option value="year">Newest Year</option>
    </select>
  </div>
</div>

<!-- ── LIST MAIN ── -->
<main class="list-main">

  <!-- GRID VIEW -->
  <div class="list-grid" id="gridView">
    <?php foreach ($favorites as $i => $item): ?>
      <div class="video-card"
           data-title="<?php echo htmlspecialchars($item['title']); ?>"
           data-rating="<?php echo $item['rating']; ?>"
           data-year="<?php echo $item['release_year']; ?>"
           style="opacity:0;animation:fadeUp .45s ease <?php echo ($i % 8) * 50; ?>ms both">

        <!-- Remove on hover -->
        <button class="card-remove" onclick="confirmRemove(<?php echo $item['id']; ?>, '<?php echo addslashes(htmlspecialchars($item['title'])); ?>')" title="Remove from list">
          <i class="fas fa-xmark"></i>
        </button>

        <div class="card-img" onclick="location.href='<?php echo $SITE_URL; ?>watch.php?id=<?php echo $item['id']; ?>'">
          <img src="<?php echo THUMBNAIL_URL . htmlspecialchars($item['thumbnail_path']); ?>"
               alt="<?php echo htmlspecialchars($item['title']); ?>"
               loading="lazy"
               onerror="this.src='assets/placeholder.jpg'">
          <div class="play-overlay"><i class="fas fa-play"></i></div>
        </div>

        <div class="card-info">
          <h3><?php echo htmlspecialchars($item['title']); ?></h3>
          <div class="meta">
            <?php if ($item['rating']): ?>
              <span class="rating"><i class="fas fa-star"></i> <?php echo $item['rating']; ?></span>
            <?php endif; ?>
            <?php if ($item['release_year']): ?>
              <span class="year"><?php echo $item['release_year']; ?></span>
            <?php endif; ?>
          </div>
          <?php if (!empty($item['category_name'])): ?>
            <span class="card-category"><?php echo htmlspecialchars($item['category_name']); ?></span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- LIST VIEW -->
  <div class="list-rows hidden" id="listView">
    <?php foreach ($favorites as $i => $item): ?>
      <div class="list-row"
           data-title="<?php echo htmlspecialchars($item['title']); ?>"
           data-rating="<?php echo $item['rating']; ?>"
           data-year="<?php echo $item['release_year']; ?>"
           style="opacity:0;animation:fadeUp .4s ease <?php echo $i * 40; ?>ms both">

        <!-- Thumbnail -->
        <div class="row-thumb" onclick="location.href='<?php echo $SITE_URL; ?>watch.php?id=<?php echo $item['id']; ?>'">
          <img src="<?php echo THUMBNAIL_URL . htmlspecialchars($item['thumbnail_path']); ?>"
               alt="<?php echo htmlspecialchars($item['title']); ?>"
               loading="lazy"
               onerror="this.src='assets/placeholder.jpg'">
          <div class="row-play"><i class="fas fa-play"></i></div>
        </div>

        <!-- Info -->
        <div class="row-body">
          <p class="row-title" onclick="location.href='<?php echo $SITE_URL; ?>watch.php?id=<?php echo $item['id']; ?>'">
            <?php echo htmlspecialchars($item['title']); ?>
          </p>
          <div class="row-meta">
            <?php if ($item['rating']): ?>
              <span class="rating"><i class="fas fa-star"></i> <?php echo $item['rating']; ?></span>
            <?php endif; ?>
            <?php if ($item['release_year']): ?>
              <span class="year"><?php echo $item['release_year']; ?></span>
            <?php endif; ?>
            <?php if ($item['duration']): ?>
              <span class="duration"><?php echo htmlspecialchars($item['duration']); ?></span>
            <?php endif; ?>
            <?php if (!empty($item['category_name'])): ?>
              <span class="cat-tag"><?php echo htmlspecialchars($item['category_name']); ?></span>
            <?php endif; ?>
          </div>
          <?php if (!empty($item['description'])): ?>
            <p class="row-desc"><?php echo htmlspecialchars(substr($item['description'], 0, 150)); ?>…</p>
          <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="row-actions">
          <button class="btn-watch" onclick="location.href='<?php echo $SITE_URL; ?>watch.php?id=<?php echo $item['id']; ?>'">
            <i class="fas fa-play"></i> <span>Watch Now</span>
          </button>
          <button class="btn-remove" onclick="confirmRemove(<?php echo $item['id']; ?>, '<?php echo addslashes(htmlspecialchars($item['title'])); ?>')">
            <i class="fas fa-heart-crack"></i> <span>Remove</span>
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</main>

<?php else: ?>

<!-- ── EMPTY STATE ── -->
<main class="list-main">
  <div class="empty-state animate-in">
    <div class="empty-icon"><i class="fas fa-heart"></i></div>
    <h2>Your List is Empty</h2>
    <p>Save movies and shows you want to watch later by tapping the heart icon on any title.</p>
    <a href="<?php echo $SITE_URL; ?>" class="btn-browse">
      <i class="fas fa-compass"></i> Browse Content
    </a>
  </div>
</main>

<?php endif; ?>

<!-- ── CONFIRM MODAL ── -->
<div class="modal-backdrop" id="confirmModal">
  <div class="modal">
    <div class="modal-icon"><i class="fas fa-heart-crack"></i></div>
    <h3>Remove Title</h3>
    <p id="modalDesc">Remove this from your list?</p>
    <div class="modal-actions">
      <button class="btn-cancel" id="modalCancel">Cancel</button>
      <button class="btn-confirm" id="modalConfirm">Remove</button>
    </div>
  </div>
</div>

<!-- Hidden form for POST remove -->
<form method="POST" id="removeForm" style="display:none">
  <input type="hidden" name="video_id" id="removeVideoId">
  <input type="hidden" name="remove" value="1">
</form>

<!-- ── TOAST ── -->
<div class="toast" id="toast"><i class="fas fa-check-circle"></i><span id="toastMsg">Done!</span></div>

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
          <li><a href="<?php echo $SITE_URL; ?>history.php">Watch History</a></li>
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
  /* ── VIEW TOGGLE ── */
  const gridBtn  = document.getElementById('gridBtn');
  const listBtn  = document.getElementById('listBtn');
  const gridView = document.getElementById('gridView');
  const listView = document.getElementById('listView');

  function setView(v) {
    const isGrid = v === 'grid';
    gridBtn.classList.toggle('active', isGrid);
    listBtn.classList.toggle('active', !isGrid);
    gridView?.classList.toggle('hidden', !isGrid);
    listView?.classList.toggle('hidden', isGrid);
    localStorage.setItem('sv_list_view', v);
  }

  gridBtn?.addEventListener('click', () => setView('grid'));
  listBtn?.addEventListener('click', () => setView('list'));

  // Restore preference (in-memory only since localStorage not supported; defaults to grid)
  // setView(localStorage.getItem('sv_list_view') || 'grid');

  /* ── SORT ── */
  document.getElementById('sortSelect')?.addEventListener('change', function () {
    const sortBy = this.value;
    const sortCards = (container, selector) => {
      const cards = [...container.querySelectorAll(selector)];
      cards.sort((a, b) => {
        if (sortBy === 'title')  return a.dataset.title.localeCompare(b.dataset.title);
        if (sortBy === 'rating') return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
        if (sortBy === 'year')   return parseInt(b.dataset.year) - parseInt(a.dataset.year);
        return 0;
      });
      cards.forEach(c => container.appendChild(c));
    };
    if (gridView) sortCards(gridView, '.video-card');
    if (listView) sortCards(listView, '.list-row');
  });

  /* ── CONFIRM REMOVE ── */
  let pendingId = null;

  function confirmRemove(videoId, title) {
    pendingId = videoId;
    document.getElementById('modalDesc').textContent = `Remove "${title}" from your list?`;
    document.getElementById('confirmModal').classList.add('open');
  }

  document.getElementById('modalCancel')?.addEventListener('click', () => {
    document.getElementById('confirmModal').classList.remove('open');
    pendingId = null;
  });

  document.getElementById('confirmModal')?.addEventListener('click', function (e) {
    if (e.target === this) { this.classList.remove('open'); pendingId = null; }
  });

  document.getElementById('modalConfirm')?.addEventListener('click', () => {
    if (!pendingId) return;
    document.getElementById('confirmModal').classList.remove('open');
    document.getElementById('removeVideoId').value = pendingId;
    document.getElementById('removeForm').submit();
  });

  /* ── TOAST ── */
  function showToast(msg, icon = 'fa-check-circle') {
    const t = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    t.querySelector('i').className = 'fas ' + icon;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }
</script>

</body>
</html>

<?php require_once 'includes/footer.php'; ?>