<?php 
require_once 'includes/config.php';
require_once 'includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : ''; ?><?php echo defined('SITE_NAME') ? SITE_NAME : 'StreamVault'; ?>
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* ══════════════════════════════════════════════════════════════════════════
     STREAMVAULT — Global Header Styles
     Tokens and all header-scoped CSS live here so every page inherits them.
  ══════════════════════════════════════════════════════════════════════════ */

        /* ── DESIGN TOKENS ──────────────────────────────────────────────────────── */
        :root {
            --bg: #080808;
            --surface: #111111;
            --surface2: #1a1a1a;
            --surface3: #222222;
            --border: rgba(255, 255, 255, .07);
            --red: #e50914;
            --red-dim: #b8070f;
            --red-glow: rgba(229, 9, 20, .35);
            --gold: #f5c518;
            --text: #f0f0f0;
            --muted: #888;
            --font-display: 'Bebas Neue', sans-serif;
            --font-body: 'DM Sans', sans-serif;
            --card-r: 12px;
            --trans: .3s cubic-bezier(.4, 0, .2, 1);
            --nav-h: 70px;
        }

        /* ── RESET ──────────────────────────────────────────────────────────────── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        html {
            scroll-behavior: smooth
        }

        body {
            font-family: var(--font-body);
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
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

        /* ── SEARCH OVERLAY ─────────────────────────────────────────────────────── */
        .sv-search-overlay {
            position: fixed;
            inset: 0;
            z-index: 3000;
            background: rgba(0, 0, 0, .96);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: clamp(80px, 15vh, 140px);
            opacity: 0;
            pointer-events: none;
            transition: opacity var(--trans);
        }

        .sv-search-overlay.open {
            opacity: 1;
            pointer-events: all
        }

        .sv-search-form {
            display: flex;
            align-items: center;
            gap: 16px;
            width: min(700px, 90vw);
            border-bottom: 2px solid var(--red);
            padding-bottom: 14px;
        }

        .sv-search-form i {
            font-size: 22px;
            color: var(--muted);
            flex-shrink: 0
        }

        .sv-search-form input {
            flex: 1;
            background: none;
            border: none;
            outline: none;
            font-family: var(--font-body);
            font-size: clamp(20px, 3vw, 30px);
            color: var(--text);
        }

        .sv-search-form input::placeholder {
            color: var(--muted)
        }

        .sv-search-close {
            background: none;
            border: none;
            color: var(--muted);
            font-size: 24px;
            cursor: pointer;
            flex-shrink: 0;
            transition: color var(--trans);
            padding: 4px;
        }

        .sv-search-close:hover {
            color: var(--text)
        }

        .sv-search-hint {
            margin-top: 20px;
            font-size: 13px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .sv-search-hint kbd {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 2px 7px;
            font-size: 12px;
            font-family: monospace;
            color: var(--muted);
        }

        /* live results panel */
        #searchResults {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: min(700px, 90vw);
            margin-top: clamp(80px, 15vh, 140px);
            margin-top: calc(clamp(80px, 15vh, 140px) + 60px);
            z-index: 3001;
            max-height: 400px;
            overflow-y: auto;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--card-r);
            display: none;
        }

        #searchResults.active {
            display: block
        }

        .search-result-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: background var(--trans);
        }

        .search-result-item:last-child {
            border-bottom: none
        }

        .search-result-item:hover {
            background: var(--surface2)
        }

        .search-result-item img {
            width: 40px;
            height: 56px;
            border-radius: 6px;
            object-fit: cover;
            flex-shrink: 0
        }

        .search-result-item .sri-info h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px
        }

        .search-result-item .sri-info span {
            font-size: 12px;
            color: var(--muted)
        }

        /* ── MOBILE NAV DRAWER ──────────────────────────────────────────────────── */
        .sv-mobile-nav {
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: rgba(8, 8, 8, .97);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transform: translateX(100%);
            transition: transform .42s cubic-bezier(.77, 0, .18, 1);
        }

        .sv-mobile-nav.open {
            transform: translateX(0)
        }

        .sv-mobile-nav a {
            font-family: var(--font-display);
            font-size: clamp(28px, 6vw, 44px);
            letter-spacing: 3px;
            color: var(--muted);
            padding: 8px 20px;
            transition: color var(--trans), letter-spacing var(--trans);
        }

        .sv-mobile-nav a:hover,
        .sv-mobile-nav a.active {
            color: var(--text);
            letter-spacing: 4px
        }

        .sv-mobile-divider {
            width: 40px;
            height: 1px;
            background: var(--border);
            margin: 12px 0
        }

        .sv-mobile-auth {
            display: flex;
            gap: 12px;
            margin-top: 8px
        }

        .sv-mobile-auth a {
            font-family: var(--font-body) !important;
            font-size: 14px !important;
            letter-spacing: .5px !important;
            padding: 10px 28px !important;
            border-radius: 8px;
        }

        .sv-mobile-auth .btn-outline {
            border: 1px solid var(--border);
            color: var(--muted) !important
        }

        .sv-mobile-auth .btn-primary {
            background: var(--red);
            color: #fff !important
        }

        /* ── NAVBAR ─────────────────────────────────────────────────────────────── */
        .sv-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 999;
            height: var(--nav-h);
            display: flex;
            align-items: center;
            padding: 0 4vw;
            gap: 32px;
            background: linear-gradient(to bottom, rgba(8, 8, 8, .98), rgba(8, 8, 8, 0));
            transition: background var(--trans), box-shadow var(--trans);
        }

        .sv-navbar.scrolled {
            background: rgba(8, 8, 8, .97) !important;
            box-shadow: 0 1px 0 var(--border);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        /* Logo */
        .sv-logo {
            font-family: var(--font-display);
            font-size: 26px;
            letter-spacing: 2px;
            color: var(--red);
            user-select: none;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .sv-logo span {
            color: var(--text)
        }

        .sv-logo:hover {
            opacity: .9
        }

        /* Nav links */
        .sv-nav-links {
            display: flex;
         
    align-items: center;
    justify-content: center;
            gap: 28px;
            list-style: none;
            flex: 1;
        }

        .sv-nav-links a {
            font-size: 14px;
            font-weight: 500;
            color: var(--muted);

            padding: 6px 12px;
            border-radius: 6px;
            transition: color var(--trans), background var(--trans);
            white-space: nowrap;
        }

        .sv-nav-links a:hover {
            color: var(--text);
            background: rgba(255, 255, 255, .05)
        }

        .sv-nav-links a.active {
            color: var(--text)
        }



        @keyframes underlineIn {
            from {
                transform: scaleX(0)
            }

            to {
                transform: scaleX(1)
            }
        }

        /* Nav right cluster */
        .sv-nav-right {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
            flex-shrink: 0
        }

        /* Icon button shared */
        .sv-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: none;
            border: none;
            color: var(--muted);
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color var(--trans), background var(--trans);
        }

        .sv-icon-btn:hover {
            color: var(--text);
            background: rgba(255, 255, 255, .07)
        }

        /* Auth buttons */
        .sv-btn-login {
            font-family: var(--font-body);
            font-size: 13px;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 7px;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--muted);
            cursor: pointer;
            transition: all var(--trans);
            white-space: nowrap;
        }

        .sv-btn-login:hover {
            border-color: rgba(255, 255, 255, .25);
            color: var(--text)
        }

        .sv-btn-signup {
            font-family: var(--font-body);
            font-size: 13px;
            font-weight: 700;
            padding: 8px 20px;
            border-radius: 7px;
            background: var(--red);
            border: none;
            color: #fff;
            cursor: pointer;
            transition: background var(--trans), transform var(--trans), box-shadow var(--trans);
            white-space: nowrap;
        }

        .sv-btn-signup:hover {
            background: var(--red-dim);
            transform: translateY(-1px);
            box-shadow: 0 4px 16px var(--red-glow)
        }

        /* User dropdown */
        .sv-user-menu {
            position: relative
        }

        .sv-user-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 30px;
            padding: 6px 14px 6px 6px;
            cursor: pointer;
            transition: background var(--trans), border-color var(--trans);
        }

        .sv-user-btn:hover {
            background: var(--surface2);
            border-color: rgba(255, 255, 255, .15)
        }

        .sv-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--red);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .sv-user-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap
        }

        .sv-user-chevron {
            font-size: 10px;
            color: var(--muted);
            transition: transform var(--trans)
        }

        .sv-user-menu.open .sv-user-chevron {
            transform: rotate(180deg)
        }

        .sv-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            min-width: 200px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .7);
            opacity: 0;
            pointer-events: none;
            transform: translateY(-8px) scale(.97);
            transform-origin: top right;
            transition: opacity var(--trans), transform var(--trans);
        }

        .sv-user-menu.open .sv-dropdown {
            opacity: 1;
            pointer-events: all;
            transform: translateY(0) scale(1)
        }

        .sv-dropdown-header {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 12px;
            color: var(--muted);
        }

        .sv-dropdown-header strong {
            display: block;
            font-size: 14px;
            color: var(--text);
            margin-bottom: 2px
        }

        .sv-dropdown a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 16px;
            font-size: 14px;
            color: var(--muted);
            transition: background var(--trans), color var(--trans);
        }

        .sv-dropdown a i {
            width: 16px;
            text-align: center;
            font-size: 13px
        }

        .sv-dropdown a:hover {
            background: var(--surface2);
            color: var(--text)
        }

        .sv-dropdown a.danger {
            color: #ef4444
        }

        .sv-dropdown a.danger:hover {
            background: rgba(239, 68, 68, .1);
            color: #ef4444
        }

        .sv-dropdown-divider {
            height: 1px;
            background: var(--border);
            margin: 4px 0
        }

        /* Subscription badge */
        .sub-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
            background: linear-gradient(135deg, #f5c518, #e09000);
            color: #000;
            padding: 2px 7px;
            border-radius: 30px;
            margin-left: auto;
        }

        /* ── NOTIFICATION DOT ───────────────────────────────────────────────────── */
        .sv-notif {
            position: relative
        }

        .sv-notif-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--red);
            border: 2px solid var(--bg);
        }

        /* ── HAMBURGER ──────────────────────────────────────────────────────────── */
        .sv-hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 6px;
            border-radius: 6px;
            background: none;
            border: none;
        }

        .sv-hamburger span {
            width: 22px;
            height: 2px;
            background: var(--text);
            border-radius: 2px;
            transition: transform .35s ease, opacity .35s ease;
            display: block;
        }

        .sv-hamburger.open span:nth-child(1) {
            transform: translateY(7px) rotate(45deg)
        }

        .sv-hamburger.open span:nth-child(2) {
            opacity: 0;
            transform: scaleX(0)
        }

        .sv-hamburger.open span:nth-child(3) {
            transform: translateY(-7px) rotate(-45deg)
        }

        /* ── MAIN CONTENT OFFSET ────────────────────────────────────────────────── */
        .main-content {
            padding-top: var(--nav-h)
        }

        /* ── RESPONSIVE ─────────────────────────────────────────────────────────── */
        @media(max-width:960px) {
            .sv-nav-links {
                display: none
            }

            .sv-btn-login,
            .sv-btn-signup {
                display: none
            }

            .sv-hamburger {
                display: flex
            }
        }

        @media(max-width:480px) {
            .sv-navbar {
                padding: 0 5vw
            }

            .sv-logo {
                font-size: 22px
            }
        }
    </style>
