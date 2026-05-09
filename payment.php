<?php
include("db.php");
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: user_login.php");
    exit();
}

/* ================================================================
   RAZORPAY KEYS — Replace with your actual keys from:
   https://dashboard.razorpay.com/app/keys
   ================================================================ */
define('RAZORPAY_KEY_ID',     'rzp_test_XXXXXXXXXXXXXXXX');   // e.g. rzp_test_abcdef1234
define('RAZORPAY_KEY_SECRET', 'XXXXXXXXXXXXXXXXXXXXXXXX');    // Secret from dashboard

/* ── Get latest booking ── */
$query   = "SELECT * FROM bookings ORDER BY booking_id DESC LIMIT 1";
$result  = mysqli_query($conn, $query);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    die("No booking found");
}

/* ── Amount in paise (Razorpay needs smallest currency unit) ── */
$amount_paise = (int)($booking['total_fare'] * 100);

/* ── Create a Razorpay Order (only once per booking) ── */
$rzp_order_id = null;
$rzp_error    = null;

if (
    !isset($_SESSION['rzp_order_id']) ||
    $_SESSION['rzp_booking_id'] != $booking['booking_id']
) {
    $order_payload = json_encode([
        'amount'          => $amount_paise,
        'currency'        => 'INR',
        'receipt'         => 'booking_' . $booking['booking_id'],
        'payment_capture' => 1
    ]);

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $order_payload,
        CURLOPT_USERPWD        => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $order = json_decode($response, true);

    if ($http_code === 200 && isset($order['id'])) {
        $rzp_order_id = $order['id'];
        $_SESSION['rzp_order_id']   = $rzp_order_id;
        $_SESSION['rzp_booking_id'] = $booking['booking_id'];
    } else {
        $rzp_error = "Could not initiate Razorpay order. Please try again.";
    }
} else {
    $rzp_order_id = $_SESSION['rzp_order_id'];
}

