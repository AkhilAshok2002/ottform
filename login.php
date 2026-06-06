<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if ($auth->isLoggedIn()) {
    header('Location: ' . SITE_URL);
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $result = $auth->login($email, $password);
        if ($result['success']) {
            header('Location: ' . SITE_URL);
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

$SITE_URL = rtrim(SITE_URL, '/') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — StreamVault</title>
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
      --card-r: 14px; --trans: .3s cubic-bezier(.4,0,.2,1);
    }
    html { scroll-behavior: smooth }
    body {
      font-family: var(--font-body);
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      -webkit-font-smoothing: antialiased;
    }
    a { text-decoration: none; color: inherit }

    /* ── CINEMATIC BACKGROUND ── */
    .bg-layer {
      position: fixed; inset: 0; z-index: 0;
      background: url('https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=1600&h=900&fit=crop') center/cover no-repeat;
      transform: scale(1.06);
      animation: bgZoom 20s ease-in-out infinite alternate;
      filter: brightness(.35) saturate(.6);
    }
    @keyframes bgZoom {
      from { transform: scale(1.06) }
      to   { transform: scale(1.12) }
    }
    .bg-overlay {
      position: fixed; inset: 0; z-index: 1;
      background:
        radial-gradient(ellipse 80% 80% at 50% 50%, rgba(8,8,8,.55) 0%, rgba(8,8,8,.92) 100%),
        linear-gradient(135deg, rgba(229,9,20,.06) 0%, transparent 60%);
    }

    /* ── CARD ── */
    .auth-wrap {
      position: relative; z-index: 10;
      width: min(460px, 92vw);
      animation: fadeUp .7s cubic-bezier(.22,1,.36,1) both;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(32px) }
      to   { opacity: 1; transform: translateY(0) }
    }

    .auth-card {
      background: rgba(17,17,17,.92);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 48px 44px 44px;
      backdrop-filter: blur(32px);
      box-shadow: 0 32px 80px rgba(0,0,0,.7), 0 0 0 1px rgba(255,255,255,.04);
    }

    /* ── LOGO ── */
    .auth-logo {
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 36px;
    }
    .logo-mark {
      font-family: var(--font-display);
      font-size: 30px;
      letter-spacing: 2px;
      color: var(--red);
      line-height: 1;
    }
    .logo-mark span { color: var(--text) }

    /* ── HEADING ── */
    .auth-heading { text-align: center; margin-bottom: 32px }
    .auth-heading h1 {
      font-family: var(--font-display);
      font-size: 40px;
      letter-spacing: 3px;
      line-height: 1;
      margin-bottom: 8px;
    }
    .auth-heading p { font-size: 14px; color: var(--muted) }

    /* ── ERROR ALERT ── */
    .alert {
      display: flex; align-items: center; gap: 10px;
      padding: 13px 16px;
      border-radius: 10px;
      font-size: 14px;
      margin-bottom: 24px;
      animation: fadeUp .3s ease both;
    }
    .alert-error {
      background: rgba(229,9,20,.1);
      border: 1px solid rgba(229,9,20,.3);
      color: #ff6b6b;
    }
    .alert i { font-size: 15px; flex-shrink: 0 }

    /* ── FORM ── */
    .form-group { margin-bottom: 20px }
    .form-label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 8px;
    }

    .input-wrap {
      position: relative;
    }
    .input-icon {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      font-size: 14px; color: var(--muted);
      pointer-events: none; transition: color var(--trans);
    }
    .form-input {
      width: 100%;
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 13px 14px 13px 42px;
      font-family: var(--font-body);
      font-size: 15px;
      color: var(--text);
      outline: none;
      transition: border-color var(--trans), box-shadow var(--trans), background var(--trans);
    }
    .form-input::placeholder { color: #444 }
    .form-input:focus {
      border-color: rgba(229,9,20,.5);
      background: var(--surface);
      box-shadow: 0 0 0 3px rgba(229,9,20,.1);
    }
    .form-input:focus + .input-icon,
    .input-wrap:focus-within .input-icon { color: var(--red) }

    /* Password toggle */
    .pw-toggle {
      position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
      background: none; border: none;
      color: var(--muted); font-size: 14px;
      cursor: pointer; padding: 4px;
      transition: color var(--trans);
    }
    .pw-toggle:hover { color: var(--text) }

    /* Remember + Forgot row */
    .form-row {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 28px; gap: 12px;
    }
    .checkbox-label {
      display: flex; align-items: center; gap: 8px;
      font-size: 13px; color: var(--muted); cursor: pointer;
      user-select: none;
    }
    .checkbox-label input[type="checkbox"] {
      appearance: none;
      width: 16px; height: 16px;
      border: 1px solid var(--border); border-radius: 4px;
      background: var(--surface2);
      cursor: pointer; flex-shrink: 0;
      transition: all var(--trans);
    }
    .checkbox-label input[type="checkbox"]:checked {
      background: var(--red); border-color: var(--red);
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='8' viewBox='0 0 10 8'%3E%3Cpath d='M1 4l3 3 5-6' stroke='white' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: center;
    }
    .forgot-link {
      font-size: 13px; color: var(--muted);
      transition: color var(--trans);
    }
    .forgot-link:hover { color: var(--red) }

    /* Submit button */
    .btn-signin {
      width: 100%;
      padding: 14px;
      background: var(--red);
      color: #fff;
      font-family: var(--font-display);
      font-size: 20px;
      letter-spacing: 2px;
      border: none; border-radius: 10px;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 10px;
      transition: background var(--trans), transform var(--trans), box-shadow var(--trans);
      box-shadow: 0 4px 20px rgba(229,9,20,.35);
      margin-bottom: 24px;
    }
    .btn-signin:hover {
      background: var(--red-dim);
      transform: translateY(-2px);
      box-shadow: 0 8px 32px rgba(229,9,20,.5);
    }
    .btn-signin:active { transform: translateY(0) }

    /* Divider */
    .divider {
      display: flex; align-items: center; gap: 12px;
      margin-bottom: 20px;
    }
    .divider::before, .divider::after {
      content: ''; flex: 1; height: 1px; background: var(--border);
    }
    .divider span { font-size: 12px; color: var(--muted); white-space: nowrap }

    /* Register link */
    .auth-footer {
      text-align: center;
      font-size: 14px;
      color: var(--muted);
    }
    .auth-footer a {
      color: var(--red); font-weight: 600;
      transition: opacity var(--trans);
    }
    .auth-footer a:hover { opacity: .8 }

    /* ── BACK TO HOME ── */
    .back-home {
      display: flex; align-items: center; justify-content: center;
      gap: 6px; margin-top: 20px;
      font-size: 13px; color: var(--muted);
      transition: color var(--trans);
    }
    .back-home:hover { color: var(--text) }
    .back-home i { font-size: 11px }

    /* ── DECORATIVE DOTS ── */
    .decor-dot {
      position: fixed; border-radius: 50%;
      pointer-events: none; z-index: 2;
      filter: blur(80px); opacity: .15;
    }
    .dot-1 { width: 400px; height: 400px; background: var(--red); top: -100px; left: -100px }
    .dot-2 { width: 300px; height: 300px; background: #4a0000; bottom: -80px; right: -80px }

    /* ── RESPONSIVE ── */
    @media(max-width: 500px) {
      .auth-card { padding: 36px 28px 32px }
      .auth-heading h1 { font-size: 34px }
    }
  </style>
</head>
<body>

<!-- Background -->
<div class="bg-layer"></div>
<div class="bg-overlay"></div>
<div class="decor-dot dot-1"></div>
<div class="decor-dot dot-2"></div>

<!-- Auth Card -->
<div class="auth-wrap">
  <div class="auth-card">

    <!-- Logo -->
    <div class="auth-logo">
      <a href="<?php echo $SITE_URL; ?>" class="logo-mark">STREAM<span>VAULT</span></a>
    </div>

    <!-- Heading -->
    <div class="auth-heading">
      <h1>SIGN IN</h1>
      <p>Welcome back — your watchlist is waiting</p>
    </div>

    <!-- Error -->
    <?php if ($error): ?>
      <div class="alert alert-error">
        <i class="fas fa-circle-exclamation"></i>
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="" autocomplete="on">

      <!-- Email -->
      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <div class="input-wrap">
          <input
            class="form-input"
            type="email" id="email" name="email"
            placeholder="you@example.com"
            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
            required autocomplete="email">
          <i class="fas fa-envelope input-icon"></i>
        </div>
      </div>

      <!-- Password -->
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-wrap">
          <input
            class="form-input"
            type="password" id="password" name="password"
            placeholder="••••••••"
            required autocomplete="current-password">
          <i class="fas fa-lock input-icon"></i>
          <button type="button" class="pw-toggle" id="pwToggle" aria-label="Toggle password visibility">
            <i class="fas fa-eye" id="pwIcon"></i>
          </button>
        </div>
      </div>

      <!-- Remember + Forgot -->
      <div class="form-row">
        <label class="checkbox-label">
          <input type="checkbox" name="remember" id="remember">
          Remember me
        </label>
        <a href="<?php echo $SITE_URL; ?>forgot-password.php" class="forgot-link">Forgot password?</a>
      </div>

      <!-- Submit -->
      <button type="submit" class="btn-signin">
        <i class="fas fa-play"></i> Watch Now
      </button>

    </form>

    <div class="divider"><span>New to StreamVault?</span></div>

    <div class="auth-footer">
      <p>Don't have an account? <a href="<?php echo $SITE_URL; ?>register.php">Create one free</a></p>
    </div>

  </div>

  <a href="<?php echo $SITE_URL; ?>" class="back-home">
    <i class="fas fa-chevron-left"></i> Back to home
  </a>
</div>

<script>
  /* ── PASSWORD TOGGLE ── */
  document.getElementById('pwToggle').addEventListener('click', function () {
    const input = document.getElementById('password');
    const icon  = document.getElementById('pwIcon');
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    icon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
  });

  /* ── AUTO-focus first empty field ── */
  window.addEventListener('DOMContentLoaded', () => {
    const email = document.getElementById('email');
    const pw    = document.getElementById('password');
    if (!email.value) email.focus();
    else pw.focus();
  });
</script>

</body>
</html>