</head>

<body>

    <!-- ══════════════════════════════════════════════════════════════════════════
     SEARCH OVERLAY
════════════════════════════════════════════════════════════════════════════ -->
    <div class="sv-search-overlay" id="svSearchOverlay" role="dialog" aria-label="Search">
        <form class="sv-search-form" id="svSearchForm" autocomplete="off"
            action="<?php echo rtrim(SITE_URL, '/'); ?>/search.php" method="get">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="text" id="searchInput" name="q" placeholder="Search movies, series, genres…" aria-label="Search">
            <button type="button" class="sv-search-close" id="svSearchClose" aria-label="Close search"><i
                    class="fas fa-times"></i></button>
        </form>
        <div class="sv-search-hint">
            <kbd>Esc</kbd> to close &nbsp;·&nbsp; <kbd>Enter</kbd> to search
        </div>
        <div id="searchResults"></div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════════
     MOBILE NAV DRAWER
════════════════════════════════════════════════════════════════════════════ -->
    <nav class="sv-mobile-nav" id="svMobileNav" aria-label="Mobile navigation">
        <?php
        $currentPage = basename($_SERVER['PHP_SELF']);
        $navItems = [
            ['href' => defined('SITE_URL') ? SITE_URL : '/', 'label' => 'Home', 'file' => 'index.php'],
            ['href' => (defined('SITE_URL') ? SITE_URL : '') . '/movies.php', 'label' => 'Movies', 'file' => 'movies.php'],
            ['href' => (defined('SITE_URL') ? SITE_URL : '') . '/series.php', 'label' => 'Series', 'file' => 'series.php'],
        ];
        if (isset($auth) && $auth->isLoggedIn()):
            $navItems[] = ['href' => (defined('SITE_URL') ? SITE_URL : '') . '/mylist.php', 'label' => 'My List', 'file' => 'mylist.php'];
            $navItems[] = ['href' => (defined('SITE_URL') ? SITE_URL : '') . '/history.php', 'label' => 'History', 'file' => 'history.php'];
        endif;
        foreach ($navItems as $item):
            $active = ($currentPage === $item['file']) ? 'active' : '';
            ?>
            <a href="<?php echo $item['href']; ?>" class="<?php echo $active; ?>"><?php echo $item['label']; ?></a>
        <?php endforeach; ?>
        <div class="sv-mobile-divider"></div>
        <?php if (isset($auth) && $auth->isLoggedIn()): ?>
            <div class="sv-mobile-auth">
                <a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/profile.php" class="btn-outline">Profile</a>
                <a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/logout.php" class="btn-primary">Log Out</a>
            </div>
        <?php else: ?>
            <div class="sv-mobile-auth">
                <a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/login.php" class="btn-outline">Login</a>
                <a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/register.php" class="btn-primary">Sign Up</a>
            </div>
        <?php endif; ?>
    </nav>

    <!-- ══════════════════════════════════════════════════════════════════════════
     STICKY NAVBAR
