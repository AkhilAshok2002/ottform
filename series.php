<?php
$pageTitle = 'Series';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Get category filter
$categoryId = $_GET['category'] ?? 0;

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 24;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT v.*, c.name as category_name FROM videos v 
          INNER JOIN categories c ON v.category_id = c.id 
          WHERE c.type = 'series'";
$countQuery = "SELECT COUNT(*) as count FROM videos v 
               INNER JOIN categories c ON v.category_id = c.id 
               WHERE c.type = 'series'";
$params = [];

if ($categoryId) {
    $query .= " AND v.category_id = ?";
    $countQuery .= " AND v.category_id = ?";
    $params[] = $categoryId;
}

$query .= " ORDER BY v.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $db->prepare($countQuery);
if ($categoryId) { $stmt->execute([$categoryId]); } else { $stmt->execute(); }
$totalSeries = $stmt->fetch()['count'];

$stmt = $db->prepare($query);
$stmt->execute($params);
$series = $stmt->fetchAll();

$totalPages = ceil($totalSeries / $limit);

$categories = $db->query("SELECT * FROM categories WHERE type = 'series' ORDER BY name")->fetchAll();

$hasEpisodesTable = false;
try {
    $hasEpisodesTable = (bool) $db->query("SHOW TABLES LIKE 'episodes'")->fetchColumn();
} catch (PDOException $e) { $hasEpisodesTable = false; }

foreach ($series as &$show) {
    if ($hasEpisodesTable) {
        $stmt = $db->prepare("SELECT COUNT(DISTINCT season) as season_count FROM episodes WHERE video_id = ?");
        $stmt->execute([$show['id']]);
        $show['seasons'] = $stmt->fetch()['season_count'] ?? 1;
    } else {
        $show['seasons'] = 1;
    }
}
unset($show);

