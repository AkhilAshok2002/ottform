<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once 'includes/header.php';

$isLoggedIn = $auth->isLoggedIn();
$currentUserId = $_SESSION['user_id'] ?? null;
$SITE_URL = rtrim(SITE_URL, '/') . '/';

function getThumbnailUrl($thumbnailPath)
{
    $thumbnailPath = trim((string) $thumbnailPath);
    if ($thumbnailPath === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $thumbnailPath) === 1) {
        return $thumbnailPath;
    }

    if (strpos($thumbnailPath, 'assets/') === 0) {
        return rtrim(SITE_URL, '/') . '/' . ltrim($thumbnailPath, '/');
    }

    return THUMBNAIL_URL . ltrim($thumbnailPath, '/');
}

$featuredVideos = $video->getFeaturedVideos();
if (empty($featuredVideos)) {
    $featuredVideos = $db->query("\n    SELECT v.*, c.name as category_name\n    FROM videos v\n    LEFT JOIN categories c ON v.category_id = c.id\n    ORDER BY v.created_at DESC\n    LIMIT 10\n  ")->fetchAll();
}
$featuredVideos = array_slice($featuredVideos, 0, 10);

$trendingVideos = $video->getTrendingVideos();
if (empty($trendingVideos)) {
    $trendingVideos = $db->query("\n    SELECT v.*, c.name as category_name\n    FROM videos v\n    LEFT JOIN categories c ON v.category_id = c.id\n    ORDER BY v.views DESC, v.created_at DESC\n    LIMIT 8\n  ")->fetchAll();
}
$trendingVideos = array_slice($trendingVideos, 0, 8);

$latestVideos = array_slice($video->getLatestVideos(), 0, 8);

$categories = $db->query("\n  SELECT id, name, type\n  FROM categories\n  ORDER BY name ASC\n  LIMIT 12\n")->fetchAll();

$categoryIcons = [
    'action' => '⚡',
    'comedy' => '😂',
    'drama' => '🎭',
    'sci-fi' => '🚀',
    'horror' => '👻',
    'romance' => '❤️',
    'documentary' => '🎬',
    'thriller' => '🔪',
    'animation' => '🎨',
];
foreach ($categories as &$category) {
    $iconKey = strtolower(trim((string) $category['name']));
    $category['icon'] = $categoryIcons[$iconKey] ?? ($category['type'] === 'series' ? '📺' : '🎬');
}
unset($category);

$watchHistory = [];
$recommendations = [];
if ($isLoggedIn && $currentUserId) {
    $watchHistory = $video->getWatchHistory($currentUserId);
    $recommendations = $video->getRecommendations($currentUserId);
}

