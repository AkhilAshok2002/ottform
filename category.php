<?php
$pageTitle = 'Category';
require_once 'includes/header.php';
require_once 'includes/functions.php';

$categoryId = $_GET['id'] ?? 0;
$type       = $_GET['type'] ?? 'all';
$sort       = $_GET['sort'] ?? 'newest';

if (!$categoryId) { header('Location: ' . SITE_URL); exit(); }

$stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$categoryId]);
$category = $stmt->fetch();
if (!$category) { header('Location: ' . SITE_URL); exit(); }

$pageTitle = htmlspecialchars($category['name']) . ' — StreamVault';

$page   = $_GET['page'] ?? 1;
$limit  = 24;
$offset = ($page - 1) * $limit;

$params     = [$categoryId];
$query      = "SELECT v.*, c.name as category_name FROM videos v LEFT JOIN categories c ON v.category_id = c.id WHERE v.category_id = ?";
$countQuery = "SELECT COUNT(*) as count FROM videos WHERE category_id = ?";

if ($type !== 'all') {
    $query      .= " AND v.type = ?";
    $countQuery .= " AND type = ?";
    $params[]    = $type;
}

switch ($sort) {
    case 'popular': $query .= " ORDER BY v.views DESC, v.rating DESC"; break;
    case 'rating':  $query .= " ORDER BY v.rating DESC, v.views DESC"; break;
    case 'title':   $query .= " ORDER BY v.title ASC"; break;
    default:        $query .= " ORDER BY v.created_at DESC"; break;
}
$query .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$cStmt = $db->prepare($countQuery);
$type !== 'all' ? $cStmt->execute([$categoryId, $type]) : $cStmt->execute([$categoryId]);
$totalVideos = $cStmt->fetch()['count'];

$stmt = $db->prepare($query);
$stmt->execute($params);
$videos = $stmt->fetchAll();

$totalPages = ceil($totalVideos / $limit);

$stmt = $db->prepare("SELECT COUNT(CASE WHEN type='movie' THEN 1 END) as movie_count, COUNT(CASE WHEN type='series' THEN 1 END) as series_count FROM videos WHERE category_id = ?");
$stmt->execute([$categoryId]);
$counts = $stmt->fetch();

$popStmt = $db->prepare("SELECT v.*, c.name as category_name FROM videos v LEFT JOIN categories c ON v.category_id = c.id WHERE v.category_id = ? ORDER BY v.views DESC, v.rating DESC LIMIT 5");
$popStmt->execute([$categoryId]);
$popularVideos = $popStmt->fetchAll();

$latStmt = $db->prepare("SELECT v.*, c.name as category_name FROM videos v LEFT JOIN categories c ON v.category_id = c.id WHERE v.category_id = ? ORDER BY v.created_at DESC LIMIT 5");
$latStmt->execute([$categoryId]);
$latestVideos = $latStmt->fetchAll();

$relStmt = $db->prepare("SELECT * FROM categories WHERE type = ? AND id != ? ORDER BY RAND() LIMIT 5");
$relStmt->execute([$category['type'], $categoryId]);
$relatedCategories = $relStmt->fetchAll();

$lastUpdStmt = $db->prepare("SELECT MAX(created_at) as last FROM videos WHERE category_id = ?");
$lastUpdStmt->execute([$categoryId]);
$lastUpdated = $lastUpdStmt->fetch()['last'];

$SITE_URL = rtrim(SITE_URL, '/') . '/';

