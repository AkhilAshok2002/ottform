<?php
$pageTitle = 'Search';
require_once 'includes/header.php';
require_once 'includes/functions.php';

$query    = $_GET['q']        ?? '';
$type     = $_GET['type']     ?? 'all';
$category = $_GET['category'] ?? 0;
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 24;
$offset   = ($page - 1) * $limit;

$results      = [];
$totalResults = 0;
$categories   = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

if (!empty($query)) {
    $searchTerm = '%' . $query . '%';
    $params     = [$searchTerm, $searchTerm];

    $sql      = "SELECT v.*, c.name as category_name FROM videos v LEFT JOIN categories c ON v.category_id = c.id WHERE (v.title LIKE ? OR v.description LIKE ?)";
    $countSql = "SELECT COUNT(*) as count FROM videos v LEFT JOIN categories c ON v.category_id = c.id WHERE (v.title LIKE ? OR v.description LIKE ?)";

    if ($type !== 'all') { $sql .= " AND v.type = ?"; $countSql .= " AND v.type = ?"; $params[] = $type; }
    if ($category > 0)   { $sql .= " AND v.category_id = ?"; $countSql .= " AND v.category_id = ?"; $params[] = $category; }

    $countParams = $params;
    $sql    .= " ORDER BY v.views DESC, v.rating DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $cStmt = $db->prepare($countSql);
    $cStmt->execute($countParams);
    $totalResults = $cStmt->fetch()['count'];

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
}

$totalPages = $totalResults > 0 ? ceil($totalResults / $limit) : 1;

// Popular videos shown when no query
$popular = $db->query("SELECT v.*, c.name as category_name FROM videos v LEFT JOIN categories c ON v.category_id = c.id ORDER BY v.views DESC LIMIT 12")->fetchAll();

$SITE_URL = rtrim(SITE_URL, '/') . '/';