$heroVideo = $featuredVideos[0] ?? ($trendingVideos[0] ?? ($latestVideos[0] ?? null));
$heroBackground = $heroVideo ? getThumbnailUrl($heroVideo['thumbnail_path'] ?? '') : '';
$heroTitle = $heroVideo['title'] ?? 'StreamVault';
$heroDescription = trim((string) ($heroVideo['description'] ?? ''));
if ($heroDescription === '') {
    $heroDescription = 'Discover the latest movies and series from your library.';
}
$heroYear = $heroVideo['release_year'] ?? '';
$heroCategory = $heroVideo['category_name'] ?? '';
$heroDuration = $heroVideo['duration'] ?? '';
$heroRating = $heroVideo['rating'] ?? '';
$heroLink = $heroVideo ? ($SITE_URL . 'watch.php?id=' . (int) $heroVideo['id']) : ($SITE_URL . 'movies.php');
$userInitial = strtoupper(substr((string) ($_SESSION['user_name'] ?? 'U'), 0, 1));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StreamVault — Watch. Anytime.</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ── RESET & TOKENS ──────────────────────────────────────────────────────── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        :root {
            --bg: #080808;
            --surface: #111111;
            --surface2: #1a1a1a;
            --border: rgba(255, 255, 255, .07);
            --red: #e50914;
            --red-dim: #b8070f;
            --gold: #f5c518;
            --text: #f0f0f0;
            --muted: #888;
            --font-display: 'Bebas Neue', sans-serif;
            --font-body: 'DM Sans', sans-serif;
            --card-r: 12px;
            --trans: .3s cubic-bezier(.4, 0, .2, 1);
        }

        html {
            scroll-behavior: smooth
        }

        body {
            font-family: var(--font-body);
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased
        }

        a {
            text-decoration: none;
            color: inherit
        }

        img {
            display: block;
            width: 100%;
            object-fit: cover
        }

        /* ── SCROLLBAR ───────────────────────────────────────────────────────────── */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px
        }

        ::-webkit-scrollbar-track {
            background: var(--bg)
        }

        ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 3px
        }

        /* ── NAVBAR ──────────────────────────────────────────────────────────────── */
    
        /* ── HERO ────────────────────────────────────────────────────────────────── */
        .hero {
            position: relative;
            height: 100vh;
            min-height: 600px;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            background: url('https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=1600&h=900&fit=crop') center/cover no-repeat;
            transform: scale(1.05);
            animation: heroZoom 20s ease-in-out infinite alternate;
        }

        @keyframes heroZoom {
            from {
                transform: scale(1.05)
            }

            to {
                transform: scale(1.12)
            }
        }

        .hero-gradient {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(to right, rgba(8, 8, 8, .95) 40%, transparent 80%),
                linear-gradient(to top, rgba(8, 8, 8, 1) 0%, rgba(8, 8, 8, .2) 30%, transparent 60%);
        }

        .hero-particles {
            position: absolute;
            inset: 0;
            pointer-events: none
        }

        .hero-content {
            position: relative;
            padding: 0 4vw;
            max-width: 680px;
            animation: heroFadeIn .9s ease both
        }

        @keyframes heroFadeIn {
            from {
                opacity: 0;
                transform: translateY(30px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(229, 9, 20, .15);
            border: 1px solid rgba(229, 9, 20, .35);
            color: var(--red);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 30px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }

        .hero-badge i {
            font-size: 10px
        }

        .hero-title {
            font-family: var(--font-display);
            font-size: clamp(52px, 8vw, 92px);
            line-height: .95;
            letter-spacing: 2px;
            margin-bottom: 16px;
            text-shadow: 0 4px 40px rgba(0, 0, 0, .6)
        }

        .hero-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px
        }

        .hero-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--gold);
            font-size: 14px;
            font-weight: 600
        }

        .hero-dot {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: var(--muted)
        }

        .hero-tag {
            font-size: 13px;
            color: var(--muted)
        }

        .hero-desc {
            font-size: 16px;
            line-height: 1.7;
            color: rgba(255, 255, 255, .75);
            margin-bottom: 32px;
            max-width: 500px
        }

        .hero-actions {
            display: flex;
            gap: 14px;
            flex-wrap: wrap
        }

        .btn-watch {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--red);
            color: #fff;
            font-weight: 700;
            font-size: 15px;
            padding: 14px 32px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: background var(--trans), transform var(--trans), box-shadow var(--trans);
            box-shadow: 0 4px 24px rgba(229, 9, 20, .35);
        }

        .btn-watch:hover {
            background: var(--red-dim);
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(229, 9, 20, .5)
        }

        .btn-info {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, .12);
            color: #fff;
            font-weight: 600;
            font-size: 15px;
            padding: 14px 28px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, .15);
            cursor: pointer;
            backdrop-filter: blur(12px);
            transition: background var(--trans), transform var(--trans);
        }

        .btn-info:hover {
            background: rgba(255, 255, 255, .2);
            transform: translateY(-2px)
        }

        .hero-scroll {
            position: absolute;
            bottom: 32px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateX(-50%) translateY(0)
            }

            50% {
                transform: translateX(-50%) translateY(-8px)
            }
        }

        .hero-scroll i {
            font-size: 18px
        }

        /* ── SECTION COMMONS ─────────────────────────────────────────────────────── */
        .section {
            padding: 60px 4vw
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px
        }

        .section-title {
            font-family: var(--font-display);
            font-size: clamp(22px, 3vw, 30px);
            letter-spacing: 2px;
            position: relative;
            padding-left: 16px
        }

        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 10%;
            bottom: 10%;
            width: 3px;
            background: var(--red);
            border-radius: 2px
        }

        .view-all {
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color var(--trans)
        }

        .view-all:hover {
            color: var(--red)
        }

        .view-all i {
            font-size: 11px;
            transition: transform var(--trans)
        }

        .view-all:hover i {
            transform: translateX(4px)
        }

        /* ── VIDEO CARD ──────────────────────────────────────────────────────────── */
        .video-card {
            position: relative;
            border-radius: var(--card-r);
            overflow: hidden;
            background: var(--surface);
            cursor: pointer;
            transition: transform var(--trans), box-shadow var(--trans);
            flex-shrink: 0;
        }

        .video-card:hover {
            transform: scale(1.04) translateY(-4px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, .7), 0 0 0 1px rgba(255, 255, 255, .08);
            z-index: 2
        }

        .card-img {
            position: relative;
            overflow: hidden;
            aspect-ratio: 2/3
        }

        .card-img img {
            height: 100%;
            transition: transform .5s ease
        }

        .video-card:hover .card-img img {
            transform: scale(1.08)
        }

        .play-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, .4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity var(--trans);
        }

        .video-card:hover .play-overlay {
            opacity: 1
        }

        .play-overlay i {
            font-size: 44px;
            color: #fff;
            filter: drop-shadow(0 0 20px rgba(229, 9, 20, .6));
            transition: transform var(--trans)
        }

        .video-card:hover .play-overlay i {
            transform: scale(1.1)
        }

        .card-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--red);
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
        }
        
        .premium-badge i {
            margin-right: 3px;
            font-size: 10px;
        }

        .card-info {
            padding: 12px 14px 14px
        }

        .card-info h3 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .meta {
            display: flex;
            align-items: center;
            gap: 10px
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
            color: var(--gold)
        }

        .duration {
            font-size: 12px;
            color: var(--muted)
        }

        /* ── HORIZONTAL SLIDER ───────────────────────────────────────────────────── */
        .slider-wrap {
            position: relative
        }

        .slider-track {
            display: flex;
            gap: 16px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
            padding-bottom: 8px;
        }

        .slider-track::-webkit-scrollbar {
            display: none
        }

        .slider-track .video-card {
            width: 200px;
            scroll-snap-align: start
        }

        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-60%);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(20, 20, 20, .9);
            border: 1px solid rgba(255, 255, 255, .1);
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background var(--trans), transform var(--trans), opacity var(--trans);
            z-index: 10;
            opacity: 0;
            backdrop-filter: blur(8px);
        }

        .slider-wrap:hover .slider-btn:not(.disabled) {
            opacity: 1
        }

        .slider-btn.disabled {
            opacity: 0 !important;
            pointer-events: none;
            cursor: default;
        }

        .slider-btn:not(.disabled):hover {
            background: var(--red);
            border-color: var(--red);
            transform: translateY(-60%) scale(1.1)
        }

        .slider-btn.prev {
            left: -22px
        }

        .slider-btn.next {
            right: -22px
        }

        /* ── VIDEO GRID ──────────────────────────────────────────────────────────── */
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 18px;
        }

        /* ── CATEGORIES ──────────────────────────────────────────────────────────── */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 14px;
        }

        .category-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 24px 16px;
            border-radius: var(--card-r);
            background: var(--surface);
            border: 1px solid var(--border);
            cursor: pointer;
            transition: transform var(--trans), background var(--trans), border-color var(--trans), box-shadow var(--trans);
            text-align: center;
        }

        .category-card:hover {
            background: var(--surface2);
            border-color: rgba(229, 9, 20, .4);
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, .5)
        }

        .cat-icon {
            font-size: 28px;
            line-height: 1
        }

        .cat-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            transition: color var(--trans)
        }

        .category-card:hover .cat-name {
            color: var(--text)
        }

        /* ── CONTINUE WATCHING ───────────────────────────────────────────────────── */
        .continue-card .card-img {
            aspect-ratio: 16/9
        }

        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: rgba(255, 255, 255, .2);
        }

        .progress-fill {
            height: 100%;
            background: var(--red);
            border-radius: 0 2px 2px 0;
            transition: width .4s ease
        }

        /* ── RECOMMENDED TAG ─────────────────────────────────────────────────────── */
        .cat-tag {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .5px;
            text-transform: uppercase;
            color: var(--red);
            background: rgba(229, 9, 20, .12);
            padding: 3px 8px;
            border-radius: 4px;
            margin-top: 4px;
        }

        /* ── SKELETON ────────────────────────────────────────────────────────────── */
        .skeleton {
            background: linear-gradient(90deg, var(--surface) 25%, var(--surface2) 50%, var(--surface) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.6s infinite
        }

        @keyframes shimmer {
            0% {
                background-position: 200% 0
            }

            100% {
                background-position: -200% 0
            }
        }

        .skel-card {
            border-radius: var(--card-r);
            overflow: hidden
        }

        .skel-img {
            aspect-ratio: 2/3
        }

        .skel-line {
            height: 12px;
            border-radius: 4px;
            margin: 10px 14px 6px
        }

        .skel-line.short {
            width: 60%;
            margin-top: 0
        }

        /* ── SEARCH OVERLAY ──────────────────────────────────────────────────────── */
        .search-overlay {
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: rgba(0, 0, 0, .96);
            backdrop-filter: blur(24px);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 120px;
            opacity: 0;
            pointer-events: none;
            transition: opacity var(--trans);
        }

        .search-overlay.open {
            opacity: 1;
            pointer-events: all
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 16px;
            width: min(680px, 90vw);
            border-bottom: 2px solid var(--red);
            padding-bottom: 12px;
        }

        .search-box i {
            font-size: 24px;
            color: var(--muted)
        }

        .search-box input {
            flex: 1;
            background: none;
            border: none;
            outline: none;
            font-family: var(--font-body);
            font-size: 28px;
            color: var(--text);
        }

        .search-box input::placeholder {
            color: var(--muted)
        }

        .search-close {
            background: none;
            border: none;
            color: var(--muted);
            font-size: 28px;
            cursor: pointer;
            transition: color var(--trans)
        }

        .search-close:hover {
            color: var(--text)
        }

        /* ── MOBILE NAV ──────────────────────────────────────────────────────────── */
        .mobile-nav {
            position: fixed;
            inset: 0;
            z-index: 998;
            background: rgba(8, 8, 8, .97);
            backdrop-filter: blur(24px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 32px;
            transform: translateX(100%);
            transition: transform .4s cubic-bezier(.77, 0, .18, 1);
        }

        .mobile-nav.open {
            transform: translateX(0)
        }

        .mobile-nav a {
            font-family: var(--font-display);
            font-size: 36px;
            letter-spacing: 3px;
            color: var(--muted);
            transition: color var(--trans)
        }

        .mobile-nav a:hover {
            color: var(--text)
        }

        /* ── FOOTER ──────────────────────────────────────────────────────────────── */
        .footer {
            padding: 48px 4vw 32px;
            border-top: 1px solid var(--border);
            margin-top: 60px
        }

        .footer-top {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            margin-bottom: 40px
        }

        .footer-brand {
            flex: 1;
            min-width: 200px
        }

        .footer-logo {
            font-family: var(--font-display);
            font-size: 26px;
            letter-spacing: 2px;
            color: var(--red);
            margin-bottom: 12px
        }

        .footer-logo span {
            color: var(--text)
        }

        .footer-desc {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.7;
            max-width: 280px
        }

        .footer-links {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            flex: 2
        }

        .footer-col h4 {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 16px
        }

        .footer-col ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px
        }

        .footer-col a {
            font-size: 14px;
            color: rgba(255, 255, 255, .5);
            transition: color var(--trans)
        }

        .footer-col a:hover {
            color: var(--text)
        }

        .footer-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding-top: 24px;
            border-top: 1px solid var(--border)
        }

        .footer-copy {
            font-size: 13px;
            color: var(--muted)
        }

        .footer-socials {
            display: flex;
            gap: 14px
        }

        .footer-socials a {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--surface);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 14px;
            transition: all var(--trans)
        }

        .footer-socials a:hover {
            background: var(--red);
            border-color: var(--red);
            color: #fff
        }

        /* ── RESPONSIVE ──────────────────────────────────────────────────────────── */
        @media(max-width:900px) {
            .nav-links {
                display: none
            }

            .hamburger {
                display: flex
            }

            .video-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr))
            }

            .slider-track .video-card {
                width: 160px
            }
        }

        @media(max-width:600px) {
            .hero-content {
                max-width: 100%
            }

            .video-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px
            }

            .categories-grid {
                grid-template-columns: repeat(4, 1fr)
            }

            .section {
                padding: 40px 4vw
            }

            .slider-track .video-card {
                width: 140px
            }
        }
    </style>
