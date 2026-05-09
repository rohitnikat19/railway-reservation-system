<?php
include("db.php");
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: user_login.php");
    exit();
}

if (!isset($_GET['train_id'])) {
    die("Train not selected");
}

$train_id = $_GET['train_id'];
$email = $_SESSION['user'];

/* Get user_id from email */
$user_query = "SELECT user_id FROM users WHERE email='$email'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);
$user_id = $user_data['user_id'];

/* Get selected train details */
$train_query = "SELECT * FROM trains WHERE train_id='$train_id'";
$train_result = mysqli_query($conn, $train_query);
$train = mysqli_fetch_assoc($train_result);

if (isset($_POST['book'])) {

    $passenger_name = $_POST['passenger_name'];
    $seat_count = $_POST['seat_count'];

    $total_fare = $seat_count * $train['fare'];

    $insert = "INSERT INTO bookings 
    (user_id, train_id, passenger_name, seat_count, total_fare)
    VALUES 
    ('$user_id', '$train_id', '$passenger_name', '$seat_count', '$total_fare')";

    if (mysqli_query($conn, $insert)) {
        header("Location: payment.php");
        exit();
    } else {
        $error = "Booking Failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RailBook — Book Ticket</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@500;600&family=Mulish:wght@400;500;600&display=swap" rel="stylesheet">
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
            --green-soft:  rgba(34,197,94,0.1);
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
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            height: 64px;
            background: rgba(13,27,42,0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .nav-logo {
            width: 34px;
            height: 34px;
            background: var(--accent);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-logo svg { width: 20px; height: 20px; }

        .nav-brand-name {
            font-family: 'Syne', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
        }

        .btn-back {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px;
            background: transparent;
            color: var(--text-muted);
            font-size: 13px;
            font-family: 'Mulish', sans-serif;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }

        .btn-back:hover { background: rgba(255,255,255,0.06); color: var(--text); }

        /* ── Main ── */
        .main {
            max-width: 680px;
            margin: 0 auto;
            padding: 2.5rem 2rem 4rem;
            animation: fadeUp 0.4s ease both;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        .breadcrumb a { color: var(--text-muted); text-decoration: none; }
        .breadcrumb a:hover { color: var(--accent); }
        .breadcrumb span { color: var(--text); }

        /* ── Train summary card ── */
        .train-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.25rem;
        }

        .train-card-left { flex: 1; min-width: 200px; }

        .train-name {
            font-family: 'Syne', sans-serif;
            font-size: 18px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }

        .route {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .route-city { font-weight: 600; color: var(--text); }

        .route-line {
            flex: 1;
            max-width: 80px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .route-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent);
            flex-shrink: 0;
        }

        .route-dash {
            flex: 1;
            height: 1px;
            border-top: 1.5px dashed rgba(240,165,0,0.35);
        }

        .train-times {
            display: flex;
            gap: 1.5rem;
            margin-top: 12px;
        }

        .time-item { font-size: 12px; }
        .time-label { color: var(--text-muted); margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.05em; font-size: 10px; font-weight: 600; }
        .time-val { font-weight: 600; font-size: 13px; }

        .train-card-right { text-align: right; flex-shrink: 0; }

        .fare-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.07em; color: var(--text-muted); font-weight: 600; margin-bottom: 4px; }

        .fare-amount {
            font-family: 'Syne', sans-serif;
            font-size: 28px;
            font-weight: 600;
            color: var(--accent);
        }

        .fare-unit { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        .seats-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--green-soft);
            color: var(--green);
            border-radius: 20px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }

        /* ── Booking form card ── */
        .form-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
        }

        .form-title {
            font-family: 'Syne', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-title svg { color: var(--accent); }

        /* ── Error ── */
        .error-msg {
            background: rgba(220,60,60,0.1);
            border: 1px solid rgba(220,60,60,0.25);
            color: #f28b8b;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── Fields ── */
        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .field-group { margin-bottom: 1.25rem; }

        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
            margin-bottom: 7px;
        }

        .field-input {
            width: 100%;
            padding: 11px 14px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: 14px;
            font-family: 'Mulish', sans-serif;
            outline: none;
            transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
        }

        .field-input::placeholder { color: rgba(143,163,184,0.45); }
        .field-input:hover { border-color: var(--border-hover); }

        .field-input:focus {
            border-color: var(--accent);
            background: rgba(240,165,0,0.05);
            box-shadow: 0 0 0 3px rgba(240,165,0,0.1);
        }

        /* hide number spinners */
        .field-input[type=number]::-webkit-inner-spin-button,
        .field-input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; }
        .field-input[type=number] { -moz-appearance: textfield; }

        /* ── Fare summary ── */
        .fare-summary {
            background: rgba(240,165,0,0.06);
            border: 1px solid rgba(240,165,0,0.15);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .fare-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: var(--text-muted);
            padding: 4px 0;
        }

        .fare-row.total {
            border-top: 1px solid rgba(240,165,0,0.2);
            margin-top: 8px;
            padding-top: 12px;
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
        }

        .fare-row.total .val { color: var(--accent); font-family: 'Syne', sans-serif; font-size: 17px; }

        /* ── Submit ── */
        .btn-book {
            width: 100%;
            padding: 13px;
            background: var(--accent);
            border: none;
            border-radius: 10px;
            color: var(--navy);
            font-size: 14px;
            font-weight: 700;
            font-family: 'Mulish', sans-serif;
            letter-spacing: 0.02em;
            cursor: pointer;
            transition: opacity 0.15s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-book:hover  { opacity: 0.88; }
        .btn-book:active { transform: scale(0.98); }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 520px) {
            .field-row { grid-template-columns: 1fr; }
            .train-card { flex-direction: column; }
            .train-card-right { text-align: left; }
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
            <line x1="19" y1="12" x2="5" y2="12"/>
            <polyline points="12 19 5 12 12 5"/>
        </svg>
        Back to Trains
    </a>
</nav>

<!-- Main -->
<main class="main">

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="dashboard.php">Trains</a>
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
        <span>Book Ticket</span>
    </div>

    <!-- Train Summary -->
    <div class="train-card">
        <div class="train-card-left">
            <div class="train-name"><?php echo htmlspecialchars($train['train_name']); ?></div>
            <div class="route">
                <span class="route-city"><?php echo htmlspecialchars($train['source_station']); ?></span>
                <div class="route-line">
                    <div class="route-dot"></div>
                    <div class="route-dash"></div>
                    <div class="route-dot"></div>
                </div>
                <span class="route-city"><?php echo htmlspecialchars($train['destination_station']); ?></span>
            </div>
            <div class="train-times">
                <div class="time-item">
                    <div class="time-label">Departure</div>
                    <div class="time-val"><?php echo htmlspecialchars($train['departure_time']); ?></div>
                </div>
                <div class="time-item">
                    <div class="time-label">Arrival</div>
                    <div class="time-val"><?php echo htmlspecialchars($train['arrival_time']); ?></div>
                </div>
            </div>
        </div>
        <div class="train-card-right">
            <div class="fare-label">Fare per seat</div>
            <div class="fare-amount">₹<?php echo number_format($train['fare']); ?></div>
            <div class="fare-unit">per passenger</div>
            <div class="seats-badge">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                <?php echo htmlspecialchars($train['available_seats']); ?> seats available
            </div>
        </div>
    </div>

    <!-- Booking Form -->
    <div class="form-card">

        <div class="form-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>
            </svg>
            Passenger Details
        </div>

        <?php if (isset($error)): ?>
        <div class="error-msg">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="booking-form">

            <div class="field-row">
                <div class="field-group">
                    <label class="field-label" for="passenger_name">Passenger Name</label>
                    <input class="field-input" type="text" id="passenger_name" name="passenger_name"
                           placeholder="Full name" required>
                </div>
                <div class="field-group">
                    <label class="field-label" for="seat_count">Number of Seats</label>
                    <input class="field-input" type="number" id="seat_count" name="seat_count"
                           placeholder="1" min="1"
                           max="<?php echo (int)$train['available_seats']; ?>"
                           value="1" required>
                </div>
            </div>

            <!-- Live fare summary -->
            <div class="fare-summary" id="fare-summary">
                <div class="fare-row">
                    <span>Fare per seat</span>
                    <span>₹<?php echo number_format($train['fare']); ?></span>
                </div>
                <div class="fare-row">
                    <span>Seats</span>
                    <span id="summary-seats">1</span>
                </div>
                <div class="fare-row total">
                    <span>Total Fare</span>
                    <span class="val" id="summary-total">₹<?php echo number_format($train['fare']); ?></span>
                </div>
            </div>

            <button type="submit" name="book" class="btn-book">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                Confirm Booking & Proceed to Payment
            </button>

        </form>
    </div>

</main>

<script>
    const farePerSeat = <?php echo (int)$train['fare']; ?>;
    const seatInput   = document.getElementById('seat_count');
    const summarySeats = document.getElementById('summary-seats');
    const summaryTotal = document.getElementById('summary-total');

    function updateFare() {
        const count = Math.max(1, parseInt(seatInput.value) || 1);
        const total = count * farePerSeat;
        summarySeats.textContent = count;
        summaryTotal.textContent = '₹' + total.toLocaleString('en-IN');
    }

    seatInput.addEventListener('input', updateFare);
    updateFare();
</script>

</body>
</html>