function buildUrl($extra = []) {
    global $query, $type, $category;
    $base = ['q' => $query, 'type' => $type, 'category' => $category];
    $merged = array_merge($base, $extra);
    return '?' . http_build_query(array_filter($merged, fn($v) => $v !== '' && $v !== '0' && $v !== 0));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $query ? 'Search: ' . htmlspecialchars($query) . ' — StreamVault' : 'Search — StreamVault'; ?></title>
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

    /* ── SEARCH HERO ── */
    .search-hero {
      position: relative;
      padding: 100px 4vw 48px;
      overflow: hidden;
    }
    .search-hero::before {
      content: '';
      position: absolute; inset: 0;
      background: radial-gradient(ellipse 70% 80% at 50% 30%, rgba(229,9,20,.07) 0%, transparent 70%);
      pointer-events: none;
    }
    .search-hero::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 1px; background: var(--border) }

    .hero-eyebrow { display: inline-flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--red); margin-bottom: 12px }
    .hero-title { font-family: var(--font-display); font-size: clamp(44px, 7vw, 80px); letter-spacing: 3px; line-height: .9; margin-bottom: 32px }
    .hero-title span { color: var(--red) }

    /* ── SEARCH BOX ── */
    .search-form { max-width: 700px }
    .search-input-wrap {
      position: relative;
      margin-bottom: 16px;
    }
    .search-icon {
      position: absolute; left: 20px; top: 50%; transform: translateY(-50%);
      font-size: 18px; color: var(--muted); pointer-events: none;
      transition: color var(--trans);
    }
    .search-input-wrap:focus-within .search-icon { color: var(--red) }
    .search-input {
      width: 100%;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 18px 120px 18px 56px;
      font-family: var(--font-body); font-size: 17px;
      color: var(--text); outline: none;
      transition: border-color var(--trans), box-shadow var(--trans);
    }
    .search-input::placeholder { color: #3a3a3a }
    .search-input:focus {
      border-color: rgba(229,9,20,.45);
      box-shadow: 0 0 0 3px rgba(229,9,20,.1);
    }
    .search-submit {
      position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
      background: var(--red); color: #fff;
      border: none; border-radius: 10px;
      padding: 10px 22px;
      font-family: var(--font-display); font-size: 17px; letter-spacing: 1px;
      cursor: pointer; display: flex; align-items: center; gap: 8px;
      transition: background var(--trans), transform var(--trans);
    }
    .search-submit:hover { background: var(--red-dim); transform: translateY(-50%) scale(1.03) }

    /* Suggestions dropdown */
    .suggestions-drop {
      position: absolute; top: calc(100% + 8px); left: 0; right: 0;
      background: var(--surface); border: 1px solid var(--border);
      border-radius: 12px; z-index: 200;
      overflow: hidden;
      box-shadow: 0 16px 48px rgba(0,0,0,.6);
      display: none;
    }
    .suggestions-drop.open { display: block }
    .suggestion-item {
      display: flex; align-items: center; gap: 12px;
      padding: 11px 16px; cursor: pointer;
      transition: background var(--trans);
      font-size: 14px;
    }
    .suggestion-item:hover { background: var(--surface2) }
    .suggestion-item i { color: var(--muted); font-size: 13px; width: 16px; text-align: center }
    .suggestion-thumb {
      width: 36px; height: 36px; border-radius: 6px; overflow: hidden;
      flex-shrink: 0; background: var(--surface2);
    }
    .suggestion-thumb img { height: 100%; object-fit: cover }
    .suggestion-info { flex: 1; min-width: 0 }
    .suggestion-title { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
    .suggestion-meta { font-size: 12px; color: var(--muted); margin-top: 2px }
    .suggestion-type { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--red) }

    /* ── FILTER ROW ── */
    .filter-row {
      display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    }
    .filter-pill {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 16px; border-radius: 30px;
      background: var(--surface2); border: 1px solid var(--border);
      font-size: 13px; font-weight: 500; color: var(--muted);
      cursor: pointer; transition: all var(--trans); white-space: nowrap;
    }
    .filter-pill:hover { border-color: rgba(229,9,20,.35); color: var(--text) }
    .filter-pill.active { background: var(--red); border-color: var(--red); color: #fff }

    .filter-sep { width: 1px; height: 20px; background: var(--border); margin: 0 4px }

    .filter-select {
      appearance: none; background: var(--surface2); border: 1px solid var(--border);
      color: var(--text); font-family: var(--font-body); font-size: 13px;
      padding: 8px 34px 8px 14px; border-radius: 8px; cursor: pointer;
      transition: border-color var(--trans);
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 10px center;
    }
    .filter-select:focus { outline: none; border-color: var(--red) }
    .filter-select option { background: #1a1a1a }

    /* ── RESULTS BAR ── */
    .results-bar {
      padding: 20px 4vw;
      display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
      border-bottom: 1px solid var(--border);
    }
    .results-count { font-size: 14px; color: var(--muted) }
    .results-count strong { color: var(--text); font-weight: 600 }
    .results-count em { color: var(--red); font-style: normal; font-weight: 600 }
    .results-timing { font-size: 12px; color: #444 }

    /* ── MAIN ── */
    .search-main { padding: 36px 4vw 80px }

    /* ── VIDEO CARD (matches index.php exactly) ── */
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
    .badge-series { background: rgba(245,197,24,.15); border: 1px solid rgba(245,197,24,.3); color: var(--gold) }
    .premium-badge {
      position: absolute; top: 35px; left: 10px;
      background: linear-gradient(135deg, #f5c518, #e09000);
      color: #000; font-size: 10px; font-weight: 700; padding: 3px 8px;
      border-radius: 4px; letter-spacing: .5px; text-transform: uppercase; z-index: 5;
    }
    .premium-badge i { margin-right: 3px; font-size: 10px; }
    .card-info { padding: 12px 14px 14px }
    .card-info h3 { font-size: 14px; font-weight: 600; margin-bottom: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
    .meta { display: flex; align-items: center; gap: 10px; margin-bottom: 4px }
    .rating { display: flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600; color: var(--gold) }
    .year { font-size: 12px; color: var(--muted) }
    .card-category { font-size: 11px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: rgba(229,9,20,.8); display: block; margin-top: 2px }
    .card-desc { font-size: 12px; color: var(--muted); margin-top: 6px; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden }

    /* Highlight matched query text */
    .highlight { color: var(--text); background: rgba(229,9,20,.2); border-radius: 2px; padding: 0 2px }

    /* ── RESULTS GRID ── */
    .results-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px }

    /* ── NO RESULTS ── */
    .no-results {
      display: flex; flex-direction: column; align-items: center; gap: 16px;
      padding: 80px 20px; text-align: center;
    }
    .no-results-icon {
      width: 80px; height: 80px; border-radius: 50%;
      background: var(--surface); border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      font-size: 28px; color: var(--muted);
    }
    .no-results h2 { font-family: var(--font-display); font-size: 32px; letter-spacing: 2px }
    .no-results p { font-size: 15px; color: var(--muted); max-width: 380px }
    .suggestions-list {
      display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin-top: 4px;
    }
    .sug-pill {
      padding: 7px 16px; border-radius: 30px;
      background: var(--surface2); border: 1px solid var(--border);
      font-size: 13px; color: var(--muted); cursor: pointer;
      transition: all var(--trans);
    }
    .sug-pill:hover { border-color: rgba(229,9,20,.35); color: var(--text) }

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

    /* ── POPULAR (empty state) ── */
    .section-label {
      font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
      color: var(--muted); margin-bottom: 20px;
      display: flex; align-items: center; gap: 10px;
    }
    .section-label::after { content: ''; flex: 1; height: 1px; background: var(--border) }

    .popular-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px }
    .popular-card {
      position: relative; border-radius: var(--card-r); overflow: hidden;
      cursor: pointer; aspect-ratio: 2/3;
      transition: transform var(--trans), box-shadow var(--trans);
    }
    .popular-card:hover { transform: scale(1.04) translateY(-3px); box-shadow: 0 16px 40px rgba(0,0,0,.6) }
    .popular-card img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: transform .5s ease }
    .popular-card:hover img { transform: scale(1.08) }
    .popular-card-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(to top, rgba(8,8,8,.92) 0%, transparent 55%);
      display: flex; align-items: flex-end; padding: 12px;
    }
    .popular-card-title { font-size: 12px; font-weight: 600; line-height: 1.3; color: var(--text) }

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
    @media(max-width:900px) { .results-grid { grid-template-columns: repeat(auto-fill, minmax(155px, 1fr)) } }
    @media(max-width:600px) {
      .results-grid { grid-template-columns: repeat(2, 1fr); gap: 12px }
      .popular-grid { grid-template-columns: repeat(3, 1fr) }
      .filter-row { gap: 6px }
      .search-input { font-size: 15px; padding-right: 100px }
    }
  </style>
</head>
<body>

<!-- ── SEARCH HERO ── -->
<div class="search-hero animate-in">
  <div class="hero-eyebrow"><i class="fas fa-magnifying-glass"></i> StreamVault</div>
  <h1 class="hero-title">
    <?php if ($query): ?>
      RESULTS FOR <span>"<?php echo htmlspecialchars(strtoupper($query)); ?>"</span>
    <?php else: ?>
      SEARCH <span>EVERYTHING</span>
    <?php endif; ?>
  </h1>

  <!-- Search form -->
  <form action="" method="GET" class="search-form" id="searchForm" autocomplete="off">
    <div class="search-input-wrap" id="searchWrap">
      <i class="fas fa-search search-icon"></i>
      <input
        class="search-input"
        type="text" name="q" id="searchInput"
        placeholder="Search movies, series, genres…"
        value="<?php echo htmlspecialchars($query); ?>">
      <button type="submit" class="search-submit">
        <i class="fas fa-search"></i> Search
      </button>
      <!-- Live suggestions dropdown -->
      <div class="suggestions-drop" id="suggestDrop"></div>
    </div>

    <!-- Filters -->
    <div class="filter-row">
      <!-- Type pills -->
      <?php foreach (['all' => 'All', 'movie' => '🎬 Movies', 'series' => '📺 Series'] as $val => $label): ?>
        <button type="submit" name="type" value="<?php echo $val; ?>"
                class="filter-pill <?php echo $type === $val ? 'active' : ''; ?>">
          <?php echo $label; ?>
        </button>
      <?php endforeach; ?>

      <div class="filter-sep"></div>

      <!-- Category select -->
      <select name="category" class="filter-select" id="catSelect">
        <option value="0">All Genres</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($cat['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- Hidden fields to preserve state on filter change -->
      <input type="hidden" name="q" value="<?php echo htmlspecialchars($query); ?>">
    </div>
  </form>
</div>

<!-- ── RESULTS BAR ── -->
<?php if (!empty($query)): ?>
<div class="results-bar">
  <p class="results-count">
    Found <strong><?php echo number_format($totalResults); ?></strong>
    result<?php echo $totalResults !== 1 ? 's' : ''; ?> for
    <em>"<?php echo htmlspecialchars($query); ?>"</em>
    <?php if ($type !== 'all'): ?> in <strong><?php echo ucfirst($type); ?>s</strong><?php endif; ?>
  </p>
  <?php if ($totalResults > 0 && $totalPages > 1): ?>
    <span class="results-timing">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── MAIN ── -->
<main class="search-main">

  <?php if (!empty($query)): ?>

    <?php if (empty($results)): ?>
    <!-- No Results -->
    <div class="no-results animate-in">
      <div class="no-results-icon"><i class="fas fa-magnifying-glass"></i></div>
      <h2>No Results Found</h2>
      <p>We couldn't find anything for <strong style="color:var(--text)">"<?php echo htmlspecialchars($query); ?>"</strong>. Try a different spelling or keyword.</p>
      <div class="suggestions-list">
        <span class="sug-pill" onclick="setSearch('action')">Action</span>
        <span class="sug-pill" onclick="setSearch('comedy')">Comedy</span>
        <span class="sug-pill" onclick="setSearch('drama')">Drama</span>
        <span class="sug-pill" onclick="setSearch('thriller')">Thriller</span>
        <span class="sug-pill" onclick="setSearch('sci-fi')">Sci-Fi</span>
      </div>
    </div>

    <?php else: ?>
    <!-- Results Grid -->
    <div class="results-grid">
      <?php foreach ($results as $i => $item): ?>
        <?php $isMovie = ($item['type'] !== 'series'); ?>
        <div class="video-card"
             style="opacity:0;animation:fadeUp .45s ease <?php echo ($i % 8) * 45; ?>ms both"
             onclick="location.href='<?php echo $SITE_URL . ($isMovie ? 'watch.php' : 'series-details.php'); ?>?id=<?php echo $item['id']; ?>'">

          <span class="type-badge <?php echo $isMovie ? 'badge-movie' : 'badge-series'; ?>">
            <?php echo $isMovie ? 'Movie' : 'Series'; ?>
          </span>

          <?php if (($item['access_level'] ?? 'free') === 'premium'): ?>
            <div class="premium-badge"><i class="fas fa-crown"></i> Premium</div>
          <?php endif; ?>

          <div class="card-img">
            <img src="<?php echo THUMBNAIL_URL . htmlspecialchars($item['thumbnail_path']); ?>"
                 alt="<?php echo htmlspecialchars($item['title']); ?>"
                 loading="lazy"
                 onerror="this.src='assets/placeholder.jpg'">
            <div class="play-overlay"><i class="fas fa-play"></i></div>
          </div>

          <div class="card-info">
            <h3><?php
              // Highlight matching query in title
              $safeTitle = htmlspecialchars($item['title']);
              $safeQ     = preg_quote(htmlspecialchars($query), '/');
              echo preg_replace('/(' . $safeQ . ')/i', '<span class="highlight">$1</span>', $safeTitle);
            ?></h3>
            <div class="meta">
              <?php if ($item['rating']): ?>
                <span class="rating"><i class="fas fa-star"></i> <?php echo number_format($item['rating'], 1); ?></span>
              <?php endif; ?>
              <?php if ($item['release_year']): ?>
                <span class="year"><?php echo $item['release_year']; ?></span>
              <?php endif; ?>
            </div>
            <?php if ($item['category_name']): ?>
              <span class="card-category"><?php echo htmlspecialchars($item['category_name']); ?></span>
            <?php endif; ?>
            <?php if (!empty($item['description'])): ?>
              <p class="card-desc"><?php echo htmlspecialchars(substr($item['description'], 0, 90)); ?>…</p>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav class="pagination">
      <?php if ($page > 1): ?>
        <a href="<?php echo buildUrl(['page' => $page-1]); ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
      <?php endif; ?>
      <?php
        $shown = []; $prev = null;
        for ($i = 1; $i <= $totalPages; $i++) {
          if ($i == 1 || $i == $totalPages || abs($i - $page) <= 2) $shown[] = $i;
        }
        foreach ($shown as $p):
          if ($prev !== null && $p - $prev > 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
          <a href="<?php echo buildUrl(['page' => $p]); ?>"
             class="page-btn <?php echo $p == $page ? 'active' : ''; ?>"><?php echo $p; ?></a>
      <?php $prev = $p; endforeach; ?>
      <?php if ($page < $totalPages): ?>
        <a href="<?php echo buildUrl(['page' => $page+1]); ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
      <?php endif; ?>
    </nav>
    <?php endif; ?>

    <?php endif; // empty results ?>

  <?php else: ?>

  <!-- ── EMPTY STATE: Popular Videos ── -->
  <p class="section-label">Trending Now</p>
  <div class="popular-grid">
    <?php foreach ($popular as $i => $item): ?>
      <?php $isMovie = ($item['type'] !== 'series'); ?>
      <div class="popular-card"
           style="opacity:0;animation:fadeUp .4s ease <?php echo $i * 40; ?>ms both"
           onclick="location.href='<?php echo $SITE_URL . ($isMovie ? 'watch.php' : 'series-details.php'); ?>?id=<?php echo $item['id']; ?>'">
        <?php if (($item['access_level'] ?? 'free') === 'premium'): ?>
          <div class="premium-badge" style="top:10px;"><i class="fas fa-crown"></i> Premium</div>
        <?php endif; ?>
        <img src="<?php echo THUMBNAIL_URL . htmlspecialchars($item['thumbnail_path']); ?>"
             alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
        <div class="popular-card-overlay">
          <span class="popular-card-title"><?php echo htmlspecialchars($item['title']); ?></span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php endif; ?>

</main>

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
  const SITE_URL    = '<?php echo $SITE_URL; ?>';
  const THUMB_URL   = '<?php echo THUMBNAIL_URL; ?>';

  /* ── CATEGORY AUTO-SUBMIT ── */
  document.getElementById('catSelect').addEventListener('change', () => {
    document.getElementById('searchForm').submit();
  });

  /* ── QUICK-SET SEARCH (no-results suggestions) ── */
  function setSearch(term) {
    document.getElementById('searchInput').value = term;
    document.getElementById('searchForm').submit();
  }

  /* ── LIVE SUGGESTIONS ── */
  const input  = document.getElementById('searchInput');
  const drop   = document.getElementById('suggestDrop');
  let debounce = null;

  input.addEventListener('input', function () {
    clearTimeout(debounce);
    const q = this.value.trim();
    if (q.length < 2) { drop.classList.remove('open'); return; }
    debounce = setTimeout(() => fetchSuggestions(q), 280);
  });

  input.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { drop.classList.remove('open'); }
  });

  document.addEventListener('click', e => {
    if (!e.target.closest('#searchWrap')) drop.classList.remove('open');
  });

  function fetchSuggestions(q) {
    fetch(SITE_URL + 'ajax/search-suggestions.php?q=' + encodeURIComponent(q))
      .then(r => r.json())
      .then(data => {
        if (!data.length) { drop.classList.remove('open'); return; }
        drop.innerHTML = data.slice(0, 7).map(item => `
          <div class="suggestion-item" onclick="location.href='${SITE_URL}${item.type !== 'series' ? 'watch.php' : 'series-details.php'}?id=${item.id}'">
            <div class="suggestion-thumb">
              <img src="${THUMB_URL}${item.thumbnail_path}" alt="${item.title}" onerror="this.style.display='none'">
            </div>
            <div class="suggestion-info">
              <div class="suggestion-title">${item.title}</div>
              <div class="suggestion-meta">${item.release_year || ''} ${item.category_name ? '· ' + item.category_name : ''}</div>
            </div>
            <span class="suggestion-type">${item.type || ''}</span>
          </div>
        `).join('');
        drop.classList.add('open');
      })
      .catch(() => drop.classList.remove('open'));
  }
</script>

</body>
</html>

<?php require_once 'includes/footer.php'; ?>