</head>

<body>

    <!-- ── SEARCH OVERLAY ─────────────────────────────────────────────────────── -->
    <div class="search-overlay" id="searchOverlay">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search movies, shows…" id="searchInput" autocomplete="off">
            <button class="search-close" id="searchClose"><i class="fas fa-times"></i></button>
        </div>
    </div>

    <!-- ── MOBILE NAV ─────────────────────────────────────────────────────────── -->


    <!-- ── HERO ───────────────────────────────────────────────────────────────── -->
<section class="hero">

    <div class="hero-bg" style="background-image:url('<?php echo $heroBackground; ?>')"></div>
    <div class="hero-gradient"></div>

    <div class="hero-content">

        <div class="hero-badge">
            <i class="fas fa-fire"></i> Trending
        </div>

        <h1 class="hero-title">
            <?php echo htmlspecialchars($heroTitle); ?>
        </h1>

        <div class="hero-meta">

            <?php if($heroRating): ?>
            <span class="hero-rating">
                <i class="fas fa-star"></i> <?php echo $heroRating; ?>
            </span>
            <?php endif; ?>

            <?php if($heroYear): ?>
            <span class="hero-dot"></span>
            <span class="hero-tag"><?php echo $heroYear; ?></span>
            <?php endif; ?>

            <?php if($heroCategory): ?>
            <span class="hero-dot"></span>
            <span class="hero-tag"><?php echo htmlspecialchars($heroCategory); ?></span>
            <?php endif; ?>

            <?php if($heroDuration): ?>
            <span class="hero-dot"></span>
            <span class="hero-tag"><?php echo $heroDuration; ?></span>
            <?php endif; ?>

        </div>

        <p class="hero-desc">
            <?php echo htmlspecialchars($heroDescription); ?>
        </p>

        <div class="hero-actions">

            <a href="<?php echo $heroLink; ?>" class="btn-watch">
                <i class="fas fa-play"></i> Watch Now
            </a>

            <a href="<?php echo $SITE_URL; ?>movies.php" class="btn-info">
                <i class="fas fa-circle-info"></i> Browse
            </a>

        </div>

    </div>