/* ── Check if already paid this session ── */
$paid        = false;
$success_txn = null;
if (
    isset($_SESSION['payment_success']) &&
    $_SESSION['payment_success'] == $booking['booking_id']
) {
    $paid        = true;
    $success_txn = $_SESSION['payment_txn_id'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RailBook — Payment</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@500;600&family=Mulish:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Razorpay Checkout SDK -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:        #0d1b2a;
            --accent:      #f0a500;
            --accent-soft: rgba(240,165,0,0.12);
            --text:        #e8edf2;
            --text-muted:  #8fa3b8;
            --border:      rgba(255,255,255,0.07);
            --border-hover:rgba(255,255,255,0.14);
            --card-bg:     rgba(255,255,255,0.04);
            --green:       #22c55e;
            --green-soft:  rgba(34,197,94,0.10);
            --blue:        #60a5fa;
            --blue-soft:   rgba(96,165,250,0.10);
            --red:         #f87171;
            --red-soft:    rgba(248,113,113,0.10);
        }

        body {
            font-family: 'Mulish', sans-serif;
            background: var(--navy);
            color: var(--text);
            min-height: 100vh;
            background-image:
                radial-gradient(ellipse 70% 50% at 10% -5%,  rgba(240,165,0,0.06) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 90% 105%, rgba(26,80,130,0.22) 0%, transparent 60%);
        }

        /* ── Navbar ── */
        .navbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 2rem; height: 64px;
            background: rgba(13,27,42,0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100;
        }

        .nav-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }

        .nav-logo {
            width: 34px; height: 34px; background: var(--accent);
            border-radius: 8px; display: flex; align-items: center; justify-content: center;
        }
        .nav-logo svg { width: 20px; height: 20px; }

        .nav-brand-name {
            font-family: 'Syne', sans-serif;
            font-size: 16px; font-weight: 600; color: var(--text);
        }

        .btn-back {
            display: flex; align-items: center; gap: 6px;
            padding: 7px 16px;
            border: 1px solid rgba(255,255,255,0.12); border-radius: 8px;
            background: transparent; color: var(--text-muted);
            font-size: 13px; font-family: 'Mulish', sans-serif; font-weight: 500;
            text-decoration: none; transition: background 0.15s, color 0.15s;
        }
        .btn-back:hover { background: rgba(255,255,255,0.06); color: var(--text); }

        /* ── Steps ── */
        .steps-bar {
            display: flex; align-items: center; justify-content: center;
            padding: 1.25rem 2rem;
            border-bottom: 1px solid var(--border);
            background: rgba(13,27,42,0.5);
        }

        .step { display: flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 600; color: var(--text-muted); }
        .step.done   { color: var(--green); }
        .step.active { color: var(--accent); }

        .step-num {
            width: 22px; height: 22px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700;
            background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
        }
        .step.done .step-num   { background: var(--green-soft); border-color: var(--green);  color: var(--green); }
        .step.active .step-num { background: var(--accent-soft); border-color: var(--accent); color: var(--accent); }

        .step-connector { width: 48px; height: 1px; background: rgba(255,255,255,0.1); margin: 0 6px; }

        /* ── Main ── */
        .main {
            max-width: 580px; margin: 0 auto;
            padding: 2.5rem 2rem 4rem;
            animation: fadeUp 0.4s ease both;
        }

        /* ── Summary card ── */
        .summary-card {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: 16px; overflow: hidden; margin-bottom: 1.25rem;
        }

        .summary-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
            font-family: 'Syne', sans-serif;
            font-size: 13px; font-weight: 600; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.07em;
        }

        .summary-body { padding: 1.25rem; }

        .summary-row {
            display: flex; justify-content: space-between;
            font-size: 13.5px; padding: 7px 0;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .summary-row:last-child { border-bottom: none; }
        .summary-row .lbl { color: var(--text-muted); }
        .summary-row .val { font-weight: 600; color: var(--text); }
        .summary-row.total { margin-top: 8px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.08); border-bottom: none; font-size: 15px; }
        .summary-row.total .val { font-family: 'Syne', sans-serif; font-size: 20px; color: var(--accent); }

        /* ── Pay card ── */
        .pay-card {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: 16px; padding: 1.75rem;
        }

        .pay-title {
            font-family: 'Syne', sans-serif;
            font-size: 15px; font-weight: 600; color: var(--text);
            margin-bottom: 0.4rem;
            display: flex; align-items: center; gap: 8px;
        }
        .pay-title svg { color: var(--accent); }

        .pay-subtitle { font-size: 12.5px; color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.6; }

        /* ── Payment method badges ── */
        .payment-logos {
            display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
            margin-bottom: 1.5rem;
            padding: 0.85rem 1rem;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border); border-radius: 10px;
        }

        .payment-logos .label { font-size: 11px; font-weight: 600; color: var(--text-muted); margin-right: 4px; }

        .pay-badge {
            padding: 3px 9px; border-radius: 6px;
            font-size: 11px; font-weight: 700; letter-spacing: 0.02em;
        }
        .badge-upi    { background: rgba(99,185,90,0.15);  color: #63b95a; border: 1px solid rgba(99,185,90,0.25); }
        .badge-card   { background: var(--blue-soft);       color: var(--blue); border: 1px solid rgba(96,165,250,0.25); }
        .badge-wallet { background: rgba(167,139,250,0.15); color: #a78bfa; border: 1px solid rgba(167,139,250,0.25); }
        .badge-nb     { background: var(--accent-soft);     color: var(--accent); border: 1px solid rgba(240,165,0,0.25); }
        .badge-emi    { background: var(--red-soft);        color: var(--red); border: 1px solid rgba(248,113,113,0.25); }

        /* ── Error ── */
        .error-msg {
            background: var(--red-soft); border: 1px solid rgba(248,113,113,0.3); color: var(--red);
            border-radius: 10px; padding: 11px 14px; font-size: 13px;
            margin-bottom: 1.25rem; display: flex; align-items: center; gap: 8px;
        }

        /* ── Razorpay button ── */
        .btn-razorpay {
            width: 100%; padding: 14px;
            background: #2d6cf4; border: none; border-radius: 12px; color: #fff;
            font-size: 15px; font-weight: 700;
            font-family: 'Mulish', sans-serif; letter-spacing: 0.02em;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: background 0.15s, transform 0.1s;
            box-shadow: 0 4px 20px rgba(45,108,244,0.3);
        }
        .btn-razorpay:hover  { background: #1f57d8; }
        .btn-razorpay:active { transform: scale(0.98); }
        .btn-razorpay:disabled {
            background: rgba(255,255,255,0.08); color: var(--text-muted);
            cursor: not-allowed; box-shadow: none;
        }

        /* ── Spinner ── */
        .spinner {
            width: 18px; height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }

        /* ── Secure strip ── */
        .secure-strip {
            display: flex; align-items: center; justify-content: center; gap: 1.5rem;
            flex-wrap: wrap; margin-top: 14px;
        }
        .secure-item { display: flex; align-items: center; gap: 5px; font-size: 11px; color: var(--text-muted); }

        /* ── Razorpay branding ── */
        .rzp-branding {
            display: flex; align-items: center; justify-content: center; gap: 6px;
            margin-top: 12px;
            font-size: 11px; color: rgba(143,163,184,0.5);
        }

        .rzp-branding svg { opacity: 0.5; }

        /* ── Success card ── */
        .success-card {
            background: var(--green-soft); border: 1px solid rgba(34,197,94,0.25);
            border-radius: 18px; padding: 2.5rem 2rem; text-align: center;
        }

        .success-icon {
            width: 64px; height: 64px; background: rgba(34,197,94,0.15);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem;
            animation: popIn 0.4s cubic-bezier(0.34,1.56,0.64,1) both;
        }
        .success-icon svg { width: 32px; height: 32px; color: var(--green); }

        .success-title {
            font-family: 'Syne', sans-serif;
            font-size: 22px; font-weight: 600; color: var(--green); margin-bottom: 6px;
        }
        .success-sub { font-size: 13px; color: var(--text-muted); margin-bottom: 1.5rem; }

        .txn-pill {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2);
            border-radius: 100px; padding: 6px 16px;
            font-size: 12px; font-weight: 600; color: var(--green);
            font-family: monospace; margin-bottom: 1.75rem; word-break: break-all;
        }

        .receipt-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 8px; text-align: left;
            background: rgba(0,0,0,0.15); border-radius: 12px;
            padding: 1rem; margin-bottom: 1.5rem;
        }
        .receipt-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); font-weight: 600; margin-bottom: 3px; }
        .receipt-val   { font-size: 14px; font-weight: 600; color: var(--text); }
        .receipt-val.accent { color: var(--accent); font-family: 'Syne', sans-serif; }

        .btn-dashboard {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 28px; background: var(--green); border: none; border-radius: 10px;
            color: #0d2a1a; font-size: 14px; font-weight: 700;
            font-family: 'Mulish', sans-serif; text-decoration: none;
            transition: opacity 0.15s, transform 0.1s;
        }
        .btn-dashboard:hover  { opacity: 0.88; }
        .btn-dashboard:active { transform: scale(0.98); }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        @keyframes popIn  { from { opacity: 0; transform: scale(0.6); }       to { opacity: 1; transform: scale(1); } }
        @keyframes spin   { to { transform: rotate(360deg); } }

        @media (max-width: 480px) {
            .step-connector { width: 24px; }
            .receipt-grid   { grid-template-columns: 1fr; }
            .secure-strip   { gap: 0.75rem; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <a class="nav-brand" href="dashboard.php">
        <div class="nav-logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="#0d1b2a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="7" width="20" height="12" rx="2"/>
                <path d="M6 7V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2"/>
                <line x1="6" y1="17" x2="6" y2="19"/>
                <line x1="18" y1="17" x2="18" y2="19"/>
                <line x1="9" y1="12" x2="15" y2="12"/>
            </svg>
        </div>
        <span class="nav-brand-name">RailBook</span>
    </a>
    <a href="dashboard.php" class="btn-back">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>
        Dashboard
    </a>
</nav>

<!-- Steps -->
<div class="steps-bar">
    <div class="step done">
        <div class="step-num"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></div>
        Select Train
    </div>
    <div class="step-connector"></div>
    <div class="step done">
        <div class="step-num"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></div>
        Book Details
    </div>
    <div class="step-connector"></div>
    <div class="step <?php echo $paid ? 'done' : 'active'; ?>">
        <div class="step-num">
            <?php if ($paid): ?><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg><?php else: ?>3<?php endif; ?>
        </div>
        Payment
    </div>
</div>

<main class="main">

<?php if ($paid): ?>
<!-- ── SUCCESS ── -->
<div class="success-card">
    <div class="success-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div class="success-title">Payment Successful!</div>
    <p class="success-sub">Your ticket is confirmed. Have a safe journey 🚆</p>

    <div class="txn-pill">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
        <?php echo htmlspecialchars($success_txn); ?>
    </div>

    <div class="receipt-grid">
        <div>
            <div class="receipt-label">Passenger</div>
            <div class="receipt-val"><?php echo htmlspecialchars($booking['passenger_name']); ?></div>
        </div>
        <div>
            <div class="receipt-label">Seats</div>
            <div class="receipt-val"><?php echo htmlspecialchars($booking['seat_count']); ?></div>
        </div>
        <div>
            <div class="receipt-label">Booking ID</div>
            <div class="receipt-val">#<?php echo htmlspecialchars($booking['booking_id']); ?></div>
        </div>
        <div>
            <div class="receipt-label">Amount Paid</div>
            <div class="receipt-val accent">₹<?php echo number_format($booking['total_fare']); ?></div>
        </div>
    </div>

    <a href="dashboard.php" class="btn-dashboard">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        Back to Dashboard
    </a>
</div>

<?php else: ?>
<!-- ── PAYMENT FORM ── -->

<div class="summary-card">
    <div class="summary-header">Booking Summary</div>
    <div class="summary-body">
        <div class="summary-row">
            <span class="lbl">Passenger</span>
            <span class="val"><?php echo htmlspecialchars($booking['passenger_name']); ?></span>
        </div>
        <div class="summary-row">
            <span class="lbl">Seats</span>
            <span class="val"><?php echo htmlspecialchars($booking['seat_count']); ?></span>
        </div>
        <div class="summary-row">
            <span class="lbl">Booking ID</span>
            <span class="val">#<?php echo htmlspecialchars($booking['booking_id']); ?></span>
        </div>
        <div class="summary-row total">
            <span class="lbl">Total Fare</span>
            <span class="val">₹<?php echo number_format($booking['total_fare']); ?></span>
        </div>
    </div>
</div>

<div class="pay-card">
    <div class="pay-title">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
        </svg>
        Complete Payment
    </div>
    <p class="pay-subtitle">Powered by Razorpay. All payment methods supported — no extra charges.</p>

    <div class="payment-logos">
        <span class="label">Accepted:</span>
        <span class="pay-badge badge-upi">UPI</span>
        <span class="pay-badge badge-card">Cards</span>
        <span class="pay-badge badge-wallet">Wallets</span>
        <span class="pay-badge badge-nb">Net Banking</span>
        <span class="pay-badge badge-emi">EMI</span>
    </div>

    <?php if ($rzp_error): ?>
    <div class="error-msg">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?php echo htmlspecialchars($rzp_error); ?>
    </div>
    <?php endif; ?>

    <button
        id="rzp-btn"
        class="btn-razorpay"
        onclick="openRazorpay()"
        <?php echo $rzp_error ? 'disabled' : ''; ?>>
        <div class="spinner" id="btn-spinner"></div>
        <svg id="btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
        </svg>
        Pay ₹<?php echo number_format($booking['total_fare']); ?> via Razorpay
    </button>

    <div class="secure-strip">
        <div class="secure-item">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2" stroke-linecap="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            256-bit SSL Encrypted
        </div>
        <div class="secure-item">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2" stroke-linecap="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            PCI-DSS Compliant
        </div>
        <div class="secure-item">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            Instant Confirmation
        </div>
    </div>

    <div class="rzp-branding">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
        Secured by Razorpay
    </div>
</div>

<?php endif; ?>
</main>

<?php if (!$paid && !$rzp_error): ?>
<!-- Hidden form — submits Razorpay signature to verify_payment.php -->
<form id="rzp-verify-form" method="POST" action="verify_payment.php" style="display:none;">
    <input type="hidden" name="razorpay_payment_id" id="f_payment_id">
    <input type="hidden" name="razorpay_order_id"   id="f_order_id">
    <input type="hidden" name="razorpay_signature"  id="f_signature">
    <input type="hidden" name="booking_id" value="<?php echo (int)$booking['booking_id']; ?>">
    <input type="hidden" name="payment_method" id="f_method" value="Razorpay">
</form>

<script>
function openRazorpay() {
    const btn     = document.getElementById('rzp-btn');
    const spinner = document.getElementById('btn-spinner');
    const icon    = document.getElementById('btn-icon');

    btn.disabled          = true;
    spinner.style.display = 'block';
    icon.style.display    = 'none';

    var options = {
        key:         '<?php echo RAZORPAY_KEY_ID; ?>',
        amount:      <?php echo $amount_paise; ?>,
        currency:    'INR',
        name:        'RailBook',
        description: 'Train Ticket — Booking #<?php echo (int)$booking['booking_id']; ?>',
        order_id:    '<?php echo htmlspecialchars($rzp_order_id); ?>',
        prefill: {
            name:    '<?php echo addslashes(htmlspecialchars($booking['passenger_name'])); ?>',
            email:   '<?php echo addslashes(htmlspecialchars($_SESSION['user'])); ?>',
            contact: ''
        },
        notes: {
            booking_id: '<?php echo (int)$booking['booking_id']; ?>'
        },
        theme: {
            color: '#f0a500'
        },
        handler: function(response) {
            document.getElementById('f_payment_id').value = response.razorpay_payment_id;
            document.getElementById('f_order_id').value   = response.razorpay_order_id;
            document.getElementById('f_signature').value  = response.razorpay_signature;
            document.getElementById('rzp-verify-form').submit();
        },
        modal: {
            ondismiss: function() {
                btn.disabled          = false;
                spinner.style.display = 'none';
                icon.style.display    = 'block';
            }
        }
    };

    var rzp = new Razorpay(options);

    rzp.on('payment.failed', function(response) {
        btn.disabled          = false;
        spinner.style.display = 'none';
        icon.style.display    = 'block';
        alert('Payment failed: ' + response.error.description);
    });

    rzp.open();
}
</script>
<?php endif; ?>

</body>
</html>