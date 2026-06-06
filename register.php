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
    $name             = $_POST['name']             ?? '';
    $email            = $_POST['email']            ?? '';
    $password         = $_POST['password']         ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $result = $auth->register($name, $email, $password);
        if ($result['success']) {
            $success = 'Account created! You can now sign in.';
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
  <title>Create Account — StreamVault</title>
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
      --green: #1db954;
      --font-display: 'Bebas Neue', sans-serif;
      --font-body: 'DM Sans', sans-serif;
      --trans: .3s cubic-bezier(.4,0,.2,1);
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
      padding: 24px 16px;
      -webkit-font-smoothing: antialiased;
      overflow-x: hidden;
    }
    a { text-decoration: none; color: inherit }

    /* ── BACKGROUND ── */
    .bg-layer {
      position: fixed; inset: 0; z-index: 0;
      background: url('https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=1600&h=900&fit=crop') center/cover no-repeat;
      transform: scale(1.06);
      animation: bgZoom 24s ease-in-out infinite alternate;
      filter: brightness(.3) saturate(.5);
    }
    @keyframes bgZoom {
      from { transform: scale(1.06) }
      to   { transform: scale(1.13) }
    }
    .bg-overlay {
      position: fixed; inset: 0; z-index: 1;
      background: radial-gradient(ellipse 90% 90% at 50% 50%, rgba(8,8,8,.5) 0%, rgba(8,8,8,.94) 100%);
    }
    .decor-dot {
      position: fixed; border-radius: 50%;
      pointer-events: none; z-index: 2;
      filter: blur(90px); opacity: .12;
    }
    .dot-1 { width: 450px; height: 450px; background: var(--red); top: -120px; right: -80px }
    .dot-2 { width: 300px; height: 300px; background: #1a0000; bottom: -80px; left: -60px }

    /* ── CARD ── */
    .auth-wrap {
      position: relative; z-index: 10;
      width: min(500px, 96vw);
      animation: fadeUp .7s cubic-bezier(.22,1,.36,1) both;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(36px) }
      to   { opacity: 1; transform: translateY(0) }
    }
    .auth-card {
      background: rgba(17,17,17,.93);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 44px 44px 40px;
      backdrop-filter: blur(32px);
      box-shadow: 0 32px 80px rgba(0,0,0,.7), 0 0 0 1px rgba(255,255,255,.04);
    }

    /* ── LOGO ── */
    .auth-logo {
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 28px;
    }
    .logo-mark {
      font-family: var(--font-display);
      font-size: 28px; letter-spacing: 2px;
      color: var(--red); line-height: 1;
    }
    .logo-mark span { color: var(--text) }

    /* ── HEADING ── */
    .auth-heading { text-align: center; margin-bottom: 28px }
    .auth-heading h1 {
      font-family: var(--font-display);
      font-size: 38px; letter-spacing: 3px; line-height: 1;
      margin-bottom: 8px;
    }
    .auth-heading p { font-size: 14px; color: var(--muted) }

    /* ── ALERTS ── */
    .alert {
      display: flex; align-items: center; gap: 10px;
      padding: 13px 16px; border-radius: 10px;
      font-size: 14px; margin-bottom: 22px;
      animation: fadeUp .3s ease both;
    }
    .alert-error {
      background: rgba(229,9,20,.1);
      border: 1px solid rgba(229,9,20,.3);
      color: #ff6b6b;
    }
    .alert-success {
      background: rgba(29,185,84,.1);
      border: 1px solid rgba(29,185,84,.3);
      color: #1db954;
    }
    .alert i { font-size: 15px; flex-shrink: 0 }

    /* ── FORM GRID ── */
    .form-row-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    /* ── FORM GROUPS ── */
    .form-group { margin-bottom: 18px }
    .form-label {
      display: block;
      font-size: 11px; font-weight: 700;
      letter-spacing: 1px; text-transform: uppercase;
      color: var(--muted); margin-bottom: 7px;
    }
    .input-wrap { position: relative }
    .input-icon {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      font-size: 13px; color: var(--muted);
      pointer-events: none; transition: color var(--trans);
    }
    .form-input {
      width: 100%;
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 12px 14px 12px 40px;
      font-family: var(--font-body); font-size: 14px;
      color: var(--text); outline: none;
      transition: border-color var(--trans), box-shadow var(--trans), background var(--trans);
    }
    .form-input::placeholder { color: #444 }
    .form-input:focus {
      border-color: rgba(229,9,20,.5);
      background: var(--surface);
      box-shadow: 0 0 0 3px rgba(229,9,20,.1);
    }
    .form-input.valid {
      border-color: rgba(29,185,84,.45);
      box-shadow: 0 0 0 3px rgba(29,185,84,.08);
    }
    .form-input.invalid {
      border-color: rgba(229,9,20,.5);
      box-shadow: 0 0 0 3px rgba(229,9,20,.08);
    }
    .input-wrap:focus-within .input-icon { color: var(--red) }

    /* Password toggle */
    .pw-toggle {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none;
      color: var(--muted); font-size: 13px;
      cursor: pointer; padding: 4px;
      transition: color var(--trans);
    }
    .pw-toggle:hover { color: var(--text) }

    /* Validation hint under field */
    .field-hint {
      font-size: 11px; margin-top: 5px;
      display: flex; align-items: center; gap: 5px;
      height: 14px; overflow: hidden;
      transition: color var(--trans);
    }
    .field-hint.ok   { color: var(--green) }
    .field-hint.err  { color: #ff6b6b }
    .field-hint i    { font-size: 10px }

    /* ── PASSWORD STRENGTH ── */
    .strength-bar {
      display: flex; gap: 4px; margin-top: 8px;
    }
    .strength-seg {
      flex: 1; height: 3px; border-radius: 2px;
      background: var(--border); transition: background .3s ease;
    }
    .strength-label {
      font-size: 11px; color: var(--muted);
      margin-top: 5px; height: 14px;
      transition: color var(--trans);
    }

    /* ── TERMS ── */
    .terms-row {
      display: flex; align-items: flex-start; gap: 10px;
      margin-bottom: 24px;
    }
    .terms-row input[type="checkbox"] {
      appearance: none;
      width: 16px; height: 16px; flex-shrink: 0; margin-top: 2px;
      border: 1px solid var(--border); border-radius: 4px;
      background: var(--surface2); cursor: pointer;
      transition: all var(--trans);
    }
    .terms-row input[type="checkbox"]:checked {
      background: var(--red); border-color: var(--red);
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='8' viewBox='0 0 10 8'%3E%3Cpath d='M1 4l3 3 5-6' stroke='white' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: center;
    }
    .terms-text {
      font-size: 13px; color: var(--muted); line-height: 1.5; cursor: pointer;
    }
    .terms-text a { color: var(--red); font-weight: 600 }
    .terms-text a:hover { opacity: .8 }

    /* ── SUBMIT ── */
    .btn-register {
      width: 100%; padding: 14px;
      background: var(--red); color: #fff;
      font-family: var(--font-display); font-size: 20px; letter-spacing: 2px;
      border: none; border-radius: 10px; cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 10px;
      transition: background var(--trans), transform var(--trans), box-shadow var(--trans);
      box-shadow: 0 4px 20px rgba(229,9,20,.35);
      margin-bottom: 22px;
    }
    .btn-register:hover {
      background: var(--red-dim);
      transform: translateY(-2px);
      box-shadow: 0 8px 32px rgba(229,9,20,.5);
    }
    .btn-register:active { transform: translateY(0) }
    .btn-register:disabled {
      opacity: .5; cursor: not-allowed;
      transform: none; box-shadow: none;
    }

    /* ── DIVIDER ── */
    .divider {
      display: flex; align-items: center; gap: 12px;
      margin-bottom: 18px;
    }
    .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--border) }
    .divider span { font-size: 12px; color: var(--muted); white-space: nowrap }

    /* ── FOOTER ── */
    .auth-footer { text-align: center; font-size: 14px; color: var(--muted) }
    .auth-footer a { color: var(--red); font-weight: 600; transition: opacity var(--trans) }
    .auth-footer a:hover { opacity: .8 }
    .back-home {
      display: flex; align-items: center; justify-content: center;
      gap: 6px; margin-top: 18px;
      font-size: 13px; color: var(--muted);
      transition: color var(--trans);
    }
    .back-home:hover { color: var(--text) }
    .back-home i { font-size: 11px }

    /* ── PERKS ROW ── */
    .perks {
      display: flex; justify-content: center; gap: 24px;
      margin-bottom: 28px; flex-wrap: wrap;
    }
    .perk {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--muted);
    }
    .perk i { color: var(--green); font-size: 11px }

    /* ── RESPONSIVE ── */
    @media(max-width: 520px) {
      .auth-card { padding: 32px 22px 28px }
      .form-row-2 { grid-template-columns: 1fr }
      .auth-heading h1 { font-size: 32px }
    }
  </style>