════════════════════════════════════════════════════════════════════════════ -->
    <header class="sv-navbar" id="svNavbar" role="banner">

        <!-- Logo -->
        <a href="<?php echo defined('SITE_URL') ? SITE_URL : '/'; ?>" class="sv-logo">
            STREAM<span>VAULT</span>
        </a>

        <!-- Centre nav links -->
        <ul class="sv-nav-links" role="list">
            <?php foreach ($navItems as $item):
                $active = ($currentPage === $item['file']) ? 'active' : '';
                ?>
                <li><a href="<?php echo $item['href']; ?>" class="<?php echo $active; ?>"><?php echo $item['label']; ?></a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Right cluster -->
        <div class="sv-nav-right">

            <!-- Search trigger -->
            <button class="sv-icon-btn" id="svSearchBtn" aria-label="Open search">
                <i class="fas fa-search"></i>
            </button>

            <?php if (isset($auth) && $auth->isLoggedIn()): ?>

                <!-- Notifications -->
                <div class="sv-notif">
                    <button class="sv-icon-btn" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                    </button>
                    <span class="sv-notif-dot" aria-hidden="true"></span>
                </div>

                <!-- User dropdown -->
                <div class="sv-user-menu" id="svUserMenu">
                    <button class="sv-user-btn" id="svUserBtn" aria-expanded="false" aria-haspopup="true">
                        <div class="sv-avatar">
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                        </div>
                        <span class="sv-user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span>
                        <i class="fas fa-chevron-down sv-user-chevron"></i>
                    </button>

                    <div class="sv-dropdown" role="menu">
                        <div class="sv-dropdown-header">
                            <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></strong>
                            <?php echo $_SESSION['user_email'] ?? ''; ?>
                        </div>

                        <a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/profile.php" role="menuitem">
                            <i class="fas fa-user"></i> Profile
                        </a>
                        <a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/mylist.php" role="menuitem">
                            <i class="fas fa-bookmark"></i> My List
                        </a>
                        <a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/history.php" role="menuitem">
                            <i class="fas fa-history"></i> Watch History
                        </a>

                        <?php if (isset($auth) && $auth->isAdmin()): ?>
                            <div class="sv-dropdown-divider"></div>
                            <a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/admin/dashboard.php" role="menuitem">
                                <i class="fas fa-cog"></i> Admin Panel
                            </a>
                        <?php endif; ?>

                        <div class="sv-dropdown-divider"></div>
                        <a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/subscription.php" role="menuitem">
                            <i class="fas fa-crown"></i> Subscription
                            <span class="sub-badge"><i class="fas fa-crown"></i> PRO</span>
                        </a>

                        <div class="sv-dropdown-divider"></div>
                        <a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/logout.php" class="danger"
                            role="menuitem">
                            <i class="fas fa-sign-out-alt"></i> Sign Out
                        </a>
                    </div>
                </div>

            <?php else: ?>

                <a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/login.php">
                    <button class="sv-btn-login">Login</button>
                </a>
                <a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/register.php">
                    <button class="sv-btn-signup">Sign Up</button>
                </a>

            <?php endif; ?>

            <!-- Hamburger (mobile) -->
            <button class="sv-hamburger" id="svHamburger" aria-label="Toggle menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>

        </div>
    </header>

    <!-- ══════════════════════════════════════════════════════════════════════════
     MAIN CONTENT BEGINS