$categoryIcons = [
    'action'=>'⚡','comedy'=>'😂','drama'=>'🎭','sci-fi'=>'🚀',
    'horror'=>'👻','romance'=>'❤️','documentary'=>'🎬','thriller'=>'🔪','animation'=>'🎨',
];
$iconKey   = strtolower(trim($category['name']));
$catIcon   = $categoryIcons[$iconKey] ?? ($category['type'] === 'series' ? '📺' : '🎬');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $pageTitle; ?></title>
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

    /* ── HERO BANNER ── */
    .cat-hero {
      position: relative;
      padding: 110px 4vw 52px;
      overflow: hidden;
    }
    .cat-hero::before {
      content: '';
      position: absolute; inset: 0;
      background:
        radial-gradient(ellipse 55% 90% at 70% 50%, rgba(229,9,20,.08) 0%, transparent 65%),
        linear-gradient(180deg, rgba(229,9,20,.04) 0%, transparent 100%);
      pointer-events: none;
    }
    .cat-hero::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 1px; background: var(--border) }

    .hero-inner { position: relative; display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 32px }

    .hero-left {}
    .hero-eyebrow { display: inline-flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--red); margin-bottom: 12px }
    .hero-icon-title { display: flex; align-items: center; gap: 20px; margin-bottom: 14px }
    .hero-emoji { font-size: 52px; line-height: 1 }
    .hero-title { font-family: var(--font-display); font-size: clamp(44px, 7vw, 86px); letter-spacing: 3px; line-height: .9 }
    .hero-title span { color: var(--red) }

    /* Stats row */
    .hero-stats { display: flex; align-items: center; gap: 0 }
    .stat-item {
      display: flex; flex-direction: column; align-items: center;
      padding: 0 24px; border-right: 1px solid var(--border);
    }
    .stat-item:first-child { padding-left: 0 }
    .stat-item:last-child { border-right: none }
    .stat-value { font-family: var(--font-display); font-size: 36px; letter-spacing: 2px; color: var(--text); line-height: 1 }
    .stat-label { font-size: 11px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: var(--muted); margin-top: 4px }

    /* ── STICKY TOOLBAR ── */
    .cat-toolbar {
      background: var(--surface); border-bottom: 1px solid var(--border);
      padding: 18px 4vw;
      display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;
      position: sticky; top: 0; z-index: 100; backdrop-filter: blur(20px);
    }
    .filter-tabs { display: flex; align-items: center; gap: 6px }
    .filter-tab {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 8px 18px; border-radius: 30px;
      background: var(--surface2); border: 1px solid var(--border);
      font-size: 13px; font-weight: 500; color: var(--muted);
      cursor: pointer; transition: all var(--trans); white-space: nowrap;
    }
    .filter-tab:hover { border-color: rgba(229,9,20,.35); color: var(--text) }
    .filter-tab.active { background: var(--red); border-color: var(--red); color: #fff }
    .tab-count { font-size: 11px; font-weight: 700; background: rgba(255,255,255,.15); padding: 1px 6px; border-radius: 10px }
    .filter-tab.active .tab-count { background: rgba(0,0,0,.2) }
    .filters-label { font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--muted) }
    .sort-wrap { display: flex; align-items: center; gap: 10px; margin-left: auto }
    .sort-select {
      appearance: none; background: var(--surface2); border: 1px solid var(--border);
      color: var(--text); font-family: var(--font-body); font-size: 13px;
      padding: 8px 36px 8px 14px; border-radius: 8px; cursor: pointer; transition: border-color var(--trans);
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 12px center;
    }
    .sort-select:focus { outline: none; border-color: var(--red) }
    .sort-select option { background: #1a1a1a }

    /* ── PAGE LAYOUT ── */
    .cat-layout { display: grid; grid-template-columns: 1fr 300px; gap: 32px; padding: 40px 4vw 80px; align-items: start }

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
      position: absolute; inset: 0; background: rgba(0,0,0,.4);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; transition: opacity var(--trans);
    }
    .video-card:hover .play-overlay { opacity: 1 }
    .play-overlay i { font-size: 44px; color: #fff; filter: drop-shadow(0 0 20px rgba(229,9,20,.6)); transition: transform var(--trans) }
    .video-card:hover .play-overlay i { transform: scale(1.1) }
    .type-badge {
      position: absolute; top: 10px; left: 10px;
      font-size: 10px; font-weight: 700; padding: 3px 8px;
      border-radius: 4px; letter-spacing: .5px; text-transform: uppercase;
    }
    .badge-movie { background: var(--red); color: #fff }
    .badge-series { background: rgba(245,197,24,.15); border: 1px solid rgba(245,197,24,.35); color: var(--gold) }
    .premium-badge {
      position: absolute; top: 35px; left: 10px;
      background: linear-gradient(135deg, #f5c518, #e09000);
      color: #000; font-size: 10px; font-weight: 700; padding: 3px 8px;
      border-radius: 4px; letter-spacing: .5px; text-transform: uppercase; z-index: 5;
    }
    .premium-badge i { margin-right: 3px; font-size: 10px; }
    .dur-badge {
      position: absolute; bottom: 10px; right: 10px;
      background: rgba(0,0,0,.75); backdrop-filter: blur(6px);
      color: var(--text); font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 4px;
    }
    .fav-btn {
      position: absolute; top: 10px; right: 10px;
      width: 30px; height: 30px; border-radius: 50%;
      background: rgba(0,0,0,.65); backdrop-filter: blur(6px);
      border: 1px solid rgba(255,255,255,.15);
      color: var(--muted); font-size: 13px;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: all var(--trans); opacity: 0;
    }
    .video-card:hover .fav-btn { opacity: 1 }
    .fav-btn.active { background: rgba(229,9,20,.2); border-color: rgba(229,9,20,.4); color: var(--red); opacity: 1 }
    .fav-btn:hover { background: var(--red); border-color: var(--red); color: #fff }
    .card-info { padding: 12px 14px 14px }
    .card-info h3 { font-size: 14px; font-weight: 600; margin-bottom: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
    .meta { display: flex; align-items: center; gap: 10px; margin-bottom: 4px }
    .rating { display: flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600; color: var(--gold) }
    .views { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--muted) }
    .year { font-size: 12px; color: var(--muted) }

    /* ── VIDEOS GRID ── */
    .videos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(168px, 1fr)); gap: 18px }

    /* ── EMPTY STATE ── */
    .empty-state {
      display: flex; flex-direction: column; align-items: center;
      gap: 16px; padding: 80px 20px; text-align: center;
    }
    .empty-icon { width: 80px; height: 80px; border-radius: 50%; background: var(--surface); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 28px; color: var(--muted) }
    .empty-state h2 { font-family: var(--font-display); font-size: 32px; letter-spacing: 2px }
    .empty-state p { font-size: 15px; color: var(--muted) }
    .btn-browse { display: inline-flex; align-items: center; gap: 8px; background: var(--red); color: #fff; font-weight: 700; font-size: 14px; padding: 12px 28px; border-radius: 8px; margin-top: 8px; transition: background var(--trans), transform var(--trans) }
    .btn-browse:hover { background: var(--red-dim); transform: translateY(-2px) }

    /* ── PAGINATION ── */
    .pagination { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 48px }
    .page-btn {
      display: inline-flex; align-items: center; justify-content: center;
      min-width: 40px; height: 40px; padding: 0 8px; border-radius: 8px;
      background: var(--surface); border: 1px solid var(--border);
      font-size: 14px; font-weight: 600; color: var(--muted);
      transition: all var(--trans); cursor: pointer;
    }
    .page-btn:hover { border-color: rgba(229,9,20,.4); color: var(--text) }
    .page-btn.active { background: var(--red); border-color: var(--red); color: #fff }
    .page-ellipsis { color: var(--muted); font-size: 14px; padding: 0 4px }

    /* ── SIDEBAR ── */
    .cat-sidebar { display: flex; flex-direction: column; gap: 24px; position: sticky; top: 80px }

    .sidebar-widget {
      background: var(--surface); border: 1px solid var(--border);
      border-radius: var(--card-r); overflow: hidden;
    }
    .widget-header {
      padding: 16px 18px;
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; gap: 10px;
    }
    .widget-header-icon {
      width: 30px; height: 30px; border-radius: 8px;
      background: rgba(229,9,20,.1); border: 1px solid rgba(229,9,20,.2);
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; color: var(--red); flex-shrink: 0;
    }
    .widget-title { font-family: var(--font-display); font-size: 17px; letter-spacing: 1.5px }

    /* Popular list */
    .popular-list { padding: 8px 0 }
    .popular-item {
      display: flex; align-items: center; gap: 12px;
      padding: 10px 18px; cursor: pointer;
      transition: background var(--trans);
    }
    .popular-item:hover { background: var(--surface2) }
    .pop-rank {
      font-family: var(--font-display); font-size: 20px; letter-spacing: 1px;
      color: var(--muted); min-width: 24px; text-align: center; line-height: 1;
    }
    .popular-item:nth-child(1) .pop-rank { color: var(--gold) }
    .popular-item:nth-child(2) .pop-rank { color: #c0c0c0 }
    .popular-item:nth-child(3) .pop-rank { color: #cd7f32 }
    .pop-thumb { flex-shrink: 0; width: 52px; aspect-ratio: 2/3; border-radius: 6px; overflow: hidden; background: var(--surface2) }
    .pop-thumb img { height: 100% }
    .pop-info { flex: 1; min-width: 0 }
    .pop-title { font-size: 13px; font-weight: 600; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
    .pop-meta { display: flex; gap: 10px }
    .pop-meta span { display: flex; align-items: center; gap: 4px; font-size: 11px; color: var(--muted) }
    .pop-meta .fa-star { color: var(--gold) }

    /* Latest list */
    .latest-list { padding: 8px 0 }
    .latest-item {
      display: flex; align-items: center; gap: 12px;
      padding: 10px 18px; cursor: pointer; transition: background var(--trans);
    }
    .latest-item:hover { background: var(--surface2) }
    .lat-thumb { flex-shrink: 0; width: 52px; aspect-ratio: 2/3; border-radius: 6px; overflow: hidden; background: var(--surface2) }
    .lat-thumb img { height: 100% }
    .lat-info { flex: 1; min-width: 0 }
    .lat-title { font-size: 13px; font-weight: 600; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
    .lat-date { display: flex; align-items: center; gap: 5px; font-size: 11px; color: var(--muted) }

    /* Related categories */
    .related-grid { display: flex; flex-wrap: wrap; gap: 8px; padding: 16px 18px }
    .related-pill {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 14px; border-radius: 20px;
      background: var(--surface2); border: 1px solid var(--border);
      font-size: 12px; font-weight: 500; color: var(--muted);
      cursor: pointer; transition: all var(--trans);
    }
    .related-pill:hover { border-color: rgba(229,9,20,.35); color: var(--text) }

    /* Category info table */
    .info-table { padding: 4px 0 }
    .info-row {
      display: flex; align-items: center; justify-content: space-between;
      padding: 11px 18px; border-bottom: 1px solid var(--border);
    }
    .info-row:last-child { border-bottom: none }
    .info-label { font-size: 12px; color: var(--muted) }
    .info-value { font-size: 13px; font-weight: 600; color: var(--text) }

    /* ── LOADING SPINNER (infinite scroll) ── */
    .load-more-spinner {
      display: none; align-items: center; justify-content: center;
      gap: 10px; padding: 32px; color: var(--muted); font-size: 14px;
    }
    .load-more-spinner.visible { display: flex }
    .spinner { width: 20px; height: 20px; border: 2px solid var(--border); border-top-color: var(--red); border-radius: 50%; animation: spin .7s linear infinite }
    @keyframes spin { to { transform: rotate(360deg) } }

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
    .footer { padding: 48px 4vw 32px; border-top: 1px solid var(--border); margin-top: 20px }
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
    @media(max-width:1100px) { .cat-layout { grid-template-columns: 1fr } .cat-sidebar { position: static } }
    @media(max-width:900px) { .videos-grid { grid-template-columns: repeat(auto-fill, minmax(148px, 1fr)) } }
    @media(max-width:600px) {
      .videos-grid { grid-template-columns: repeat(2, 1fr); gap: 12px }
      .cat-toolbar { flex-direction: column; align-items: flex-start; gap: 12px }
      .sort-wrap { margin-left: 0; width: 100% }
      .sort-select { width: 100% }
      .hero-stats { gap: 0 }
      .stat-item { padding: 0 16px }
    }
  </style>
</head>
<body>

<!-- ── HERO BANNER ── -->
<div class="cat-hero animate-in">
  <div class="hero-inner">
    <div class="hero-left">
      <div class="hero-eyebrow"><i class="fas fa-tag"></i> Genre</div>
      <div class="hero-icon-title">
        <span class="hero-emoji"><?php echo $catIcon; ?></span>
        <h1 class="hero-title"><?php echo strtoupper(htmlspecialchars($category['name'])); ?></h1>
      </div>
      <div class="hero-stats">
        <div class="stat-item">
          <span class="stat-value"><?php echo number_format($totalVideos); ?></span>
          <span class="stat-label">Total</span>
        </div>
        <div class="stat-item">
          <span class="stat-value"><?php echo number_format($counts['movie_count']); ?></span>
          <span class="stat-label">Movies</span>
        </div>
        <div class="stat-item">
          <span class="stat-value"><?php echo number_format($counts['series_count']); ?></span>
          <span class="stat-label">Series</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── TOOLBAR ── -->
<div class="cat-toolbar">
  <div class="filter-tabs">
    <a href="?id=<?php echo $categoryId; ?>&type=all&sort=<?php echo $sort; ?>"
       class="filter-tab <?php echo $type==='all' ? 'active' : ''; ?>">
      All <span class="tab-count"><?php echo number_format($totalVideos); ?></span>
    </a>
    <a href="?id=<?php echo $categoryId; ?>&type=movie&sort=<?php echo $sort; ?>"
       class="filter-tab <?php echo $type==='movie' ? 'active' : ''; ?>">
      <i class="fas fa-film"></i> Movies <span class="tab-count"><?php echo number_format($counts['movie_count']); ?></span>
    </a>
    <a href="?id=<?php echo $categoryId; ?>&type=series&sort=<?php echo $sort; ?>"
       class="filter-tab <?php echo $type==='series' ? 'active' : ''; ?>">
      <i class="fas fa-tv"></i> Series <span class="tab-count"><?php echo number_format($counts['series_count']); ?></span>
    </a>
  </div>
  <div class="sort-wrap">
    <span class="filters-label">Sort</span>
    <select class="sort-select" id="sortSelect">
      <option value="newest"  <?php echo $sort==='newest'  ? 'selected' : ''; ?>>Newest First</option>
      <option value="popular" <?php echo $sort==='popular' ? 'selected' : ''; ?>>Most Popular</option>
      <option value="rating"  <?php echo $sort==='rating'  ? 'selected' : ''; ?>>Highest Rated</option>
      <option value="title"   <?php echo $sort==='title'   ? 'selected' : ''; ?>>Title A–Z</option>
    </select>
  </div>
</div>

<!-- ── MAIN LAYOUT ── -->
<div class="cat-layout">

  <!-- Videos Column -->
  <div class="cat-main">

    <?php if (empty($videos)): ?>
    <div class="empty-state animate-in">
      <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
      <h2>No Videos Found</h2>
      <p>There are no videos in this category yet.</p>
      <a href="<?php echo $SITE_URL; ?>" class="btn-browse"><i class="fas fa-home"></i> Go Home</a>
    </div>

    <?php else: ?>
    <div class="videos-grid" id="videosGrid">
      <?php foreach ($videos as $i => $v): ?>
        <?php $isMovie = ($v['type'] !== 'series'); ?>
        <div class="video-card"
             style="opacity:0;animation:fadeUp .45s ease <?php echo ($i % 8) * 45; ?>ms both"
             onclick="location.href='<?php echo $SITE_URL . ($isMovie ? 'watch.php' : 'watch.php'); ?>?id=<?php echo $v['id']; ?>'">

          <span class="type-badge <?php echo $isMovie ? 'badge-movie' : 'badge-series'; ?>">
            <?php echo $isMovie ? 'Movie' : 'Series'; ?>
          </span>

          <?php if (($v['access_level'] ?? 'free') === 'premium'): ?>
            <div class="premium-badge"><i class="fas fa-crown"></i> Premium</div>
          <?php endif; ?>

          <?php if (!empty($v['duration'])): ?>
            <div class="dur-badge"><?php echo htmlspecialchars($v['duration']); ?></div>
          <?php endif; ?>

          <button class="fav-btn <?php echo (function_exists('isFavorite') && isFavorite($v['id'])) ? 'active' : ''; ?>"
                  onclick="event.stopPropagation(); toggleFav(this, <?php echo $v['id']; ?>)"
                  title="Add to list">
            <i class="fas fa-heart"></i>
          </button>

          <div class="card-img">
            <img src="<?php echo $SITE_URL; ?>assets/uploads/thumbnails/<?php echo htmlspecialchars($v['thumbnail_path']); ?>"
                 alt="<?php echo htmlspecialchars($v['title']); ?>"
                 loading="lazy"
                 onerror="this.src='assets/placeholder.jpg'">
            <div class="play-overlay"><i class="fas fa-play"></i></div>
          </div>

          <div class="card-info">
            <h3><?php echo htmlspecialchars($v['title']); ?></h3>
            <div class="meta">
              <?php if ($v['rating']): ?>
                <span class="rating"><i class="fas fa-star"></i> <?php echo number_format($v['rating'],1); ?></span>
              <?php endif; ?>
              <span class="views"><i class="fas fa-eye"></i> <?php echo number_format($v['views']); ?></span>
            </div>
            <?php if ($v['release_year']): ?>
              <div class="meta"><span class="year"><?php echo $v['release_year']; ?></span></div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Loading spinner for infinite scroll -->
    <div class="load-more-spinner" id="loadSpinner">
      <div class="spinner"></div> Loading more…
    </div>

    <!-- Pagination fallback -->
    <?php if ($totalPages > 1): ?>
    <nav class="pagination" id="paginationBar">
      <?php if ($page > 1): ?>
        <a href="?id=<?php echo $categoryId; ?>&type=<?php echo $type; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page-1; ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
      <?php endif; ?>
      <?php
        $shown = []; $prev = null;
        for ($i = 1; $i <= $totalPages; $i++) {
          if ($i == 1 || $i == $totalPages || abs($i - $page) <= 2) $shown[] = $i;
        }
        foreach ($shown as $p):
          if ($prev !== null && $p - $prev > 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
          <a href="?id=<?php echo $categoryId; ?>&type=<?php echo $type; ?>&sort=<?php echo $sort; ?>&page=<?php echo $p; ?>"
             class="page-btn <?php echo $p==$page ? 'active' : ''; ?>"><?php echo $p; ?></a>
      <?php $prev = $p; endforeach; ?>
      <?php if ($page < $totalPages): ?>
        <a href="?id=<?php echo $categoryId; ?>&type=<?php echo $type; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page+1; ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
      <?php endif; ?>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- ── SIDEBAR ── -->
  <aside class="cat-sidebar">

    <!-- Popular -->
    <?php if (!empty($popularVideos)): ?>
    <div class="sidebar-widget animate-in">
      <div class="widget-header">
        <div class="widget-header-icon"><i class="fas fa-fire"></i></div>
        <h3 class="widget-title">Popular</h3>
      </div>
      <div class="popular-list">
        <?php foreach ($popularVideos as $i => $v): ?>
          <?php $isMovie = ($v['type'] !== 'series'); ?>
          <a href="<?php echo $SITE_URL . ($isMovie ? 'watch.php' : 'watch.php'); ?>?id=<?php echo $v['id']; ?>"
             class="popular-item">
            <span class="pop-rank"><?php echo $i+1; ?></span>
            <div class="pop-thumb">
              <img src="<?php echo $SITE_URL; ?>assets/uploads/thumbnails/<?php echo htmlspecialchars($v['thumbnail_path']); ?>"
                   alt="<?php echo htmlspecialchars($v['title']); ?>" loading="lazy">
            </div>
            <div class="pop-info">
              <p class="pop-title"><?php echo htmlspecialchars($v['title']); ?></p>
              <div class="pop-meta">
                <span><i class="fas fa-star"></i> <?php echo $v['rating']; ?></span>
                <span><i class="fas fa-eye"></i> <?php echo number_format($v['views']); ?></span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Latest -->
    <?php if (!empty($latestVideos)): ?>
    <div class="sidebar-widget animate-in" style="animation-delay:.1s">
      <div class="widget-header">
        <div class="widget-header-icon"><i class="fas fa-clock"></i></div>
        <h3 class="widget-title">Latest Added</h3>
      </div>
      <div class="latest-list">
        <?php foreach ($latestVideos as $v): ?>
          <?php $isMovie = ($v['type'] !== 'series'); ?>
          <a href="<?php echo $SITE_URL . ($isMovie ? 'watch.php' : 'watch.php'); ?>?id=<?php echo $v['id']; ?>"
             class="latest-item">
            <div class="lat-thumb">
              <img src="<?php echo $SITE_URL; ?>assets/uploads/thumbnails/<?php echo htmlspecialchars($v['thumbnail_path']); ?>"
                   alt="<?php echo htmlspecialchars($v['title']); ?>" loading="lazy">
            </div>
            <div class="lat-info">
              <p class="lat-title"><?php echo htmlspecialchars($v['title']); ?></p>
              <span class="lat-date">
                <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($v['created_at'])); ?>
              </span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Related categories -->
    <?php if (!empty($relatedCategories)): ?>
    <div class="sidebar-widget animate-in" style="animation-delay:.15s">
      <div class="widget-header">
        <div class="widget-header-icon"><i class="fas fa-tags"></i></div>
        <h3 class="widget-title">Related</h3>
      </div>
      <div class="related-grid">
        <?php foreach ($relatedCategories as $rel): ?>
          <a href="<?php echo $SITE_URL; ?>category.php?id=<?php echo $rel['id']; ?>" class="related-pill">
            <?php echo htmlspecialchars($rel['name']); ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Category Info -->
    <div class="sidebar-widget animate-in" style="animation-delay:.2s">
      <div class="widget-header">
        <div class="widget-header-icon"><i class="fas fa-circle-info"></i></div>
        <h3 class="widget-title">Info</h3>
      </div>
      <div class="info-table">
        <div class="info-row">
          <span class="info-label">Total Videos</span>
          <span class="info-value"><?php echo number_format($totalVideos); ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Movies</span>
          <span class="info-value"><?php echo number_format($counts['movie_count']); ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Series</span>
          <span class="info-value"><?php echo number_format($counts['series_count']); ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Type</span>
          <span class="info-value" style="text-transform:capitalize"><?php echo htmlspecialchars($category['type']); ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Last Updated</span>
          <span class="info-value"><?php echo $lastUpdated ? date('M d, Y', strtotime($lastUpdated)) : 'N/A'; ?></span>
        </div>
      </div>
    </div>

  </aside>
</div>

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
  const SITE_URL   = '<?php echo $SITE_URL; ?>';
  const IS_LOGGED  = <?php echo $auth->isLoggedIn() ? 'true' : 'false'; ?>;
  let curPage      = <?php echo (int)$page; ?>;
  const totalPages = <?php echo (int)$totalPages; ?>;
  let loading      = false;

  /* ── SORT ── */
  document.getElementById('sortSelect').addEventListener('change', function () {
    const p = new URLSearchParams(window.location.search);
    p.set('sort', this.value); p.set('page', '1');
    window.location.search = p.toString();
  });

  /* ── FAVOURITE ── */
  function toggleFav(btn, videoId) {
    if (!IS_LOGGED) { window.location.href = SITE_URL + 'login.php'; return; }
    fetch(SITE_URL + 'ajax/toggle-favorite.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'video_id=' + videoId
    })
    .then(r => r.json())
    .then(data => {
      const added = data.action === 'added';
      btn.classList.toggle('active', added);
      showToast(added ? 'Added to your list' : 'Removed from list', added ? 'fa-heart' : 'fa-heart-crack');
    })
    .catch(() => showToast('Something went wrong', 'fa-triangle-exclamation'));
  }

  /* ── INFINITE SCROLL ── */
  window.addEventListener('scroll', () => {
    if (loading || curPage >= totalPages) return;
    const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
    if (scrollTop + clientHeight >= scrollHeight - 300) {
      loading = true;
      curPage++;
      document.getElementById('loadSpinner').classList.add('visible');
      document.getElementById('paginationBar')?.style && (document.getElementById('paginationBar').style.display = 'none');

      const p = new URLSearchParams(window.location.search);
      p.set('page', curPage);

      fetch(SITE_URL + 'ajax/load-category-videos.php?' + p.toString())
        .then(r => r.text())
        .then(html => {
          document.getElementById('loadSpinner').classList.remove('visible');
          if (html.trim()) {
            document.getElementById('videosGrid').insertAdjacentHTML('beforeend', html);
          }
          loading = false;
        })
        .catch(() => { document.getElementById('loadSpinner').classList.remove('visible'); loading = false; });
    }
  }, { passive: true });

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