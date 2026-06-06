<?php
$pageTitle = 'Subscription Plans';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Redirect if not logged in
if (!$auth->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

$userId = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$plans = $db->query("SELECT * FROM subscription_plans ORDER BY price")->fetchAll();

$paymentHistory = $db->prepare("
    SELECT p.*, sp.plan_name 
    FROM payments p 
    LEFT JOIN subscription_plans sp ON p.plan_id = sp.id 
    WHERE p.user_id = ? 
    ORDER BY p.payment_date DESC 
    LIMIT 10
");
$paymentHistory->execute([$userId]);
$payments = $paymentHistory->fetchAll();

if (isset($_GET['cancel'])) {
    $db->prepare("UPDATE users SET subscription_status = 'free', subscription_expiry = NULL WHERE id = ?")->execute([$userId]);
    header('Location: subscription.php?cancelled=1');
    exit();
}

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
$cancelled = isset($_GET['cancelled']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Plans — StreamVault</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ── RESET & TOKENS ── */
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
        body {
            font-family: var(--font-body);
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit }
        ::-webkit-scrollbar { width: 6px; height: 6px }
        ::-webkit-scrollbar-track { background: var(--bg) }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px }

        /* ── PAGE WRAPPER ── */
        .sub-page {
            min-height: 100vh;
            padding: 100px 4vw 80px;
            max-width: 1500px;
            margin: 0 auto;
        }

        /* ── PAGE HERO HEADER ── */
        .page-hero {
            text-align: center;
            margin-bottom: 60px;
            animation: fadeUp .7s ease both;
        }
        .page-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(229,9,20,.12);
            border: 1px solid rgba(229,9,20,.3);
            color: var(--red);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 6px 16px;
            border-radius: 30px;
            margin-bottom: 20px;
        }
        .page-hero h1 {
            font-family: var(--font-display);
            font-size: clamp(48px, 7vw, 82px);
            letter-spacing: 3px;
            line-height: .95;
            margin-bottom: 16px;
        }
        .page-hero p {
            font-size: 16px;
            color: var(--muted);
            max-width: 460px;
            margin: 0 auto;
            line-height: 1.7;
        }

        /* ── ALERTS ── */
        .alert {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            border-radius: var(--card-r);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 28px;
            animation: fadeUp .5s ease both;
        }
        .alert-success {
            background: rgba(34,197,94,.1);
            border: 1px solid rgba(34,197,94,.25);
            color: #4ade80;
        }
        .alert-error {
            background: rgba(229,9,20,.1);
            border: 1px solid rgba(229,9,20,.25);
            color: #f87171;
        }
        .alert-info {
            background: rgba(245,197,24,.08);
            border: 1px solid rgba(245,197,24,.2);
            color: var(--gold);
        }

        /* ── CURRENT SUBSCRIPTION CARD ── */
        .current-sub {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--card-r);
            padding: 24px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 56px;
            animation: fadeUp .6s ease .1s both;
        }
        .current-sub-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .current-sub-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(229,9,20,.12);
            border: 1px solid rgba(229,9,20,.25);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--red);
            font-size: 18px;
            flex-shrink: 0;
        }
        .current-sub-info h3 {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .current-sub-info span {
            font-size: 13px;
            color: var(--muted);
        }
        .sub-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .sub-status-pill.free     { background: rgba(136,136,136,.12); border: 1px solid rgba(136,136,136,.2); color: var(--muted); }
        .sub-status-pill.basic    { background: rgba(59,130,246,.12);  border: 1px solid rgba(59,130,246,.25); color: #60a5fa; }
        .sub-status-pill.standard { background: rgba(245,197,24,.1);   border: 1px solid rgba(245,197,24,.25); color: var(--gold); }
        .sub-status-pill.premium  { background: rgba(229,9,20,.12);    border: 1px solid rgba(229,9,20,.3);   color: var(--red); }
        .sub-status-pill i { font-size: 8px }

        /* ── SECTION TITLE ── */
        .section-title {
            font-family: var(--font-display);
            font-size: clamp(22px, 3vw, 30px);
            letter-spacing: 2px;
            position: relative;
            padding-left: 16px;
            margin-bottom: 28px;
        }
        .section-title::before {
            content: '';
            position: absolute;
            left: 0; top: 10%; bottom: 10%;
            width: 3px;
            background: var(--red);
            border-radius: 2px;
        }

        /* ── PLANS GRID ── */
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 72px;
        }

        .plan-card {
            position: relative;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform var(--trans), box-shadow var(--trans), border-color var(--trans);
            animation: fadeUp .6s ease both;
        }
        .plan-card:nth-child(1) { animation-delay: .1s }
        .plan-card:nth-child(2) { animation-delay: .18s }
        .plan-card:nth-child(3) { animation-delay: .26s }
        .plan-card:nth-child(4) { animation-delay: .34s }

        .plan-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 60px rgba(0,0,0,.6);
            border-color: rgba(255,255,255,.12);
        }
        .plan-card.popular {
            border-color: rgba(229,9,20,.4);
            box-shadow: 0 0 0 1px rgba(229,9,20,.15);
        }
        .plan-card.popular:hover {
            border-color: rgba(229,9,20,.6);
            box-shadow: 0 24px 60px rgba(229,9,20,.2);
        }
        .plan-card.current-plan {
            border-color: rgba(245,197,24,.35);
        }

        /* Popular ribbon */
        .popular-ribbon {
            position: absolute;
            top: 16px; right: -28px;
            background: var(--red);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 5px 36px;
            transform: rotate(45deg);
            transform-origin: center;
        }

        .plan-top {
            padding: 28px 28px 20px;
            border-bottom: 1px solid var(--border);
        }
        .plan-name-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .plan-name {
            font-family: var(--font-display);
            font-size: 28px;
            letter-spacing: 2px;
        }
        .plan-icon {
            width: 36px; height: 36px;
            border-radius: 8px;
            background: var(--surface2);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
        }
        .plan-price {
            display: flex;
            align-items: flex-end;
            gap: 3px;
            line-height: 1;
        }
        .price-currency {
            font-size: 20px;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 6px;
        }
        .price-amount {
            font-family: var(--font-display);
            font-size: 52px;
            letter-spacing: -1px;
            color: var(--text);
        }
        .price-period {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 10px;
        }

        .plan-features {
            padding: 20px 28px;
            flex: 1;
        }
        .plan-features ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .plan-features li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: rgba(255,255,255,.75);
        }
        .plan-features li .check {
            width: 18px; height: 18px;
            border-radius: 50%;
            background: rgba(34,197,94,.12);
            border: 1px solid rgba(34,197,94,.25);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            color: #4ade80;
            font-size: 9px;
        }
        .plan-features li .cross {
            width: 18px; height: 18px;
            border-radius: 50%;
            background: rgba(136,136,136,.08);
            border: 1px solid rgba(136,136,136,.15);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            color: var(--muted);
            font-size: 9px;
        }
        .plan-features li.dimmed { color: var(--muted) }

        .plan-action {
            padding: 20px 28px 28px;
        }

        /* ── BUTTONS ── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 13px 24px;
            border-radius: 8px;
            font-family: var(--font-body);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all var(--trans);
            letter-spacing: .3px;
        }
        .btn-primary {
            background: var(--red);
            color: #fff;
            box-shadow: 0 4px 20px rgba(229,9,20,.3);
        }
        .btn-primary:hover {
            background: var(--red-dim);
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(229,9,20,.45);
        }
        .btn-ghost {
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--border);
        }
        .btn-ghost:hover {
            background: var(--surface2);
            color: var(--text);
            border-color: rgba(255,255,255,.15);
        }
        .btn-current {
            background: var(--surface2);
            color: var(--gold);
            border: 1px solid rgba(245,197,24,.2);
            cursor: default;
        }
        .btn-danger {
            background: transparent;
            color: #f87171;
            border: 1px solid rgba(248,113,113,.2);
        }
        .btn-danger:hover {
            background: rgba(248,113,113,.08);
            border-color: rgba(248,113,113,.35);
        }

        /* ── PAYMENT HISTORY ── */
        .history-wrap {
            margin-bottom: 72px;
            animation: fadeUp .6s ease .2s both;
        }
        .table-scroll { overflow-x: auto; border-radius: var(--card-r) }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .history-table thead th {
            background: var(--surface);
            padding: 14px 20px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        .history-table thead th:first-child { border-radius: var(--card-r) 0 0 0 }
        .history-table thead th:last-child  { border-radius: 0 var(--card-r) 0 0 }
        .history-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background var(--trans);
        }
        .history-table tbody tr:last-child { border-bottom: none }
        .history-table tbody tr:hover { background: var(--surface) }
        .history-table td {
            padding: 14px 20px;
            color: rgba(255,255,255,.75);
        }
        .history-table td:first-child { color: var(--text); font-weight: 500 }

        .pay-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .pay-badge.completed { background: rgba(34,197,94,.1);  border: 1px solid rgba(34,197,94,.2);  color: #4ade80 }
        .pay-badge.pending   { background: rgba(245,197,24,.1); border: 1px solid rgba(245,197,24,.2); color: var(--gold) }
        .pay-badge.failed    { background: rgba(229,9,20,.1);   border: 1px solid rgba(229,9,20,.2);   color: #f87171 }

        /* ── FAQ ── */
        .faq-wrap { animation: fadeUp .6s ease .3s both }
        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }
        .faq-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--card-r);
            padding: 24px;
            transition: border-color var(--trans), background var(--trans);
        }
        .faq-card:hover {
            border-color: rgba(255,255,255,.12);
            background: var(--surface2);
        }
        .faq-card h4 {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text);
        }
        .faq-card p {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.7;
        }

        /* ── PAYMENT MODAL ── */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 3000;
            background: rgba(0,0,0,.85);
            backdrop-filter: blur(16px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            pointer-events: none;
            transition: opacity var(--trans);
        }
        .modal-backdrop.open { opacity: 1; pointer-events: all }

        .modal-box {
            background: var(--surface);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 20px;
            width: min(480px, 100%);
            padding: 36px;
            position: relative;
            transform: translateY(20px);
            transition: transform var(--trans);
            box-shadow: 0 40px 80px rgba(0,0,0,.7);
        }
        .modal-backdrop.open .modal-box { transform: translateY(0) }

        .modal-close {
            position: absolute;
            top: 20px; right: 20px;
            width: 32px; height: 32px;
            border-radius: 50%;
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--muted);
            font-size: 14px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all var(--trans);
        }
        .modal-close:hover { background: var(--red); border-color: var(--red); color: #fff }

        .modal-title {
            font-family: var(--font-display);
            font-size: 30px;
            letter-spacing: 2px;
            margin-bottom: 6px;
        }
        .modal-subtitle { font-size: 13px; color: var(--muted); margin-bottom: 28px }

        .modal-summary {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        .modal-summary-name { font-size: 14px; font-weight: 700 }
        .modal-summary-price { font-family: var(--font-display); font-size: 22px; color: var(--red); letter-spacing: 1px }

        /* form */
        .form-group { margin-bottom: 16px }
        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }
        .form-input {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 14px;
            font-family: var(--font-body);
            font-size: 14px;
            color: var(--text);
            outline: none;
            transition: border-color var(--trans), box-shadow var(--trans);
        }
        .form-input::placeholder { color: #444 }
        .form-input:focus {
            border-color: rgba(229,9,20,.5);
            box-shadow: 0 0 0 3px rgba(229,9,20,.1);
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px }

        .btn-pay {
            width: 100%;
            margin-top: 8px;
            padding: 15px;
            background: var(--red);
            color: #fff;
            font-family: var(--font-body);
            font-size: 15px;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all var(--trans);
            box-shadow: 0 4px 20px rgba(229,9,20,.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-pay:hover {
            background: var(--red-dim);
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(229,9,20,.45);
        }

        .secure-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 12px;
            color: var(--muted);
            margin-top: 14px;
        }
        .secure-note i { color: #4ade80; font-size: 11px }

        /* ── FOOTER ── */
        .footer {
            padding: 48px 4vw 32px;
            border-top: 1px solid var(--border);
            margin-top: 60px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
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
        .footer-logo {
            font-family: var(--font-display);
            font-size: 20px;
            letter-spacing: 2px;
            color: var(--red);
        }
        .footer-logo span { color: var(--text) }

        /* ── ANIMATIONS ── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px) }
            to   { opacity: 1; transform: translateY(0) }
        }

        /* ── RESPONSIVE ── */
        @media(max-width: 600px) {
            .sub-page { padding: 80px 4vw 60px }
            .plans-grid { grid-template-columns: 1fr }
            .form-row { grid-template-columns: 1fr }
            .current-sub { flex-direction: column; align-items: flex-start }
        }
    </style>
</head>
<body>

<!-- ── PAYMENT MODAL ─────────────────────────────────────────────────────── -->
<div class="modal-backdrop" id="paymentModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closePaymentModal()"><i class="fas fa-times"></i></button>
        <p class="modal-title">UPGRADE</p>
        <p class="modal-subtitle">Complete your payment to unlock premium content</p>

        <div class="modal-summary">
            <span class="modal-summary-name" id="modal_plan_name">Plan</span>
            <span class="modal-summary-price">₹<span id="modal_price">0</span><small style="font-size:14px;color:var(--muted)">/mo</small></span>
        </div>

        <form id="paymentForm" action="process-payment.php" method="POST">
            <input type="hidden" name="plan_id" id="modal_plan_id">

            <div class="form-group">
                <label class="form-label" for="card_number">Card Number</label>
                <input class="form-input" type="text" id="card_number" name="card_number" placeholder="1234  5678  9012  3456" maxlength="19" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="expiry_date">Expiry</label>
                    <input class="form-input" type="text" id="expiry_date" name="expiry_date" placeholder="MM / YY" maxlength="7" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="cvv">CVV</label>
                    <input class="form-input" type="text" id="cvv" name="cvv" placeholder="• • •" maxlength="4" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="card_name">Name on Card</label>
                <input class="form-input" type="text" id="card_name" name="card_name" placeholder="John Doe" required>
            </div>

            <button type="submit" class="btn-pay">
                <i class="fas fa-lock"></i> Pay Now
            </button>
            <p class="secure-note"><i class="fas fa-shield-halved"></i> Secured with 256-bit SSL encryption</p>
        </form>
    </div>
</div>

<!-- ── MAIN ───────────────────────────────────────────────────────────────── -->
<div class="sub-page">

    <!-- Page Hero -->
    <div class="page-hero">
        <div class="page-hero-badge"><i class="fas fa-crown"></i> &nbsp;Plans & Pricing</div>
        <h1>Choose Your<br>Plan</h1>
        <p>Unlock unlimited entertainment. Cancel anytime, no contracts.</p>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($cancelled): ?>
    <div class="alert alert-info"><i class="fas fa-circle-info"></i> Your subscription has been downgraded to Free.</div>
    <?php endif; ?>

    <!-- Current Subscription -->
    <div class="current-sub">
        <div class="current-sub-left">
            <div class="current-sub-icon"><i class="fas fa-bolt"></i></div>
            <div class="current-sub-info">
                <h3><?php echo ucfirst(htmlspecialchars($user['subscription_status'])); ?> Plan — Active</h3>
                <?php if ($user['subscription_expiry']): ?>
                <span>Renews on <?php echo date('F d, Y', strtotime($user['subscription_expiry'])); ?></span>
                <?php else: ?>
                <span>No expiry date set</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="sub-status-pill <?php echo strtolower(htmlspecialchars($user['subscription_status'])); ?>">
            <i class="fas fa-circle"></i>
            <?php echo ucfirst(htmlspecialchars($user['subscription_status'])); ?>
        </div>
    </div>

    <!-- Plans -->
    <h2 class="section-title">Available Plans</h2>
    <div class="plans-grid">
        <?php
        $planIcons   = ['free' => '🎬', 'basic' => '📺', 'standard' => '⭐', 'premium' => '👑'];
        $popularPlan = 'standard';
        foreach ($plans as $i => $plan):
            $key       = strtolower($plan['plan_name']);
            $isCurrent = strtolower($user['subscription_status']) === $key;
            $isFree    = $key === 'free';
            $isPopular = $key === $popularPlan;
        ?>
        <div class="plan-card <?php echo $isCurrent ? 'current-plan' : ''; ?> <?php echo $isPopular ? 'popular' : ''; ?>">
            <?php if ($isPopular): ?><div class="popular-ribbon">Popular</div><?php endif; ?>

            <div class="plan-top">
                <div class="plan-name-row">
                    <span class="plan-name"><?php echo strtoupper(htmlspecialchars($plan['plan_name'])); ?></span>
                    <span class="plan-icon"><?php echo $planIcons[$key] ?? '🎬'; ?></span>
                </div>
                <div class="plan-price">
                    <?php if ($plan['price'] > 0): ?>
                    <span class="price-currency">₹</span>
                    <span class="price-amount"><?php echo str_replace('.00', '', formatIndianCurrency($plan['price'])); ?></span>
                    <span class="price-period">/mo</span>
                    <?php else: ?>
                    <span class="price-amount">Free</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="plan-features">
                <ul>
                    <li>
                        <span class="check"><i class="fas fa-check"></i></span>
                        Quality: <strong style="color:var(--text);margin-left:4px"><?php echo htmlspecialchars($plan['max_quality']); ?></strong>
                    </li>
                    <li><span class="check"><i class="fas fa-check"></i></span> Watch on any device</li>
                    <li><span class="check"><i class="fas fa-check"></i></span> Unlimited streaming</li>
                    <?php if (!$isFree): ?>
                    <li><span class="check"><i class="fas fa-check"></i></span> No ads</li>
                    <li><span class="check"><i class="fas fa-check"></i></span> Download for offline</li>
                    <?php else: ?>
                    <li class="dimmed"><span class="cross"><i class="fas fa-xmark"></i></span> Contains ads</li>
                    <li class="dimmed"><span class="cross"><i class="fas fa-xmark"></i></span> No offline downloads</li>
                    <?php endif; ?>
                    <?php if ($key === 'premium'): ?>
                    <li><span class="check"><i class="fas fa-check"></i></span> 4K Ultra HD + HDR</li>
                    <li><span class="check"><i class="fas fa-check"></i></span> Dolby Atmos audio</li>
                    <li><span class="check"><i class="fas fa-check"></i></span> Up to 4 screens</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="plan-action">
                <?php if ($isCurrent): ?>
                <button class="btn btn-current" disabled><i class="fas fa-check"></i> Current Plan</button>
                <?php elseif ($isFree): ?>
                <a href="subscription.php?cancel=1" class="btn btn-danger"
                   onclick="return confirm('Switch to Free plan? Premium access will end.')">
                   <i class="fas fa-arrow-down"></i> Downgrade to Free
                </a>
                <?php else: ?>
                <button class="btn btn-primary"
                    onclick="showPaymentModal(<?php echo $plan['id']; ?>, '<?php echo htmlspecialchars($plan['plan_name'], ENT_QUOTES); ?>', <?php echo $plan['price']; ?>)">
                    <i class="fas fa-arrow-up"></i> Upgrade Now
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Payment History -->
    <?php if (!empty($payments)): ?>
    <div class="history-wrap">
        <h2 class="section-title">Payment History</h2>
        <div class="table-scroll">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Expires</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($p['payment_date'])); ?></td>
                        <td><?php echo htmlspecialchars($p['plan_name']); ?></td>
                        <td style="font-weight:700;color:var(--text)">₹<?php echo formatIndianCurrency($p['amount']); ?></td>
                        <td>
                            <span class="pay-badge <?php echo strtolower(htmlspecialchars($p['status'])); ?>">
                                <i class="fas fa-circle" style="font-size:6px"></i>
                                <?php echo ucfirst(htmlspecialchars($p['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($p['expiry_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- FAQ -->
    <div class="faq-wrap">
        <h2 class="section-title">Common Questions</h2>
        <div class="faq-grid">
            <div class="faq-card">
                <h4><i class="fas fa-rotate-left" style="color:var(--red);margin-right:8px"></i>How do I cancel?</h4>
                <p>Click "Downgrade to Free" above. Your access continues until the end of your billing period — no questions asked.</p>
            </div>
            <div class="faq-card">
                <h4><i class="fas fa-credit-card" style="color:var(--red);margin-right:8px"></i>Payment methods?</h4>
                <p>We accept all major credit cards, PayPal, and popular digital wallets. All payments are encrypted.</p>
            </div>
            <div class="faq-card">
                <h4><i class="fas fa-arrows-up-down" style="color:var(--red);margin-right:8px"></i>Can I change plans?</h4>
                <p>Yes — upgrade or downgrade anytime. Changes take effect immediately with prorated billing.</p>
            </div>
            <div class="faq-card">
                <h4><i class="fas fa-file-contract" style="color:var(--red);margin-right:8px"></i>Any contracts?</h4>
                <p>None. All plans are month-to-month. Cancel before your next billing date and you won't be charged again.</p>
            </div>
        </div>
    </div>

</div><!-- /.sub-page -->

<!-- Footer -->
<footer class="footer">
    <div class="footer-bottom">
        <div class="footer-logo">STREAM<span>VAULT</span></div>
        <p class="footer-copy">© 2025 StreamVault. All rights reserved.</p>
    </div>
</footer>

<script>
/* ── Modal ── */
function showPaymentModal(planId, planName, price) {
    document.getElementById('modal_plan_id').value   = planId;
    document.getElementById('modal_plan_name').textContent = planName + ' Plan';
    document.getElementById('modal_price').textContent     = parseFloat(price).toFixed(2);
    document.getElementById('paymentModal').classList.add('open');
}
function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('open');
}
document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) closePaymentModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closePaymentModal(); });

/* ── Card number format ── */
document.getElementById('card_number').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '').slice(0, 16);
    e.target.value = v.replace(/(.{4})/g, '$1  ').trim();
});

/* ── Expiry format ── */
document.getElementById('expiry_date').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '').slice(0, 4);
    e.target.value = v.length >= 3 ? v.slice(0,2) + ' / ' + v.slice(2) : v;
});

/* ── CVV numeric only ── */
document.getElementById('cvv').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4);
});
</script>

</body>
</html>