</head>
<body>

<div class="bg-layer"></div>
<div class="bg-overlay"></div>
<div class="decor-dot dot-1"></div>
<div class="decor-dot dot-2"></div>

<div class="auth-wrap">
  <div class="auth-card">

    <!-- Logo -->
    <div class="auth-logo">
      <a href="<?php echo $SITE_URL; ?>" class="logo-mark">STREAM<span>VAULT</span></a>
    </div>

    <!-- Heading -->
    <div class="auth-heading">
      <h1>CREATE ACCOUNT</h1>
      <p>Join millions of viewers — it's free to start</p>
    </div>

    <!-- Perks -->
    <div class="perks">
      <span class="perk"><i class="fas fa-check"></i> Free to join</span>
      <span class="perk"><i class="fas fa-check"></i> No credit card</span>
      <span class="perk"><i class="fas fa-check"></i> Cancel anytime</span>
    </div>

    <!-- Alerts -->
    <?php if ($error): ?>
      <div class="alert alert-error">
        <i class="fas fa-circle-exclamation"></i>
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success">
        <i class="fas fa-circle-check"></i>
        <?php echo htmlspecialchars($success); ?>
        <a href="<?php echo $SITE_URL; ?>login.php" style="margin-left:auto;color:var(--green);font-weight:700;white-space:nowrap">Sign in →</a>
      </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="" autocomplete="on" id="regForm" novalidate>

      <!-- Name + Email row -->
      <div class="form-row-2">
        <div class="form-group">
          <label class="form-label" for="name">Full Name</label>
          <div class="input-wrap">
            <input class="form-input" type="text" id="name" name="name"
                   placeholder="John Doe"
                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                   required autocomplete="name">
            <i class="fas fa-user input-icon"></i>
          </div>
          <div class="field-hint" id="nameHint"></div>
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <div class="input-wrap">
            <input class="form-input" type="email" id="email" name="email"
                   placeholder="you@example.com"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                   required autocomplete="email">
            <i class="fas fa-envelope input-icon"></i>
          </div>
          <div class="field-hint" id="emailHint"></div>
        </div>
      </div>

      <!-- Password -->
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-wrap">
          <input class="form-input" type="password" id="password" name="password"
                 placeholder="At least 6 characters"
                 required autocomplete="new-password">
          <i class="fas fa-lock input-icon"></i>
          <button type="button" class="pw-toggle" id="pwToggle1"><i class="fas fa-eye" id="pwIcon1"></i></button>
        </div>
        <!-- Strength bar -->
        <div class="strength-bar" id="strengthBar">
          <div class="strength-seg" id="seg1"></div>
          <div class="strength-seg" id="seg2"></div>
          <div class="strength-seg" id="seg3"></div>
          <div class="strength-seg" id="seg4"></div>
        </div>
        <div class="strength-label" id="strengthLabel"></div>
      </div>

      <!-- Confirm Password -->
      <div class="form-group">
        <label class="form-label" for="confirm_password">Confirm Password</label>
        <div class="input-wrap">
          <input class="form-input" type="password" id="confirm_password" name="confirm_password"
                 placeholder="Repeat your password"
                 required autocomplete="new-password">
          <i class="fas fa-lock-open input-icon"></i>
          <button type="button" class="pw-toggle" id="pwToggle2"><i class="fas fa-eye" id="pwIcon2"></i></button>
        </div>
        <div class="field-hint" id="matchHint"></div>
      </div>

      <!-- Terms -->
      <div class="terms-row">
        <input type="checkbox" id="terms" name="terms" required>
        <label for="terms" class="terms-text">
          I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>. I confirm I am 18 or older.
        </label>
      </div>

      <!-- Submit -->
      <button type="submit" class="btn-register" id="submitBtn">
        <i class="fas fa-user-plus"></i> Create Account
      </button>

    </form>
    <?php endif; ?>

    <div class="divider"><span>Already have an account?</span></div>
    <div class="auth-footer">
      <a href="<?php echo $SITE_URL; ?>login.php">Sign in to StreamVault →</a>
    </div>

  </div>

  <a href="<?php echo $SITE_URL; ?>" class="back-home">
    <i class="fas fa-chevron-left"></i> Back to home
  </a>