</section>

    <!-- ── FEATURED VIDEOS ────────────────────────────────────────────────────── -->
    <?php if (!empty($featuredVideos)): ?>
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">Featured</h2>
                <a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="slider-wrap">
                <button class="slider-btn prev" data-target="feat"><i class="fas fa-chevron-left"></i></button>
                <div class="slider-track" id="feat">
                    <?php foreach ($featuredVideos as $i => $v): ?>
                        <div class="video-card"
                            onclick="location.href='<?php echo $SITE_URL; ?>watch.php?id=<?php echo $v['id']; ?>'">
                            <?php if (($v['access_level'] ?? 'free') === 'premium'): ?>
                                <div class="card-badge premium-badge"><i class="fas fa-crown"></i> Premium</div>
                            <?php elseif ($i === 0): ?>
                                <div class="card-badge">Featured</div>
                            <?php endif; ?>
                            <div class="card-img">
                                <img src="<?php echo htmlspecialchars(getThumbnailUrl($v['thumbnail_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?php echo htmlspecialchars($v['title']); ?>" loading="lazy">
                                <div class="play-overlay"><i class="fas fa-play"></i></div>
                            </div>
                            <div class="card-info">
                                <h3><?php echo htmlspecialchars($v['title']); ?></h3>
                                <div class="meta">
                                    <span class="rating"><i class="fas fa-star"></i> <?php echo htmlspecialchars((string)($v['rating'] ?? '')); ?></span>
                                    <span class="duration"><?php echo htmlspecialchars((string)($v['duration'] ?? '')); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="slider-btn next" data-target="feat"><i class="fas fa-chevron-right"></i></button>
            </div>
        </section>
    <?php endif; ?>

    <!-- ── TRENDING NOW ────────────────────────────────────────────────────────── -->
    <section class="section" style="padding-top:0">
        <div class="section-header">
            <h2 class="section-title">Trending Now</h2>
            <a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="video-grid">
            <?php foreach ($trendingVideos as $i => $v): ?>
                <div class="video-card"
                    onclick="location.href='<?php echo $SITE_URL; ?>watch.php?id=<?php echo $v['id']; ?>'">
                    <?php if (($v['access_level'] ?? 'free') === 'premium'): ?>
                        <div class="card-badge premium-badge"><i class="fas fa-crown"></i> Premium</div>
                    <?php elseif ($i < 3): ?>
                        <div class="card-badge" style="background:linear-gradient(135deg,#f5c518,#e09000);color:#000">
                            #<?php echo $i + 1; ?></div>
                    <?php endif; ?>
                    <div class="card-img">
                        <img src="<?php echo htmlspecialchars(getThumbnailUrl($v['thumbnail_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($v['title']); ?>"
                            loading="lazy">
                        <div class="play-overlay"><i class="fas fa-play"></i></div>
                    </div>
                    <div class="card-info">
                        <h3><?php echo htmlspecialchars($v['title']); ?></h3>
                        <div class="meta">
                            <span class="rating"><i class="fas fa-star"></i> <?php echo htmlspecialchars((string)($v['rating'] ?? '')); ?></span>
                            <span class="duration"><?php echo htmlspecialchars((string)($v['duration'] ?? '')); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ── CATEGORIES ─────────────────────────────────────────────────────────── -->
    <section class="section" style="padding-top:0">
        <div class="section-header">
            <h2 class="section-title">Browse by Genre</h2>
        </div>
        <div class="categories-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="<?php echo $SITE_URL; ?>category.php?id=<?php echo $cat['id']; ?>" class="category-card">
                    <span class="cat-icon"><?php echo $cat['icon']; ?></span>
                    <span class="cat-name"><?php echo htmlspecialchars($cat['name']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ── LATEST UPLOADS ─────────────────────────────────────────────────────── -->
    <section class="section" style="padding-top:0">
        <div class="section-header">
            <h2 class="section-title">Latest Uploads</h2>
            <a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="video-grid">
            <?php foreach ($latestVideos as $v): ?>
                <div class="video-card"
                    onclick="location.href='<?php echo $SITE_URL; ?>watch.php?id=<?php echo $v['id']; ?>'">
                    <?php if (($v['access_level'] ?? 'free') === 'premium'): ?>
                        <div class="card-badge premium-badge"><i class="fas fa-crown"></i> Premium</div>
                    <?php else: ?>
                        <div class="card-badge" style="background:rgba(255,255,255,.15);backdrop-filter:blur(8px)">NEW</div>
                    <?php endif; ?>
                    <div class="card-img">
                        <img src="<?php echo htmlspecialchars(getThumbnailUrl($v['thumbnail_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($v['title']); ?>"
                            loading="lazy">
                        <div class="play-overlay"><i class="fas fa-play"></i></div>
                    </div>
                    <div class="card-info">
                        <h3><?php echo htmlspecialchars($v['title']); ?></h3>
                        <div class="meta">
                            <span class="rating"><i class="fas fa-star"></i> <?php echo htmlspecialchars((string)($v['rating'] ?? '')); ?></span>
                            <span class="duration"><?php echo htmlspecialchars((string)($v['duration'] ?? '')); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ── CONTINUE WATCHING ──────────────────────────────────────────────────── -->
    <?php if ($isLoggedIn && !empty($watchHistory)): ?>
        <section class="section" style="padding-top:0">
            <div class="section-header">
                <h2 class="section-title">Continue Watching</h2>
            </div>
            <div class="slider-wrap">
                <button class="slider-btn prev" data-target="cont"><i class="fas fa-chevron-left"></i></button>
                <div class="slider-track" id="cont">
                    <?php foreach (array_slice($watchHistory, 0, 6) as $h): ?>
                        <div class="video-card continue-card" style="width:280px"
                            onclick="location.href='<?php echo $SITE_URL; ?>watch.php?id=<?php echo $h['id']; ?>'">
                            <?php if (($h['access_level'] ?? 'free') === 'premium'): ?>
                                <div class="card-badge premium-badge"><i class="fas fa-crown"></i> Premium</div>
                            <?php endif; ?>
                            <div class="card-img">
                                <img src="<?php echo htmlspecialchars(getThumbnailUrl($h['thumbnail_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?php echo htmlspecialchars($h['title']); ?>" loading="lazy">
                                <div class="play-overlay"><i class="fas fa-play"></i></div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width:<?php echo min(100, $h['watch_time']); ?>%"></div>
                                </div>
                            </div>
                            <div class="card-info">
                                <h3><?php echo htmlspecialchars($h['title']); ?></h3>
                                <div class="meta" style="margin-top:4px">
                                    <span style="font-size:12px;color:var(--muted)"><?php echo $h['watch_time']; ?>%
                                        watched</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="slider-btn next" data-target="cont"><i class="fas fa-chevron-right"></i></button>
            </div>
        </section>
    <?php endif; ?>

    <!-- ── RECOMMENDED FOR YOU ────────────────────────────────────────────────── -->
    <?php if ($isLoggedIn && !empty($recommendations)): ?>
        <section class="section" style="padding-top:0">
            <div class="section-header">
                <h2 class="section-title">Recommended For You</h2>
                <a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="video-grid">
                <?php foreach (array_slice($recommendations, 0, 8) as $v): ?>
                    <div class="video-card"
                        onclick="location.href='<?php echo $SITE_URL; ?>watch.php?id=<?php echo $v['id']; ?>'">
                        <?php if (($v['access_level'] ?? 'free') === 'premium'): ?>
                            <div class="card-badge premium-badge"><i class="fas fa-crown"></i> Premium</div>
                        <?php endif; ?>
                        <div class="card-img">
                            <img src="<?php echo htmlspecialchars(getThumbnailUrl($v['thumbnail_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($v['title']); ?>"
                                loading="lazy">
                            <div class="play-overlay"><i class="fas fa-play"></i></div>
                        </div>
                        <div class="card-info">
                            <h3><?php echo htmlspecialchars($v['title']); ?></h3>
                            <?php if (!empty($v['category_name'])): ?><div class="cat-tag"><?php echo htmlspecialchars($v['category_name']); ?></div><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- ── FOOTER ─────────────────────────────────────────────────────────────── -->
    <footer class="footer">
        <div class="footer-top">
            <div class="footer-brand">
                <div class="footer-logo">STREAM<span>VAULT</span></div>
                <p class="footer-desc">Your premium destination for movies, TV shows, and original content. Unlimited
                    entertainment.</p>
            </div>
            <div class="footer-links">
                <div class="footer-col">
                    <h4>Browse</h4>
                    <ul>
                        <li><a href="#">Home</a></li>
                        <li><a href="#">Movies</a></li>
                        <li><a href="#">TV Shows</a></li>
                        <li><a href="#">New & Hot</a></li>
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
        /* ── NAVBAR SCROLL ─────────────────────────────────────────────────────── */
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 60);
        }, { passive: true });

        /* ── HAMBURGER ─────────────────────────────────────────────────────────── */
        const ham = document.getElementById('hamburger');
        const mobileNav = document.getElementById('mobileNav');
        ham.addEventListener('click', () => {
            const open = mobileNav.classList.toggle('open');
            const spans = ham.querySelectorAll('span');
            if (open) {
                spans[0].style.transform = 'translateY(7px) rotate(45deg)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'translateY(-7px) rotate(-45deg)';
            } else {
                spans.forEach(s => { s.style.transform = ''; s.style.opacity = ''; });
            }
        });
        mobileNav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
            mobileNav.classList.remove('open');
            ham.querySelectorAll('span').forEach(s => { s.style.transform = ''; s.style.opacity = ''; });
        }));

        /* ── SEARCH OVERLAY ────────────────────────────────────────────────────── */
        const overlay = document.getElementById('searchOverlay');
        document.getElementById('searchBtn').addEventListener('click', () => {
            overlay.classList.add('open');
            setTimeout(() => document.getElementById('searchInput').focus(), 100);
        });
        document.getElementById('searchClose').addEventListener('click', () => overlay.classList.remove('open'));
        document.addEventListener('keydown', e => { if (e.key === 'Escape') overlay.classList.remove('open'); });

        /* ── HORIZONTAL SLIDERS ────────────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.slider-wrap').forEach(wrap => {
                const track = wrap.querySelector('.slider-track');
                const btnPrev = wrap.querySelector('.slider-btn.prev');
                const btnNext = wrap.querySelector('.slider-btn.next');

                if (!track) return;

                const updateButtons = () => {
                    const maxScroll = track.scrollWidth - track.clientWidth;
                    if (btnPrev) {
                        if (track.scrollLeft <= 0) {
                            btnPrev.classList.add('disabled');
                        } else {
                            btnPrev.classList.remove('disabled');
                        }
                    }
                    if (btnNext) {
                        if (track.scrollLeft >= maxScroll - 1) {
                            btnNext.classList.add('disabled');
                        } else {
                            btnNext.classList.remove('disabled');
                        }
                    }
                };

                track.addEventListener('scroll', updateButtons);
                window.addEventListener('resize', updateButtons);
                // Initial check
                setTimeout(updateButtons, 100);

                const getScrollAmount = () => {
                    const card = track.querySelector('.video-card');
                    const cardW = card ? card.offsetWidth : 200;
                    return (cardW + 16) * 3;
                };

                if (btnPrev) {
                    btnPrev.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        track.scrollBy({ left: -getScrollAmount(), behavior: 'smooth' });
                    });
                }

                if (btnNext) {
                    btnNext.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        track.scrollBy({ left: getScrollAmount(), behavior: 'smooth' });
                    });
                }
            });
        });

        /* ── LAZY IMAGE LOADING ────────────────────────────────────────────────── */
        const imgs = document.querySelectorAll('img[loading="lazy"]');
        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((entries) => {
                entries.forEach(e => { if (e.isIntersecting) { e.target.setAttribute('loading', 'eager'); io.unobserve(e.target); } });
            }, { rootMargin: '200px' });
            imgs.forEach(img => io.observe(img));
        }

        /* ── CARD REVEAL ANIMATION ─────────────────────────────────────────────── */
        const cards = document.querySelectorAll('.video-card, .category-card');
        const revealObs = new IntersectionObserver((entries) => {
            entries.forEach((e, i) => {
                if (e.isIntersecting) {
                    e.target.style.animation = `heroFadeIn .5s ease ${i * 40}ms both`;
                    revealObs.unobserve(e.target);
                }
            });
        }, { rootMargin: '-40px' });
        cards.forEach(c => { c.style.opacity = '0'; revealObs.observe(c); });
    </script>

</body>

</html>