════════════════════════════════════════════════════════════════════════════ -->
    <main class="main-content">

        <script>
            (function () {
                /* ── NAVBAR SCROLL ────────────────────────────────────────────────────── */
                const navbar = document.getElementById('svNavbar');
                const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 50);
                window.addEventListener('scroll', onScroll, { passive: true });
                onScroll();

                /* ── HAMBURGER / MOBILE DRAWER ────────────────────────────────────────── */
                const ham = document.getElementById('svHamburger');
                const mobileNav = document.getElementById('svMobileNav');
                ham.addEventListener('click', () => {
                    const isOpen = ham.classList.toggle('open');
                    mobileNav.classList.toggle('open', isOpen);
                    ham.setAttribute('aria-expanded', isOpen);
                    document.body.style.overflow = isOpen ? 'hidden' : '';
                });
                mobileNav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
                    ham.classList.remove('open');
                    mobileNav.classList.remove('open');
                    document.body.style.overflow = '';
                }));

                /* ── USER DROPDOWN ────────────────────────────────────────────────────── */
                const userMenu = document.getElementById('svUserMenu');
                const userBtn = document.getElementById('svUserBtn');
                if (userMenu && userBtn) {
                    userBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const isOpen = userMenu.classList.toggle('open');
                        userBtn.setAttribute('aria-expanded', isOpen);
                    });
                    document.addEventListener('click', () => {
                        userMenu.classList.remove('open');
                        userBtn?.setAttribute('aria-expanded', 'false');
                    });
                    userMenu.addEventListener('click', e => e.stopPropagation());
                }

                /* ── SEARCH OVERLAY ───────────────────────────────────────────────────── */
                const overlay = document.getElementById('svSearchOverlay');
                const searchBtn = document.getElementById('svSearchBtn');
                const closeBtn = document.getElementById('svSearchClose');
                const searchForm = document.getElementById('svSearchForm');
                const input = document.getElementById('searchInput');
                const results = document.getElementById('searchResults');
                const siteBaseUrl = '<?php echo rtrim(SITE_URL, '/'); ?>';

                const buildUrl = (path) => {
                    const normalizedPath = path.startsWith('/') ? path : `/${path}`;
                    return `${siteBaseUrl}${normalizedPath}`;
                };

                const escapeHtml = (value) => String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

                const getThumbnailUrl = (thumbnailPath) => {
                    const cleanPath = String(thumbnailPath ?? '').trim();
                    if (cleanPath === '') {
                        return '';
                    }
                    if (/^https?:\/\//i.test(cleanPath)) {
                        return cleanPath;
                    }
                    if (cleanPath.startsWith('assets/')) {
                        return buildUrl(`/${cleanPath}`);
                    }
                    return buildUrl(`/assets/uploads/thumbnails/${cleanPath}`);
                };

                const goToSearchPage = (queryValue) => {
                    const q = String(queryValue ?? input?.value ?? '').trim();
                    if (q === '') {
                        input?.focus();
                        return;
                    }
                    window.location.href = `${buildUrl('/search.php')}?q=${encodeURIComponent(q)}`;
                };

                const renderResults = (items, queryValue) => {
                    if (!results) {
                        return;
                    }

                    if (!Array.isArray(items) || items.length === 0) {
                        results.innerHTML = '<div class="search-result-item"><div class="sri-info"><h4>No results found</h4><span>Press Enter to search full catalog</span></div></div>';
                        results.classList.add('active');
                        return;
                    }

                    const topItems = items.slice(0, 8);
                    let html = '';

                    topItems.forEach((item) => {
                        const itemId = Number(item.id || 0);
                        const targetUrl = `${buildUrl('/watch.php')}?id=${itemId}`;
                        const thumbUrl = getThumbnailUrl(item.thumbnail_path);
                        const title = escapeHtml(item.title || 'Untitled');
                        const metaParts = [item.category_name, item.release_year].filter(Boolean).map(escapeHtml);

                        html += `
                            <div class="search-result-item" data-url="${escapeHtml(targetUrl)}">
                                ${thumbUrl ? `<img src="${escapeHtml(thumbUrl)}" alt="${title}" loading="lazy">` : ''}
                                <div class="sri-info">
                                    <h4>${title}</h4>
                                    <span>${metaParts.join(' • ')}</span>
                                </div>
                            </div>
                        `;
                    });

                    html += `
                        <div class="search-result-item" data-search-query="${escapeHtml(queryValue)}">
                            <div class="sri-info">
                                <h4>View all results for “${escapeHtml(queryValue)}”</h4>
                                <span>Open full search page</span>
                            </div>
                        </div>
                    `;

                    results.innerHTML = html;
                    results.classList.add('active');

                    results.querySelectorAll('.search-result-item[data-url]').forEach((itemNode) => {
                        itemNode.addEventListener('click', () => {
                            window.location.href = itemNode.getAttribute('data-url');
                        });
                    });

                    results.querySelectorAll('.search-result-item[data-search-query]').forEach((itemNode) => {
                        itemNode.addEventListener('click', () => {
                            goToSearchPage(itemNode.getAttribute('data-search-query'));
                        });
                    });
                };

                function openSearch() {
                    overlay.classList.add('open');
                    document.body.style.overflow = 'hidden';
                    setTimeout(() => input?.focus(), 80);
                }
                function closeSearch() {
                    overlay.classList.remove('open');
                    document.body.style.overflow = '';
                    if (results) {
                        results.classList.remove('active');
                        results.innerHTML = '';
                    }
                }

                searchBtn?.addEventListener('click', openSearch);
                closeBtn?.addEventListener('click', closeSearch);
                searchForm?.addEventListener('submit', (event) => {
                    event.preventDefault();
                    goToSearchPage();
                });
                document.addEventListener('keydown', e => {
                    if (e.key === 'Escape') closeSearch();
                    if ((e.ctrlKey || e.metaKey) && e.key === 'k') { e.preventDefault(); openSearch(); }
                });
                overlay?.addEventListener('click', e => { if (e.target === overlay) closeSearch(); });

                /* Live search suggestions (debounced AJAX) */
                let debounceTimer;
                let activeRequest;
                input?.addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    const q = input.value.trim();
                    if (!results) return;

                    if (activeRequest) {
                        activeRequest.abort();
                        activeRequest = null;
                    }

                    if (q.length < 2) {
                        results.classList.remove('active');
                        results.innerHTML = '';
                        return;
                    }

                    debounceTimer = setTimeout(() => {
                        activeRequest = new AbortController();
                        fetch(`${buildUrl('/ajax/search.php')}?q=${encodeURIComponent(q)}`, {
                            signal: activeRequest.signal,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Search request failed');
                                }
                                return response.json();
                            })
                            .then(data => {
                                renderResults(data, q);
                            })
                            .catch(error => {
                                if (error.name === 'AbortError') {
                                    return;
                                }
                                results.innerHTML = '<div class="search-result-item"><div class="sri-info"><h4>Search is temporarily unavailable</h4><span>Please try again</span></div></div>';
                                results.classList.add('active');
                            })
                            .finally(() => {
                                activeRequest = null;
                            });
                    }, 280);
                });

                document.addEventListener('click', (event) => {
                    if (!overlay?.classList.contains('open')) {
                        return;
                    }
                    const clickedInsideForm = searchForm?.contains(event.target);
                    const clickedInsideResults = results?.contains(event.target);
                    const clickedSearchButton = searchBtn?.contains(event.target);

                    if (!clickedInsideForm && !clickedInsideResults && !clickedSearchButton) {
                        closeSearch();
                    }
                });
            })();
            
        </script>