</div>

<script>
  /* ── PASSWORD TOGGLES ── */
  function initToggle(btnId, iconId, inputId) {
    document.getElementById(btnId)?.addEventListener('click', () => {
      const inp  = document.getElementById(inputId);
      const icon = document.getElementById(iconId);
      const show = inp.type === 'password';
      inp.type   = show ? 'text' : 'password';
      icon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
    });
  }
  initToggle('pwToggle1', 'pwIcon1', 'password');
  initToggle('pwToggle2', 'pwIcon2', 'confirm_password');

  /* ── PASSWORD STRENGTH ── */
  const segs   = [1,2,3,4].map(i => document.getElementById('seg' + i));
  const label  = document.getElementById('strengthLabel');
  const colors = ['#e50914','#ff6b35','#f5c518','#1db954'];
  const labels = ['Weak','Fair','Good','Strong'];

  function checkStrength(pw) {
    let score = 0;
    if (pw.length >= 6)  score++;
    if (pw.length >= 10) score++;
    if (/[A-Z]/.test(pw) && /[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    return Math.max(0, Math.min(4, score));
  }

  document.getElementById('password')?.addEventListener('input', function () {
    const score = checkStrength(this.value);
    segs.forEach((s, i) => {
      s.style.background = i < score ? colors[score - 1] : 'var(--border)';
    });
    label.textContent  = this.value ? labels[score - 1] || '' : '';
    label.style.color  = this.value ? colors[score - 1] : 'var(--muted)';
    checkMatch();
  });

  /* ── CONFIRM MATCH ── */
  function checkMatch() {
    const pw  = document.getElementById('password').value;
    const cpw = document.getElementById('confirm_password').value;
    const hint = document.getElementById('matchHint');
    const inp  = document.getElementById('confirm_password');
    if (!cpw) { hint.innerHTML = ''; inp.classList.remove('valid','invalid'); return; }
    if (pw === cpw) {
      hint.innerHTML = '<i class="fas fa-check"></i> Passwords match';
      hint.className = 'field-hint ok';
      inp.classList.add('valid'); inp.classList.remove('invalid');
    } else {
      hint.innerHTML = '<i class="fas fa-xmark"></i> Passwords do not match';
      hint.className = 'field-hint err';
      inp.classList.add('invalid'); inp.classList.remove('valid');
    }
  }
  document.getElementById('confirm_password')?.addEventListener('input', checkMatch);

  /* ── EMAIL VALIDATION ── */
  document.getElementById('email')?.addEventListener('blur', function () {
    const hint = document.getElementById('emailHint');
    const ok   = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value);
    if (!this.value) { hint.innerHTML = ''; this.classList.remove('valid','invalid'); return; }
    if (ok) {
      hint.innerHTML = '<i class="fas fa-check"></i> Looks good';
      hint.className = 'field-hint ok';
      this.classList.add('valid'); this.classList.remove('invalid');
    } else {
      hint.innerHTML = '<i class="fas fa-xmark"></i> Enter a valid email';
      hint.className = 'field-hint err';
      this.classList.add('invalid'); this.classList.remove('valid');
    }
  });

  /* ── NAME VALIDATION ── */
  document.getElementById('name')?.addEventListener('blur', function () {
    const hint = document.getElementById('nameHint');
    if (!this.value.trim()) { hint.innerHTML = ''; this.classList.remove('valid','invalid'); return; }
    if (this.value.trim().length >= 2) {
      hint.innerHTML = '';
      this.classList.add('valid'); this.classList.remove('invalid');
    } else {
      hint.innerHTML = '<i class="fas fa-xmark"></i> Name too short';
      hint.className = 'field-hint err';
      this.classList.add('invalid'); this.classList.remove('valid');
    }
  });

  /* ── TERMS GATE ── */
  const termsBox  = document.getElementById('terms');
  const submitBtn = document.getElementById('submitBtn');
  function updateSubmit() {
    if (submitBtn) submitBtn.disabled = !termsBox?.checked;
  }
  termsBox?.addEventListener('change', updateSubmit);
  updateSubmit();

  /* ── AUTO FOCUS ── */
  window.addEventListener('DOMContentLoaded', () => {
    const name = document.getElementById('name');
    if (name && !name.value) name.focus();
  });
</script>

</body>
</html>