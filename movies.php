<?php
$pageTitle = 'Movies';
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
          WHERE c.type = 'movie'";
$countQuery = "SELECT COUNT(*) as count FROM videos v 
               INNER JOIN categories c ON v.category_id = c.id 
               WHERE c.type = 'movie'";
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
if ($categoryId) {
    $stmt->execute([$categoryId]);
} else {
    $stmt->execute();
}
$totalMovies = $stmt->fetch()['count'];

$stmt = $db->prepare($query);
$stmt->execute($params);
$movies = $stmt->fetchAll();

$totalPages = ceil($totalMovies / $limit);

$categories = $db->query("SELECT * FROM categories WHERE type = 'movie' ORDER BY name")->fetchAll();

$SITE_URL = rtrim(SITE_URL, '/') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Movies — StreamVault</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* ── RESET & TOKENS ── */
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
    body {
      font-family: var(--font-body);
      background: var(--bg);
      color: var(--text);
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
    }
    a { text-decoration: none; color: inherit }
    img { display: block; width: 100%; object-fit: cover }

    ::-webkit-scrollbar { width: 6px; height: 6px }
    ::-webkit-scrollbar-track { background: var(--bg) }
    ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px }

    /* ── PAGE HERO BANNER ── */
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
        radial-gradient(ellipse 60% 80% at 80% 50%, rgba(229,9,20,.08) 0%, transparent 70%),
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
    .banner-inner {
      position: relative;
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 24px;
    }
    .banner-left {}
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
    .banner-eyebrow i { font-size: 10px }
    .banner-title {
      font-family: var(--font-display);
      font-size: clamp(48px, 7vw, 88px);
      letter-spacing: 3px;
      line-height: .92;
      margin-bottom: 14px;
    }
    .banner-title span {
      color: var(--red);
    }
    .banner-meta {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .banner-count {
      font-size: 14px;
      color: var(--muted);
    }
    .banner-count strong {
      color: var(--text);
      font-weight: 600;
    }
    .banner-dot {
      width: 4px; height: 4px;
      border-radius: 50%;
      background: var(--muted);
    }

    /* ── FILTERS BAR ── */
    .filters-section {
      padding: 28px 4vw;
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      position: sticky;
      top: 0;
      z-index: 100;
      backdrop-filter: blur(20px);
    }
    .filters-inner {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
    }
    .filters-label {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--muted);
      margin-right: 4px;
    }

    /* Category pill filters */
    .filter-pills {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
      flex: 1;
    }
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
    .filter-pill:hover {
      border-color: rgba(229,9,20,.4);
      color: var(--text);
    }
    .filter-pill.active {
      background: var(--red);
      border-color: var(--red);
      color: #fff;
    }

    /* Sort select */
    .sort-wrap {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-left: auto;
    }
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

    /* ── MAIN CONTENT ── */
    .movies-main {
      padding: 48px 4vw 80px;
    }

    /* Active filter info bar */
    .active-filter-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 32px;
      padding: 14px 20px;
      background: rgba(229,9,20,.07);
      border: 1px solid rgba(229,9,20,.2);
      border-radius: 10px;
    }
    .active-filter-text {
      font-size: 14px;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .active-filter-text i { color: var(--red) }
    .clear-filter {
      font-size: 13px;
      color: var(--muted);
      display: flex;
      align-items: center;
      gap: 6px;
      cursor: pointer;
      transition: color var(--trans);
    }
    .clear-filter:hover { color: var(--red) }

    /* ── VIDEO GRID ── */
    .movies-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 20px;
    }

    /* ── VIDEO CARD ── (matches index.php) */
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
    .card-img {
      position: relative;
      overflow: hidden;
      aspect-ratio: 2/3;
    }
    .card-img img {
      height: 100%;
      transition: transform .5s ease;
    }
    .video-card:hover .card-img img { transform: scale(1.08) }
    .play-overlay {
      position: absolute;
      inset: 0;
      background: rgba(0,0,0,.4);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity var(--trans);
    }
    .video-card:hover .play-overlay { opacity: 1 }
    .play-overlay i {
      font-size: 44px;
      color: #fff;
      filter: drop-shadow(0 0 20px rgba(229,9,20,.6));
      transition: transform var(--trans);
    }
    .video-card:hover .play-overlay i { transform: scale(1.1) }

    .card-badge {
      position: absolute;
      top: 10px; left: 10px;
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
    .badge-hd {
      background: rgba(245,197,24,.15);
      border: 1px solid rgba(245,197,24,.35);
      color: var(--gold);
    }
    .duration-badge {
      position: absolute;
      bottom: 10px; right: 10px;
      background: rgba(0,0,0,.75);
      backdrop-filter: blur(6px);
      color: var(--text);
      font-size: 11px;
      font-weight: 600;
      padding: 3px 8px;
      border-radius: 4px;
    }
    .card-info {
      padding: 12px 14px 14px;
    }
    .card-info h3 {
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 8px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .meta {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .rating {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 12px;
      font-weight: 600;
      color: var(--gold);
    }
    .year {
      font-size: 12px;
      color: var(--muted);
    }
    .card-category {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: .5px;
      text-transform: uppercase;
      color: rgba(229,9,20,.8);
      margin-top: 6px;
      display: block;
    }

    /* ── EMPTY STATE ── */
    .empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 16px;
      padding: 100px 20px;
      text-align: center;
    }
    .empty-icon {
      width: 80px; height: 80px;
      border-radius: 50%;
      background: var(--surface);
      border: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      color: var(--muted);
    }
    .empty-state h2 {
      font-family: var(--font-display);
      font-size: 32px;
      letter-spacing: 2px;
      color: var(--text);
    }
    .empty-state p {
      font-size: 15px;
      color: var(--muted);
    }
    .btn-browse {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--red);
      color: #fff;
      font-weight: 700;
      font-size: 14px;
      padding: 12px 28px;
      border-radius: 8px;
      margin-top: 8px;
      transition: background var(--trans), transform var(--trans);
    }
    .btn-browse:hover { background: var(--red-dim); transform: translateY(-2px) }

    /* ── PAGINATION ── */
    .pagination {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 60px;
    }
    .page-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 40px;
      height: 40px;
      padding: 0 8px;
      border-radius: 8px;
      background: var(--surface);
      border: 1px solid var(--border);
      font-size: 14px;
      font-weight: 600;
      color: var(--muted);
      transition: all var(--trans);
      cursor: pointer;
    }
    .page-btn:hover {
      border-color: rgba(229,9,20,.4);
      color: var(--text);
    }
    .page-btn.active {
      background: var(--red);
      border-color: var(--red);
      color: #fff;
    }
    .page-btn.arrow {
      font-size: 13px;
    }
    .page-ellipsis {
      color: var(--muted);
      font-size: 14px;
      padding: 0 4px;
    }

    /* ── FOOTER ── (matches index.php) */
    .footer {
      padding: 48px 4vw 32px;
      border-top: 1px solid var(--border);
    }
    .footer-top {
      display: flex;
      flex-wrap: wrap;
      gap: 40px;
      margin-bottom: 40px;
    }
    .footer-brand { flex: 1; min-width: 200px }
    .footer-logo {
      font-family: var(--font-display);
      font-size: 26px;
      letter-spacing: 2px;
      color: var(--red);
      margin-bottom: 12px;
    }
    .footer-logo span { color: var(--text) }
    .footer-desc {
      font-size: 14px;
      color: var(--muted);
      line-height: 1.7;
      max-width: 280px;
    }
    .footer-links { display: flex; flex-wrap: wrap; gap: 40px; flex: 2 }
    .footer-col h4 {
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 16px;
    }
    .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 10px }
    .footer-col a {
      font-size: 14px;
      color: rgba(255,255,255,.5);
      transition: color var(--trans);
    }
    .footer-col a:hover { color: var(--text) }
    .footer-bottom {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      padding-top: 24px;
      border-top: 1px solid var(--border);
    }
    .footer-copy { font-size: 13px; color: var(--muted) }
    .footer-socials { display: flex; gap: 14px }
    .footer-socials a {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: var(--surface);
      border: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--muted);
      font-size: 14px;
      transition: all var(--trans);
    }
    .footer-socials a:hover {
      background: var(--red);
      border-color: var(--red);
      color: #fff;
    }

    /* ── ANIMATIONS ── */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px) }
      to   { opacity: 1; transform: translateY(0) }
    }
    .animate-in {
      animation: fadeUp .5s ease both;
    }

    /* ── RESPONSIVE ── */
    @media(max-width:900px) {
      .movies-grid { grid-template-columns: repeat(auto-fill, minmax(155px, 1fr)) }
    }
    @media(max-width:600px) {
      .movies-grid { grid-template-columns: repeat(2, 1fr); gap: 12px }
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
  <div class="banner-inner">
    <div class="banner-left">
      <div class="banner-eyebrow">
        <i class="fas fa-film"></i> StreamVault
      </div>
      <h1 class="banner-title">ALL <span>MOVIES</span></h1>
      <div class="banner-meta">
        <span class="banner-count">
          <strong><?php echo number_format($totalMovies); ?></strong> titles available
        </span>
        <?php if ($categoryId && !empty($categories)): ?>
          <?php $activeCat = array_filter($categories, fn($c) => $c['id'] == $categoryId); ?>
          <?php $activeCat = reset($activeCat); ?>
          <?php if ($activeCat): ?>
            <span class="banner-dot"></span>
            <span class="banner-count">Filtered by <strong><?php echo htmlspecialchars($activeCat['name']); ?></strong></span>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ── FILTERS ── -->
<div class="filters-section">
  <div class="filters-inner">
    <span class="filters-label"><i class="fas fa-sliders" style="margin-right:6px"></i>Genre</span>
    <div class="filter-pills">
      <a href="?page=1" class="filter-pill <?php echo !$categoryId ? 'active' : ''; ?>">
        All
      </a>
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

<!-- ── MAIN ── -->
<main class="movies-main">

  <?php if (empty($movies)): ?>
  <!-- Empty State -->
  <div class="empty-state animate-in">
    <div class="empty-icon"><i class="fas fa-film"></i></div>
    <h2>No Movies Found</h2>
    <p>We couldn't find any movies in this category.</p>
    <a href="movies.php" class="btn-browse">
      <i class="fas fa-th"></i> Browse All Movies
    </a>
  </div>

  <?php else: ?>

  <!-- Movies Grid -->
  <div class="movies-grid" id="moviesGrid">
    <?php foreach ($movies as $i => $movie): ?>
      <div class="video-card" style="opacity:0;animation:fadeUp .5s ease <?php echo ($i % 8) * 50; ?>ms both"
           onclick="location.href='<?php echo $SITE_URL; ?>watch.php?id=<?php echo $movie['id']; ?>'">

        <?php if ($movie['duration']): ?>
          <div class="duration-badge"><?php echo htmlspecialchars($movie['duration']); ?></div>
        <?php endif; ?>

        <?php if (($movie['access_level'] ?? 'free') === 'premium'): ?>
          <div class="card-badge premium-badge"><i class="fas fa-crown"></i> Premium</div>
        <?php endif; ?>

        <div class="card-img">
          <img src="<?php echo THUMBNAIL_URL . htmlspecialchars($movie['thumbnail_path']); ?>"
               alt="<?php echo htmlspecialchars($movie['title']); ?>"
               loading="lazy"
               onerror="this.src='assets/placeholder.jpg'">
          <div class="play-overlay"><i class="fas fa-play"></i></div>
        </div>

        <div class="card-info">
          <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
          <div class="meta">
            <?php if ($movie['rating']): ?>
              <span class="rating"><i class="fas fa-star"></i> <?php echo $movie['rating']; ?></span>
            <?php endif; ?>
            <?php if ($movie['release_year']): ?>
              <span class="year"><?php echo $movie['release_year']; ?></span>
            <?php endif; ?>
          </div>
          <?php if ($movie['category_name']): ?>
            <span class="card-category"><?php echo htmlspecialchars($movie['category_name']); ?></span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <nav class="pagination">

    <?php if ($page > 1): ?>
      <a href="?page=<?php echo $page - 1; ?><?php echo $categoryId ? '&category='.$categoryId : ''; ?>"
         class="page-btn arrow"><i class="fas fa-chevron-left"></i></a>
    <?php endif; ?>

    <?php
      // Smart pagination: show first, last, current ±2, with ellipsis
      $range = 2;
      $shown = [];
      for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == 1 || $i == $totalPages || abs($i - $page) <= $range) {
          $shown[] = $i;
        }
      }
      $prev = null;
      foreach ($shown as $p):
        if ($prev !== null && $p - $prev > 1):
    ?>
      <span class="page-ellipsis">…</span>
    <?php endif; ?>
      <a href="?page=<?php echo $p; ?><?php echo $categoryId ? '&category='.$categoryId : ''; ?>"
         class="page-btn <?php echo $p == $page ? 'active' : ''; ?>">
        <?php echo $p; ?>
      </a>
    <?php $prev = $p; endforeach; ?>

    <?php if ($page < $totalPages): ?>
      <a href="?page=<?php echo $page + 1; ?><?php echo $categoryId ? '&category='.$categoryId : ''; ?>"
         class="page-btn arrow"><i class="fas fa-chevron-right"></i></a>
    <?php endif; ?>

  </nav>
  <?php endif; ?>

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
          <li><a href="#">TV Shows</a></li>
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
  /* ── SORT SELECT ── */
  document.getElementById('sortSelect').addEventListener('change', function() {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', this.value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
  });

  // Restore sort state from URL
  const urlSort = new URLSearchParams(window.location.search).get('sort');
  if (urlSort) document.getElementById('sortSelect').value = urlSort;

  /* ── CARD REVEAL ── */
  const cards = document.querySelectorAll('.video-card');
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) io.unobserve(e.target); });
  }, { rootMargin: '-30px' });
  cards.forEach(c => io.observe(c));
</script>

</body>
</html>

<?php require_once 'includes/footer.php'; ?>