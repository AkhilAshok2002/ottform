<?php
$pageTitle = 'My Profile';
require_once 'includes/header.php';
require_once 'includes/functions.php';

if (!$auth->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

$hasAvatarColumn = false;
try {
    $avatarColumnStmt = $db->query("SHOW COLUMNS FROM users LIKE 'avatar'");
    $hasAvatarColumn = (bool) $avatarColumnStmt->fetch();
    if (!$hasAvatarColumn) {
        $db->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL DEFAULT NULL");
        $hasAvatarColumn = true;
    }
} catch (PDOException $e) {
    $hasAvatarColumn = false;
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$stats = [];
$stmt = $db->prepare("SELECT SUM(watch_time) as total_time FROM watch_history WHERE user_id = ?");
$stmt->execute([$userId]);
$stats['total_watch_time'] = $stmt->fetch()['total_time'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(DISTINCT video_id) as videos_watched FROM watch_history WHERE user_id = ?");
$stmt->execute([$userId]);
$stats['videos_watched'] = $stmt->fetch()['videos_watched'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(*) as count FROM user_favorites WHERE user_id = ?");
$stmt->execute([$userId]);
$stats['favorites'] = $stmt->fetch()['count'];

$watchHistory = $db->prepare("SELECT v.*, wh.last_watched, wh.watch_time FROM watch_history wh JOIN videos v ON wh.video_id = v.id WHERE wh.user_id = ? ORDER BY wh.last_watched DESC LIMIT 10");
$watchHistory->execute([$userId]);
$history = $watchHistory->fetchAll();

$favorites = $db->prepare("SELECT v.*, uf.created_at as favorited_date FROM user_favorites uf JOIN videos v ON uf.video_id = v.id WHERE uf.user_id = ? ORDER BY uf.created_at DESC LIMIT 12");
$favorites->execute([$userId]);
$favItems = $favorites->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name  = $_POST['name']  ?? '';
        $email = $_POST['email'] ?? '';
        $stmt  = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            $error = 'Email already in use by another account.';
        } else {
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            if ($stmt->execute([$name, $email, $userId])) {
                $_SESSION['user_name'] = $name;
                $success = 'Profile updated successfully.';
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
            }
        }
    }
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (empty($current) || empty($new) || empty($confirm)) {
            $error = 'Please fill in all password fields.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif (!password_verify($current, $user['password'])) {
            $error = 'Current password is incorrect.';
        } else {
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([password_hash($new, PASSWORD_DEFAULT), $userId])) {
                $success = 'Password changed successfully.';
            }
        }
    }
}

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    if (!$hasAvatarColumn) {
        $error = 'Avatar upload unavailable. Please run the latest database schema update.';
    } else {
        $allowed = ['image/jpeg','image/png','image/gif'];
        $maxSize = 2 * 1024 * 1024;
        $avatarDirAbs = __DIR__ . '/assets/uploads/avatars/';
        $avatarDirRel = 'assets/uploads/avatars/';
        if (!in_array($_FILES['avatar']['type'], $allowed)) {
            $error = 'Only JPG, PNG and GIF files are allowed.';
        } elseif ($_FILES['avatar']['size'] > $maxSize) {
            $error = 'File size must be under 2 MB.';
        } else {
            if (!is_dir($avatarDirAbs)) mkdir($avatarDirAbs, 0755, true);
            $ext      = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarDirAbs . $filename)) {
                if (!empty($user['avatar'] ?? '')) {
                    $old = ltrim(str_replace(SITE_URL, '', $user['avatar']), '/');
                    if (strpos($old, 'assets/uploads/avatars/') === 0 && is_file(__DIR__ . '/' . $old)) unlink(__DIR__ . '/' . $old);
                }
                $stmt = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                if ($stmt->execute([$avatarDirRel . $filename, $userId])) {
                    $success = 'Avatar updated successfully.';
                    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                }
            } else {
                $error = 'Failed to upload image.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — StreamVault</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0 }
        :root {
            --bg:       #080808;
            --surface:  #111111;
            --surface2: #1a1a1a;
            --surface3: #222222;
            --border:   rgba(255,255,255,.07);
            --red:      #e50914;
            --red-dim:  #b8070f;
            --gold:     #f5c518;
            --text:     #f0f0f0;
            --muted:    #888;
            --font-display: 'Bebas Neue', sans-serif;
            --font-body:    'DM Sans', sans-serif;
            --card-r: 12px;
            --trans:  .3s cubic-bezier(.4,0,.2,1);
        }
        html { scroll-behavior: smooth }
        body { font-family: var(--font-body); background: var(--bg); color: var(--text); overflow-x: hidden; -webkit-font-smoothing: antialiased }
        a { text-decoration: none; color: inherit }
        img { display: block; width: 100%; object-fit: cover }
        ::-webkit-scrollbar { width: 6px; height: 6px }
        ::-webkit-scrollbar-track { background: var(--bg) }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px }

        /* ── LAYOUT ── */
        .profile-page {
            min-height: 100vh;
            padding: 100px 4vw 80px;
            max-width: 1280px;
            margin: 0 auto;
        }
        .profile-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 28px;
            align-items: start;
        }

        /* ── ALERTS ── */
        .alert {
            display: flex; align-items: center; gap: 12px;
            padding: 14px 18px; border-radius: var(--card-r);
            font-size: 14px; font-weight: 500;
            margin-bottom: 24px;
            animation: fadeUp .4s ease both;
        }
        .alert-success { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.2); color: #4ade80 }
        .alert-error   { background: rgba(229,9,20,.1);  border: 1px solid rgba(229,9,20,.2);  color: #f87171 }

        /* ── SIDEBAR ── */
        .sidebar {
            position: sticky;
            top: 90px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            animation: fadeUp .6s ease both;
        }

        /* Avatar card */
        .avatar-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px 24px 24px;
            text-align: center;
        }
        .avatar-wrap {
            position: relative;
            width: 96px;
            height: 96px;
            margin: 0 auto 16px;
        }
        .avatar-img {
            width: 96px; height: 96px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--surface2);
            transition: border-color var(--trans);
        }
        .avatar-wrap:hover .avatar-img { border-color: var(--red) }
        .avatar-upload-btn {
            position: absolute;
            bottom: 0; right: 0;
            width: 30px; height: 30px;
            border-radius: 50%;
            background: var(--red);
            border: 2px solid var(--bg);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 11px;
            cursor: pointer;
            transition: background var(--trans), transform var(--trans);
        }
        .avatar-upload-btn:hover { background: var(--red-dim); transform: scale(1.1) }

        .user-name {
            font-family: var(--font-display);
            font-size: 24px; letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .user-email { font-size: 13px; color: var(--muted); margin-bottom: 20px }

        /* Stats row */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1px;
            background: var(--border);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .stat-cell {
            background: var(--surface2);
            padding: 12px 8px;
            text-align: center;
        }
        .stat-val {
            display: block;
            font-family: var(--font-display);
            font-size: 22px;
            letter-spacing: 1px;
            color: var(--text);
        }
        .stat-lbl { font-size: 11px; color: var(--muted); font-weight: 600; letter-spacing: .5px; text-transform: uppercase }

        /* Plan pill */
        .plan-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 14px; border-radius: 20px;
            font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
        }
        .plan-pill.free     { background: rgba(136,136,136,.1); border: 1px solid rgba(136,136,136,.2); color: var(--muted) }
        .plan-pill.basic    { background: rgba(59,130,246,.1);  border: 1px solid rgba(59,130,246,.2);  color: #60a5fa }
        .plan-pill.standard { background: rgba(245,197,24,.1);  border: 1px solid rgba(245,197,24,.2);  color: var(--gold) }
        .plan-pill.premium  { background: rgba(229,9,20,.1);    border: 1px solid rgba(229,9,20,.25);   color: var(--red) }

        /* Nav menu */
        .sidebar-nav {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }
        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 14px 20px;
            font-size: 14px; font-weight: 500;
            color: var(--muted);
            cursor: pointer;
            border-left: 3px solid transparent;
            transition: all var(--trans);
            border-bottom: 1px solid var(--border);
        }
        .nav-item:last-child { border-bottom: none }
        .nav-item i { width: 16px; text-align: center; font-size: 13px }
        .nav-item:hover { color: var(--text); background: var(--surface2) }
        .nav-item.active { color: var(--text); border-left-color: var(--red); background: var(--surface2) }
        .nav-item.active i { color: var(--red) }

        /* ── CONTENT AREA ── */
        .content-area { min-width: 0 }

        .tab-pane { display: none; animation: fadeUp .4s ease both }
        .tab-pane.active { display: block }

        .tab-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 28px;
        }
        .tab-title {
            font-family: var(--font-display);
            font-size: clamp(24px, 3vw, 32px);
            letter-spacing: 2px;
            position: relative;
            padding-left: 16px;
        }
        .tab-title::before {
            content: '';
            position: absolute; left: 0; top: 10%; bottom: 10%;
            width: 3px; background: var(--red); border-radius: 2px;
        }

        /* ── CARDS / PANELS ── */
        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
        }

        /* ── FORMS ── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px }
        .form-grid .full { grid-column: 1 / -1 }
        .form-group { display: flex; flex-direction: column; gap: 8px }
        .form-label {
            font-size: 11px; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase;
            color: var(--muted);
        }
        .form-input {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 14px;
            font-family: var(--font-body); font-size: 14px; color: var(--text);
            outline: none;
            transition: border-color var(--trans), box-shadow var(--trans);
        }
        .form-input:focus { border-color: rgba(229,9,20,.5); box-shadow: 0 0 0 3px rgba(229,9,20,.1) }
        .form-input::placeholder { color: #444 }
        .form-input[readonly], .form-static {
            color: var(--muted); cursor: default;
            background: rgba(255,255,255,.02);
        }

        /* ── BUTTONS ── */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 12px 24px; border-radius: 8px;
            font-family: var(--font-body); font-size: 14px; font-weight: 700;
            cursor: pointer; border: none; transition: all var(--trans);
        }
        .btn-primary {
            background: var(--red); color: #fff;
            box-shadow: 0 4px 20px rgba(229,9,20,.25);
        }
        .btn-primary:hover { background: var(--red-dim); transform: translateY(-2px); box-shadow: 0 8px 28px rgba(229,9,20,.4) }
        .btn-ghost {
            background: transparent; color: var(--muted);
            border: 1px solid var(--border);
        }
        .btn-ghost:hover { background: var(--surface2); color: var(--text); border-color: rgba(255,255,255,.15) }

        /* ── DIVIDER ── */
        .divider { height: 1px; background: var(--border); margin: 28px 0 }

        /* ── HISTORY LIST ── */
        .history-list { display: flex; flex-direction: column; gap: 12px }
        .history-item {
            display: flex; align-items: center; gap: 16px;
            padding: 14px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: var(--card-r);
            cursor: pointer;
            transition: border-color var(--trans), background var(--trans);
        }
        .history-item:hover { border-color: rgba(255,255,255,.12); background: var(--surface3) }
        .history-thumb {
            width: 100px; height: 60px; flex-shrink: 0;
            border-radius: 8px; overflow: hidden; background: var(--surface);
        }
        .history-thumb img { width: 100%; height: 100%; object-fit: cover }
        .history-info { flex: 1; min-width: 0 }
        .history-info h3 { font-size: 14px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 6px }
        .history-meta { display: flex; align-items: center; gap: 14px }
        .history-date { font-size: 12px; color: var(--muted) }
        .progress-wrap { flex: 1; max-width: 120px }
        .progress-track { height: 3px; background: rgba(255,255,255,.1); border-radius: 2px }
        .progress-fill  { height: 100%; background: var(--red); border-radius: 2px }
        .progress-pct   { font-size: 11px; color: var(--muted); margin-top: 3px }

        /* ── VIEW ALL LINK ── */
        .view-all-link {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 13px; font-weight: 600; color: var(--muted);
            margin-top: 16px; transition: color var(--trans);
        }
        .view-all-link:hover { color: var(--red) }
        .view-all-link i { font-size: 11px; transition: transform var(--trans) }
        .view-all-link:hover i { transform: translateX(4px) }

        /* ── FAVORITES GRID ── */
        .fav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 16px;
        }
        .fav-card {
            position: relative; border-radius: var(--card-r); overflow: hidden;
            background: var(--surface2); cursor: pointer;
            transition: transform var(--trans), box-shadow var(--trans);
        }
        .fav-card:hover { transform: scale(1.04) translateY(-4px); box-shadow: 0 20px 50px rgba(0,0,0,.7); z-index: 2 }
        .fav-card .card-img { position: relative; aspect-ratio: 2/3; overflow: hidden }
        .fav-card .card-img img { height: 100%; transition: transform .5s ease }
        .fav-card:hover .card-img img { transform: scale(1.08) }
        .fav-overlay {
            position: absolute; inset: 0;
            background: rgba(0,0,0,.4);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity var(--trans);
        }
        .fav-card:hover .fav-overlay { opacity: 1 }
        .fav-overlay i { font-size: 36px; color: #fff; filter: drop-shadow(0 0 16px rgba(229,9,20,.5)) }
        .remove-fav {
            position: absolute; top: 8px; right: 8px;
            width: 26px; height: 26px; border-radius: 50%;
            background: rgba(229,9,20,.85); border: none;
            color: #fff; font-size: 10px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity var(--trans), transform var(--trans);
            z-index: 3;
        }
        .fav-card:hover .remove-fav { opacity: 1 }
        .remove-fav:hover { transform: scale(1.15) }
        .fav-info { padding: 10px 12px 12px }
        .fav-info h3 { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 5px }
        .fav-meta { display: flex; align-items: center; gap: 8px; font-size: 11px }
        .fav-rating { color: var(--gold); display: flex; align-items: center; gap: 3px }
        .fav-date   { color: var(--muted) }

        /* ── SUBSCRIPTION DETAILS ── */
        .sub-details { display: flex; flex-direction: column; gap: 0 }
        .detail-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
        }
        .detail-row:last-of-type { border-bottom: none }
        .detail-key { font-size: 13px; color: var(--muted); font-weight: 500 }
        .detail-val { font-size: 14px; font-weight: 600 }
        .status-dot {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 13px; font-weight: 700;
        }
        .status-dot.active { color: #4ade80 }
        .status-dot.active i { color: #4ade80; font-size: 8px }
        .status-dot.inactive { color: var(--muted) }

        /* ── EMPTY STATE ── */
        .empty-state {
            display: flex; flex-direction: column; align-items: center;
            gap: 16px; padding: 60px 20px; text-align: center;
        }
        .empty-icon {
            width: 64px; height: 64px; border-radius: 50%;
            background: var(--surface2); border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: var(--muted);
        }
        .empty-state p { font-size: 15px; color: var(--muted) }

        /* ── PASSWORD STRENGTH ── */
        .strength-bar { display: flex; gap: 4px; margin-top: 6px }
        .strength-seg { flex: 1; height: 3px; border-radius: 2px; background: var(--surface3); transition: background .3s }
        .strength-seg.weak   { background: #f87171 }
        .strength-seg.medium { background: var(--gold) }
        .strength-seg.strong { background: #4ade80 }
        .strength-label { font-size: 11px; color: var(--muted); margin-top: 4px }

        /* ── FOOTER ── */
        .footer { padding: 32px 4vw; border-top: 1px solid var(--border); margin-top: 60px; max-width: 1280px; margin-left: auto; margin-right: auto }
        .footer-bottom { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px }
        .footer-logo { font-family: var(--font-display); font-size: 20px; letter-spacing: 2px; color: var(--red) }
        .footer-logo span { color: var(--text) }
        .footer-copy { font-size: 13px; color: var(--muted) }

        /* ── ANIMATIONS ── */
        @keyframes fadeUp { from { opacity:0; transform:translateY(20px) } to { opacity:1; transform:translateY(0) } }

        /* ── RESPONSIVE ── */
        @media(max-width: 900px) {
            .profile-layout { grid-template-columns: 1fr }
            .sidebar { position: static }
            .form-grid { grid-template-columns: 1fr }
        }
        @media(max-width: 600px) {
            .profile-page { padding: 80px 4vw 60px }
            .fav-grid { grid-template-columns: repeat(2, 1fr) }
            .history-thumb { width: 72px; height: 44px }
        }
    </style>
</head>
<body>

<div class="profile-page">

    <!-- Alerts -->
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="profile-layout">

        <!-- ── SIDEBAR ── -->
        <aside class="sidebar">

            <!-- Avatar Card -->
            <div class="avatar-card">
                <div class="avatar-wrap">
                    <img class="avatar-img" id="avatarPreview"
                         src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : SITE_URL . '/assets/images/default-avatar.png'; ?>"
                         alt="<?php echo htmlspecialchars($user['name']); ?>">
                    <form action="" method="POST" enctype="multipart/form-data" id="avatar-form">
                        <label class="avatar-upload-btn" for="avatar-upload" title="Change photo">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="avatar-upload" name="avatar" accept="image/*" style="display:none">
                    </form>
                </div>

                <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>

                <div class="stats-row">
                    <div class="stat-cell">
                        <span class="stat-val"><?php echo $stats['videos_watched']; ?></span>
                        <span class="stat-lbl">Watched</span>
                    </div>
                    <div class="stat-cell">
                        <span class="stat-val"><?php echo $stats['favorites']; ?></span>
                        <span class="stat-lbl">Saved</span>
                    </div>
                    <div class="stat-cell">
                        <span class="stat-val"><?php echo round($stats['total_watch_time'] / 60, 1); ?>h</span>
                        <span class="stat-lbl">Hours</span>
                    </div>
                </div>

                <span class="plan-pill <?php echo strtolower(htmlspecialchars($user['subscription_status'])); ?>">
                    <i class="fas fa-crown" style="font-size:9px"></i>
                    <?php echo ucfirst(htmlspecialchars($user['subscription_status'])); ?> Plan
                </span>
            </div>

            <!-- Nav -->
            <nav class="sidebar-nav">
                <div class="nav-item active" data-tab="profile"><i class="fas fa-user"></i> Profile Info</div>
                <div class="nav-item" data-tab="security"><i class="fas fa-shield-halved"></i> Security</div>
                <div class="nav-item" data-tab="history"><i class="fas fa-clock-rotate-left"></i> Watch History</div>
                <div class="nav-item" data-tab="favorites"><i class="fas fa-heart"></i> Favorites</div>
                <div class="nav-item" data-tab="subscription"><i class="fas fa-crown"></i> Subscription</div>
            </nav>

        </aside>

        <!-- ── CONTENT ── -->
        <div class="content-area">

            <!-- Profile Tab -->
            <div class="tab-pane active" id="tab-profile">
                <div class="tab-header">
                    <h2 class="tab-title">Profile Info</h2>
                </div>
                <div class="panel">
                    <form action="" method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="name">Full Name</label>
                                <input class="form-input" type="text" id="name" name="name"
                                       value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="email">Email Address</label>
                                <input class="form-input" type="email" id="email" name="email"
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Member Since</label>
                                <input class="form-input form-static" type="text" readonly
                                       value="<?php echo date('F d, Y', strtotime($user['created_at'])); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Current Plan</label>
                                <input class="form-input form-static" type="text" readonly
                                       value="<?php echo ucfirst(htmlspecialchars($user['subscription_status'])); ?><?php echo $user['subscription_expiry'] ? '  ·  Expires ' . date('M d, Y', strtotime($user['subscription_expiry'])) : ''; ?>">
                            </div>
                        </div>
                        <div class="divider"></div>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-floppy-disk"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Security Tab -->
            <div class="tab-pane" id="tab-security">
                <div class="tab-header">
                    <h2 class="tab-title">Security</h2>
                </div>
                <div class="panel">
                    <form action="" method="POST">
                        <div class="form-grid">
                            <div class="form-group full">
                                <label class="form-label" for="current_password">Current Password</label>
                                <input class="form-input" type="password" id="current_password" name="current_password"
                                       placeholder="Enter your current password" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="new_password">New Password</label>
                                <input class="form-input" type="password" id="new_password" name="new_password"
                                       placeholder="Minimum 6 characters" required>
                                <div class="strength-bar" id="strengthBar">
                                    <div class="strength-seg" id="s1"></div>
                                    <div class="strength-seg" id="s2"></div>
                                    <div class="strength-seg" id="s3"></div>
                                    <div class="strength-seg" id="s4"></div>
                                </div>
                                <span class="strength-label" id="strengthLabel"></span>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="confirm_password">Confirm Password</label>
                                <input class="form-input" type="password" id="confirm_password" name="confirm_password"
                                       placeholder="Re-enter new password" required>
                            </div>
                        </div>
                        <div class="divider"></div>
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-lock"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- History Tab -->
            <div class="tab-pane" id="tab-history">
                <div class="tab-header">
                    <h2 class="tab-title">Watch History</h2>
                </div>
                <div class="panel">
                    <?php if (empty($history)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-clock-rotate-left"></i></div>
                        <p>No watch history yet</p>
                        <a href="<?php echo SITE_URL; ?>" class="btn btn-primary"><i class="fas fa-play"></i> Start Watching</a>
                    </div>
                    <?php else: ?>
                    <div class="history-list">
                        <?php foreach ($history as $item): $pct = min(100, (int)$item['watch_time']); ?>
                        <div class="history-item" onclick="location.href='watch.php?id=<?php echo $item['id']; ?>'">
                            <div class="history-thumb">
                                <img src="<?php echo htmlspecialchars(THUMBNAIL_URL . $item['thumbnail_path']); ?>"
                                     alt="<?php echo htmlspecialchars($item['title']); ?>">
                            </div>
                            <div class="history-info">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <div class="history-meta">
                                    <span class="history-date"><i class="far fa-clock"></i> <?php echo date('M d, Y', strtotime($item['last_watched'])); ?></span>
                                </div>
                            </div>
                            <div class="progress-wrap">
                                <div class="progress-track">
                                    <div class="progress-fill" style="width:<?php echo $pct; ?>%"></div>
                                </div>
                                <div class="progress-pct"><?php echo $pct; ?>% watched</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="history.php" class="view-all-link">View Full History <i class="fas fa-arrow-right"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Favorites Tab -->
            <div class="tab-pane" id="tab-favorites">
                <div class="tab-header">
                    <h2 class="tab-title">Favorites</h2>
                    <span style="font-size:13px;color:var(--muted)"><?php echo count($favItems); ?> saved</span>
                </div>
                <?php if (empty($favItems)): ?>
                <div class="panel">
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-heart"></i></div>
                        <p>No favorites yet</p>
                        <a href="<?php echo SITE_URL; ?>" class="btn btn-primary"><i class="fas fa-compass"></i> Browse Content</a>
                    </div>
                </div>
                <?php else: ?>
                <div class="fav-grid">
                    <?php foreach ($favItems as $item): ?>
                    <div class="fav-card" onclick="location.href='watch.php?id=<?php echo $item['id']; ?>'">
                        <div class="card-img">
                            <img src="<?php echo htmlspecialchars(THUMBNAIL_URL . $item['thumbnail_path']); ?>"
                                 alt="<?php echo htmlspecialchars($item['title']); ?>">
                            <div class="fav-overlay"><i class="fas fa-play"></i></div>
                            <button class="remove-fav" onclick="event.stopPropagation(); removeFavorite(<?php echo $item['id']; ?>)" title="Remove">
                                <i class="fas fa-xmark"></i>
                            </button>
                        </div>
                        <div class="fav-info">
                            <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                            <div class="fav-meta">
                                <span class="fav-rating"><i class="fas fa-star"></i> <?php echo htmlspecialchars((string)($item['rating'] ?? '')); ?></span>
                                <span class="fav-date"><?php echo date('M d', strtotime($item['favorited_date'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Subscription Tab -->
            <div class="tab-pane" id="tab-subscription">
                <div class="tab-header">
                    <h2 class="tab-title">Subscription</h2>
                </div>
                <div class="panel">
                    <div class="sub-details">
                        <div class="detail-row">
                            <span class="detail-key">Current Plan</span>
                            <span class="detail-val">
                                <span class="plan-pill <?php echo strtolower(htmlspecialchars($user['subscription_status'])); ?>">
                                    <i class="fas fa-crown" style="font-size:9px"></i>
                                    <?php echo ucfirst(htmlspecialchars($user['subscription_status'])); ?>
                                </span>
                            </span>
                        </div>
                        <?php if ($user['subscription_expiry']): ?>
                        <div class="detail-row">
                            <span class="detail-key">Renewal Date</span>
                            <span class="detail-val"><?php echo date('F d, Y', strtotime($user['subscription_expiry'])); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="detail-row">
                            <span class="detail-key">Status</span>
                            <span class="detail-val">
                                <?php $isActive = $user['subscription_expiry'] && strtotime($user['subscription_expiry']) > time(); ?>
                                <span class="status-dot <?php echo $isActive ? 'active' : 'inactive'; ?>">
                                    <i class="fas fa-circle"></i>
                                    <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                                </span>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-key">Member Since</span>
                            <span class="detail-val"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="divider"></div>
                    <a href="subscription.php" class="btn btn-primary"><i class="fas fa-arrow-up-right-from-square"></i> Manage Subscription</a>
                </div>
            </div>

        </div><!-- /.content-area -->
    </div><!-- /.profile-layout -->
</div><!-- /.profile-page -->

<footer class="footer">
    <div class="footer-bottom">
        <div class="footer-logo">STREAM<span>VAULT</span></div>
        <p class="footer-copy">© 2025 StreamVault. All rights reserved.</p>
    </div>
</footer>

<script>
/* ── Tab switching ── */
const navItems = document.querySelectorAll('.nav-item');
const tabPanes = document.querySelectorAll('.tab-pane');

function showTab(name) {
    navItems.forEach(n => n.classList.toggle('active', n.dataset.tab === name));
    tabPanes.forEach(p => p.classList.toggle('active', p.id === 'tab-' + name));
    history.replaceState(null, '', '#' + name);
}

navItems.forEach(item => item.addEventListener('click', () => showTab(item.dataset.tab)));

// Hash-based activation on load
const hash = location.hash.replace('#', '') || 'profile';
showTab(hash);

/* ── Avatar preview & auto-submit ── */
document.getElementById('avatar-upload').addEventListener('change', function() {
    if (!this.files || !this.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => document.getElementById('avatarPreview').src = e.target.result;
    reader.readAsDataURL(this.files[0]);
    document.getElementById('avatar-form').submit();
});

/* ── Password strength ── */
document.getElementById('new_password').addEventListener('input', function() {
    const v = this.value;
    const segs = [document.getElementById('s1'), document.getElementById('s2'),
                  document.getElementById('s3'), document.getElementById('s4')];
    const label = document.getElementById('strengthLabel');
    let score = 0;
    if (v.length >= 6)  score++;
    if (v.length >= 10) score++;
    if (/[A-Z]/.test(v) && /[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const cls = score <= 1 ? 'weak' : score <= 2 ? 'medium' : 'strong';
    const lbl = score <= 1 ? 'Weak' : score <= 2 ? 'Medium' : 'Strong';
    segs.forEach((s, i) => { s.className = 'strength-seg'; if (i < score) s.classList.add(cls); });
    label.textContent = v.length ? lbl : '';
    label.style.color = cls === 'weak' ? '#f87171' : cls === 'medium' ? 'var(--gold)' : '#4ade80';
});

/* ── Remove favorite ── */
function removeFavorite(videoId) {
    if (!confirm('Remove from favorites?')) return;
    fetch('ajax/remove-favorite.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'video_id=' + videoId
    })
    .then(r => r.json())
    .then(d => { if (d.success) location.reload(); });
}
</script>

</body>
</html>