// Trending series
if ($hasEpisodesTable) {
    $trendingSeries = $db->query("
        SELECT v.*, c.name as category_name,
               COUNT(DISTINCT e.id) as episodes,
               COUNT(DISTINCT e.season) as seasons
        FROM videos v
        INNER JOIN categories c ON v.category_id = c.id
        LEFT JOIN episodes e ON v.id = e.video_id
        WHERE c.type = 'series'
        GROUP BY v.id
        ORDER BY v.views DESC
        LIMIT 6
    ")->fetchAll();
} else {
    $trendingSeries = $db->query("
        SELECT v.*, c.name as category_name, 0 as episodes, 1 as seasons
        FROM videos v
        INNER JOIN categories c ON v.category_id = c.id
        WHERE c.type = 'series'
        ORDER BY v.views DESC
        LIMIT 6
    ")->fetchAll();
}

$SITE_URL = rtrim(SITE_URL, '/') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TV Series — StreamVault</title>
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
    ::-webkit-scrollbar { width: 6px; height: 6px }
    ::-webkit-scrollbar-track { background: var(--bg) }
    ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px }

    /* ── PAGE BANNER ── */
    .page-banner {
      position: relative;
      padding: 120px 4vw 64px;
      overflow: hidden;
    }
    .page-banner::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse 60% 80% at 20% 50%, rgba(229,9,20,.07) 0%, transparent 70%),
        linear-gradient(180deg, rgba(229,9,20,.04) 0%, transparent 100%);
      pointer-events: none;
    }
    .page-banner::after {
      content: '';
      position: absolute;
      bottom: 0; left: 0; right: 0;
      height: 1px;
      background: var(--border);
    }
    .banner-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--red);
      margin-bottom: 12px;
    }
    .banner-title {
      font-family: var(--font-display);
      font-size: clamp(48px, 7vw, 88px);
      letter-spacing: 3px;
      line-height: .92;
      margin-bottom: 14px;
      position: relative;
    }
    .banner-title span { color: var(--red) }
    .banner-meta { display: flex; align-items: center; gap: 16px }
    .banner-count { font-size: 14px; color: var(--muted) }
    .banner-count strong { color: var(--text); font-weight: 600 }
    .banner-dot { width: 4px; height: 4px; border-radius: 50%; background: var(--muted) }

    /* ── FILTERS ── */
    .filters-section {
      padding: 22px 4vw;
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      position: sticky;
      top: 0;
      z-index: 100;
      backdrop-filter: blur(20px);
    }
    .filters-inner { display: flex; align-items: center; gap: 14px; flex-wrap: wrap }
    .filters-label {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--muted);
    }
    .filter-pills { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; flex: 1 }
    .filter-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 16px;
      border-radius: 30px;
      background: var(--surface2);
      border: 1px solid var(--border);
      font-size: 13px;
      font-weight: 500;
      color: var(--muted);
      cursor: pointer;
      transition: all var(--trans);
      white-space: nowrap;
    }
    .filter-pill:hover { border-color: rgba(229,9,20,.4); color: var(--text) }
    .filter-pill.active { background: var(--red); border-color: var(--red); color: #fff }
    .sort-wrap { display: flex; align-items: center; gap: 10px; margin-left: auto }
    .sort-select {
      appearance: none;
      background: var(--surface2);
      border: 1px solid var(--border);
      color: var(--text);
      font-family: var(--font-body);
      font-size: 13px;
      padding: 8px 36px 8px 14px;
      border-radius: 8px;
      cursor: pointer;
      transition: border-color var(--trans);
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
    }
    .sort-select:focus { outline: none; border-color: var(--red) }
    .sort-select option { background: #1a1a1a }

    /* ── MAIN ── */
    .series-main { padding: 48px 4vw 0 }

    /* ── VIDEO CARD (matches index.php) ── */
    .video-card {
      position: relative;
      border-radius: var(--card-r);
      overflow: hidden;
      background: var(--surface);
      cursor: pointer;
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
      position: absolute; inset: 0;
      background: rgba(0,0,0,.4);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; transition: opacity var(--trans);
    }
    .video-card:hover .play-overlay { opacity: 1 }
    .play-overlay i {
      font-size: 44px; color: #fff;
      filter: drop-shadow(0 0 20px rgba(229,9,20,.6));
      transition: transform var(--trans);
    }
    .video-card:hover .play-overlay i { transform: scale(1.1) }

    /* Season badge — top left */
    .card-badge {
      position: absolute;
      top: 10px; left: 10px;
      background: rgba(229,9,20,.85);
      backdrop-filter: blur(6px);
      color: #fff;
      font-size: 10px;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 4px;
      letter-spacing: .5px;
      text-transform: uppercase;
    }
    .card-badge.premium-badge {
      background: linear-gradient(135deg, #f5c518, #e09000);
      color: #000;
      z-index: 5;
    }
    .premium-badge i { margin-right: 3px; font-size: 10px; }
    /* TV badge — top right */
    .tv-badge {
      position: absolute;
      top: 10px; right: 10px;
      background: rgba(0,0,0,.65);
      backdrop-filter: blur(6px);
      border: 1px solid rgba(255,255,255,.15);
      color: var(--muted);
      font-size: 10px;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 4px;
      letter-spacing: .5px;
      text-transform: uppercase;
    }

    .card-info { padding: 12px 14px 14px }
    .card-info h3 {
      font-size: 14px; font-weight: 600;
      margin-bottom: 8px;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .meta { display: flex; align-items: center; gap: 10px }
    .rating { display: flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600; color: var(--gold) }
    .year { font-size: 12px; color: var(--muted) }
    .card-category {
      font-size: 11px; font-weight: 600;
      letter-spacing: .5px; text-transform: uppercase;
      color: rgba(229,9,20,.8);
      margin-top: 6px; display: block;
    }
    .card-desc {
      font-size: 12px;
      color: var(--muted);
      margin-top: 6px;
      line-height: 1.5;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    /* ── SERIES GRID ── */
    .series-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 20px;
    }

    /* ── EMPTY STATE ── */
    .empty-state {
      display: flex; flex-direction: column; align-items: center; justify-content: center;
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

    /* ── PAGINATION ── */
    .pagination {
      display: flex; align-items: center; justify-content: center;
      gap: 8px; margin-top: 60px; padding-bottom: 0;
    }
    .page-btn {
      display: inline-flex; align-items: center; justify-content: center;
      min-width: 40px; height: 40px; padding: 0 8px;
      border-radius: 8px; background: var(--surface);
      border: 1px solid var(--border);
      font-size: 14px; font-weight: 600; color: var(--muted);
      transition: all var(--trans); cursor: pointer;
    }
    .page-btn:hover { border-color: rgba(229,9,20,.4); color: var(--text) }
    .page-btn.active { background: var(--red); border-color: var(--red); color: #fff }
    .page-ellipsis { color: var(--muted); font-size: 14px; padding: 0 4px }

    /* ── TRENDING SECTION ── */
    .section {
      padding: 60px 4vw;
    }
    .section-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 28px;
    }
    .section-title {
      font-family: var(--font-display);
      font-size: clamp(22px, 3vw, 30px);
      letter-spacing: 2px;
      position: relative;
      padding-left: 16px;
    }
    .section-title::before {
      content: '';
      position: absolute; left: 0; top: 10%; bottom: 10%;
      width: 3px; background: var(--red); border-radius: 2px;
    }
    .view-all {
      font-size: 13px; font-weight: 600; color: var(--muted);
      display: flex; align-items: center; gap: 6px;
      transition: color var(--trans);
    }
    .view-all:hover { color: var(--red) }
    .view-all i { font-size: 11px; transition: transform var(--trans) }
    .view-all:hover i { transform: translateX(4px) }

    /* Trending horizontal scroll */
    .slider-wrap { position: relative }
    .slider-track {
      display: flex; gap: 16px;
      overflow-x: auto;
      scroll-snap-type: x mandatory;
      scrollbar-width: none;
      padding-bottom: 8px;
    }
    .slider-track::-webkit-scrollbar { display: none }

    /* Trending card — landscape 16:9 */
    .trending-card {
      flex-shrink: 0;
      width: 280px;
      scroll-snap-align: start;
      border-radius: var(--card-r);
      overflow: hidden;
      background: var(--surface);
      cursor: pointer;
      transition: transform var(--trans), box-shadow var(--trans);
    }
    .trending-card:hover {
      transform: scale(1.03) translateY(-4px);
      box-shadow: 0 20px 50px rgba(0,0,0,.7), 0 0 0 1px rgba(255,255,255,.08);
    }
    .trending-card .card-img { aspect-ratio: 16/9 }
    .trending-card .card-img img { height: 100%; transition: transform .5s ease }
    .trending-card:hover .card-img img { transform: scale(1.08) }
    .trending-card .play-overlay { opacity: 0; transition: opacity var(--trans) }
    .trending-card:hover .play-overlay { opacity: 1 }
    .rank-badge {
      position: absolute;
      bottom: 10px; left: 10px;
      background: linear-gradient(135deg, #f5c518, #e09000);
      color: #000;
      font-family: var(--font-display);
      font-size: 18px;
      letter-spacing: 1px;
      padding: 2px 10px;
      border-radius: 4px;
    }
    .trending-card .card-info { padding: 12px 14px 14px }
    .trending-card .card-info h4 {
      font-size: 14px; font-weight: 600;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
      margin-bottom: 8px;
    }
    .trending-stats {
      display: flex; align-items: center; gap: 14px;
    }
    .trending-stats span {
      display: flex; align-items: center; gap: 5px;
      font-size: 12px; color: var(--muted);
    }
    .trending-stats .fa-star { color: var(--gold) }
    .trending-stats .fa-eye { color: var(--red) }

    .slider-btn {
      position: absolute; top: 50%; transform: translateY(-60%);
      width: 44px; height: 44px; border-radius: 50%;
      background: rgba(20,20,20,.9); border: 1px solid rgba(255,255,255,.1);
      color: #fff; font-size: 16px; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: background var(--trans), transform var(--trans), opacity var(--trans);
      z-index: 10; opacity: 0; backdrop-filter: blur(8px);
    }
    .slider-wrap:hover .slider-btn { opacity: 1 }
    .slider-btn:hover { background: var(--red); border-color: var(--red); transform: translateY(-60%) scale(1.1) }
    .slider-btn.prev { left: -22px }
    .slider-btn.next { right: -22px }

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
      from { opacity: 0; transform: translateY(24px) }
      to   { opacity: 1; transform: translateY(0) }
    }

    /* ── RESPONSIVE ── */
    @media(max-width:900px) {
      .series-grid { grid-template-columns: repeat(auto-fill, minmax(155px, 1fr)) }
    }
    @media(max-width:600px) {
      .series-grid { grid-template-columns: repeat(2, 1fr); gap: 12px }
      .filter-pills { gap: 6px }
      .sort-wrap { margin-left: 0; width: 100% }
      .sort-select { width: 100% }
      .banner-title { font-size: 52px }
    }
  </style>
</head>
<body>

<!-- ── PAGE BANNER ── -->
<div class="page-banner">
  <div class="banner-eyebrow"><i class="fas fa-tv"></i> StreamVault</div>
  <h1 class="banner-title">TV <span>SERIES</span></h1>
  <div class="banner-meta">
    <span class="banner-count">
      <strong><?php echo number_format($totalSeries); ?></strong> shows available
    </span>
    <?php if ($categoryId && !empty($categories)):
      $activeCat = reset(array_filter($categories, fn($c) => $c['id'] == $categoryId));
      if ($activeCat): ?>
        <span class="banner-dot"></span>
        <span class="banner-count">Filtered by <strong><?php echo htmlspecialchars($activeCat['name']); ?></strong></span>
    <?php endif; endif; ?>
  </div>
</div>

<!-- ── FILTERS ── -->
<div class="filters-section">
  <div class="filters-inner">
    <span class="filters-label"><i class="fas fa-sliders" style="margin-right:6px"></i>Genre</span>
    <div class="filter-pills">
      <a href="?page=1" class="filter-pill <?php echo !$categoryId ? 'active' : ''; ?>">All</a>
      <?php foreach ($categories as $cat): ?>
        <a href="?category=<?php echo $cat['id']; ?>&page=1"
           class="filter-pill <?php echo $categoryId == $cat['id'] ? 'active' : ''; ?>">
          <?php echo htmlspecialchars($cat['name']); ?>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="sort-wrap">
      <span class="filters-label">Sort</span>
      <select class="sort-select" id="sortSelect">
        <option value="newest">Newest First</option>
        <option value="popular">Most Popular</option>
        <option value="rating">Highest Rated</option>
      </select>
    </div>
  </div>
</div>

<!-- ── MAIN GRID ── -->
<main class="series-main">

  <?php if (empty($series)): ?>
  <div class="empty-state" style="animation:fadeUp .5s ease both">
    <div class="empty-icon"><i class="fas fa-tv"></i></div>
    <h2>No Series Found</h2>
    <p>We couldn't find any shows in this category.</p>
    <a href="series.php" class="btn-browse"><i class="fas fa-th"></i> Browse All Series</a>
  </div>

  <?php else: ?>
  <div class="series-grid">
    <?php foreach ($series as $i => $show): ?>
      <div class="video-card"
           style="opacity:0;animation:fadeUp .5s ease <?php echo ($i % 8) * 50; ?>ms both"
           onclick="location.href='<?php echo $SITE_URL; ?>watch.php?id=<?php echo $show['id']; ?>'">

        <?php if (($show['access_level'] ?? 'free') === 'premium'): ?>
          <div class="card-badge premium-badge"><i class="fas fa-crown"></i> Premium</div>
          <div class="card-badge season-badge" style="top:35px; background: rgba(229,9,20,.85);">
            <?php echo $show['seasons']; ?> Season<?php echo $show['seasons'] > 1 ? 's' : ''; ?>
          </div>
        <?php else: ?>
          <div class="card-badge">
            <?php echo $show['seasons']; ?> Season<?php echo $show['seasons'] > 1 ? 's' : ''; ?>
          </div>
        <?php endif; ?>
        <div class="tv-badge">TV</div>

        <div class="card-img">
          <img src="<?php echo THUMBNAIL_URL . htmlspecialchars($show['thumbnail_path']); ?>"
               alt="<?php echo htmlspecialchars($show['title']); ?>"
               loading="lazy"
               onerror="this.src='assets/placeholder.jpg'">
          <div class="play-overlay"><i class="fas fa-play"></i></div>
        </div>

        <div class="card-info">
          <h3><?php echo htmlspecialchars($show['title']); ?></h3>
          <div class="meta">
            <?php if ($show['rating']): ?>
              <span class="rating"><i class="fas fa-star"></i> <?php echo $show['rating']; ?></span>
            <?php endif; ?>
            <?php if ($show['release_year']): ?>
              <span class="year"><?php echo $show['release_year']; ?></span>
            <?php endif; ?>
          </div>
          <?php if ($show['category_name']): ?>
            <span class="card-category"><?php echo htmlspecialchars($show['category_name']); ?></span>
          <?php endif; ?>
          <?php if (!empty($show['description'])): ?>
            <p class="card-desc"><?php echo htmlspecialchars($show['description']); ?></p>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <nav class="pagination">
    <?php if ($page > 1): ?>
      <a href="?page=<?php echo $page-1; ?><?php echo $categoryId ? '&category='.$categoryId : ''; ?>"
         class="page-btn"><i class="fas fa-chevron-left"></i></a>
    <?php endif; ?>

    <?php
      $range = 2; $shown = []; $prev = null;
      for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == 1 || $i == $totalPages || abs($i - $page) <= $range) $shown[] = $i;
      }
      foreach ($shown as $p):
        if ($prev !== null && $p - $prev > 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
        <a href="?page=<?php echo $p; ?><?php echo $categoryId ? '&category='.$categoryId : ''; ?>"
           class="page-btn <?php echo $p == $page ? 'active' : ''; ?>"><?php echo $p; ?></a>
    <?php $prev = $p; endforeach; ?>

    <?php if ($page < $totalPages): ?>
      <a href="?page=<?php echo $page+1; ?><?php echo $categoryId ? '&category='.$categoryId : ''; ?>"
         class="page-btn"><i class="fas fa-chevron-right"></i></a>
    <?php endif; ?>
  </nav>
  <?php endif; ?>

  <?php endif; ?>
</main>

<!-- ── TRENDING SERIES ── -->
<?php if (!empty($trendingSeries)): ?>
<section class="section" style="padding-top:48px">
  <div class="section-header">
    <h2 class="section-title">Trending Series</h2>
    <a href="series.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
  </div>
  <div class="slider-wrap">
    <button class="slider-btn prev" data-target="trending"><i class="fas fa-chevron-left"></i></button>
    <div class="slider-track" id="trending">
      <?php foreach ($trendingSeries as $i => $show): ?>
        <div class="trending-card"
             onclick="location.href='<?php echo $SITE_URL; ?>watch.php?id=<?php echo $show['id']; ?>'">
          <?php if (($show['access_level'] ?? 'free') === 'premium'): ?>
            <div class="card-badge premium-badge" style="position:absolute; top:10px; left:10px; background: linear-gradient(135deg, #f5c518, #e09000); color: #000; font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 4px; letter-spacing: .5px; text-transform: uppercase; z-index: 5;"><i class="fas fa-crown" style="margin-right:3px;font-size:10px;"></i> Premium</div>
          <?php endif; ?>
          <div class="card-img">
            <img src="<?php echo THUMBNAIL_URL . htmlspecialchars($show['thumbnail_path']); ?>"
                 alt="<?php echo htmlspecialchars($show['title']); ?>"
                 loading="lazy"
                 onerror="this.src='assets/placeholder.jpg'">
            <div class="play-overlay"><i class="fas fa-play"></i></div>
            <div class="rank-badge">#<?php echo $i + 1; ?></div>
          </div>
          <div class="card-info">
            <h4><?php echo htmlspecialchars($show['title']); ?></h4>
            <div class="trending-stats">
              <span><i class="fas fa-eye"></i> <?php echo number_format($show['views']); ?></span>
              <span><i class="fas fa-star"></i> <?php echo $show['rating']; ?></span>
              <?php if ($show['seasons'] > 0): ?>
                <span><i class="fas fa-layer-group"></i> <?php echo $show['seasons']; ?> Season<?php echo $show['seasons'] > 1 ? 's' : ''; ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="slider-btn next" data-target="trending"><i class="fas fa-chevron-right"></i></button>
  </div>
</section>
<?php endif; ?>

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
          <li><a href="#">Sign In</a></li>
          <li><a href="#">Register</a></li>
          <li><a href="#">My List</a></li>
          <li><a href="#">Settings</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Legal</h4>
        <ul>
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Terms of Use</a></li>
          <li><a href="#">Cookie Prefs</a></li>
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
  /* ── SORT ── */
  document.getElementById('sortSelect').addEventListener('change', function() {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', this.value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
  });
  const urlSort = new URLSearchParams(window.location.search).get('sort');
  if (urlSort) document.getElementById('sortSelect').value = urlSort;

  /* ── SLIDERS ── */
  document.querySelectorAll('.slider-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const track = document.getElementById(btn.dataset.target);
      if (!track) return;
      const dir = btn.classList.contains('prev') ? -1 : 1;
      const cardW = track.querySelector('.trending-card')?.offsetWidth || 280;
      track.scrollBy({ left: dir * (cardW + 16) * 2, behavior: 'smooth' });
    });
  });

  /* ── CARD REVEAL ── */
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) io.unobserve(e.target); });
  }, { rootMargin: '-30px' });
  document.querySelectorAll('.video-card, .trending-card').forEach(c => io.observe(c));
</script>

</body>
</html>

<?php require_once 'includes/footer.php'; ?>