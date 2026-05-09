<?php
include("db.php");
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: user_login.php");
    exit();
}

$email = $_SESSION['user'];

$query = "SELECT * FROM trains";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Railway Reservation — Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@500;600&family=Mulish:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy: #0d1b2a;
            --navy-mid: #1a2e42;
            --navy-light: #243b55;
            --accent: #f0a500;
            --accent-soft: rgba(240,165,0,0.12);
            --text: #e8edf2;
            --text-muted: #8fa3b8;
            --border: rgba(255,255,255,0.07);
            --card-bg: rgba(255,255,255,0.04);
            --green: #22c55e;
            --green-soft: rgba(34,197,94,0.12);
        }

        body {
            font-family: 'Mulish', sans-serif;
            background: var(--navy);
            color: var(--text);
            min-height: 100vh;
            background-image:
                radial-gradient(ellipse 80% 50% at 20% -10%, rgba(240,165,0,0.07) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 80% 110%, rgba(26,80,130,0.25) 0%, transparent 60%);
        }

        /* ── Top Nav ── */
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
            letter-spacing: 0.02em;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 100px;
            padding: 5px 14px 5px 8px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .user-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--accent-soft);
            border: 1px solid var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            color: var(--accent);
        }

        .btn-logout {
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
            transition: background 0.15s, color 0.15s, border-color 0.15s;
            cursor: pointer;
        }

        .btn-logout:hover {
            background: rgba(255,255,255,0.06);
            color: var(--text);
            border-color: rgba(255,255,255,0.2);
        }

        /* ── Main ── */
        .main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2.5rem 2rem 4rem;
        }

        .page-header {
            margin-bottom: 2rem;
            animation: fadeUp 0.4s ease both;
        }

        .page-header h1 {
            font-family: 'Syne', sans-serif;
            font-size: 28px;
            font-weight: 600;
            color: var(--text);
            letter-spacing: -0.01em;
        }

        .page-header p {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .page-header span { color: var(--accent); font-weight: 600; }

        /* ── Stats Row ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
            animation: fadeUp 0.4s 0.1s ease both;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.1rem 1.25rem;
        }

        .stat-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 6px;
        }

        .stat-value {
            font-family: 'Syne', sans-serif;
            font-size: 26px;
            font-weight: 600;
            color: var(--text);
        }

        /* ── Table Section ── */
        .table-section {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            animation: fadeUp 0.4s 0.2s ease both;
        }

        .table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .table-title {
            font-family: 'Syne', sans-serif;
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
        }

        .badge {
            background: var(--accent-soft);
            color: var(--accent);
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 12px;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            padding: 11px 1.5rem;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-weight: 600;
            color: var(--text-muted);
            background: rgba(255,255,255,0.02);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.15s;
        }

        tbody tr:last-child { border-bottom: none; }

        tbody tr:hover { background: rgba(255,255,255,0.03); }

        tbody td {
            padding: 14px 1.5rem;
            font-size: 13.5px;
            color: var(--text);
            white-space: nowrap;
        }

        .train-name {
            font-weight: 600;
            color: var(--text);
        }

        .route {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }

        .route-arrow {
            color: var(--text-muted);
            font-size: 11px;
        }

        .time-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 3px 9px;
            font-size: 12.5px;
            font-family: 'Mulish', monospace;
        }

        .seats-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 12px;
            font-weight: 600;
        }

        .seats-ok {
            background: var(--green-soft);
            color: var(--green);
        }

        .seats-low {
            background: rgba(240,165,0,0.12);
            color: var(--accent);
        }

        .fare {
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
        }

        .btn-book {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            background: var(--accent);
            color: var(--navy);
            border-radius: 8px;
            text-decoration: none;
            font-size: 12.5px;
            font-weight: 700;
            font-family: 'Mulish', sans-serif;
            letter-spacing: 0.01em;
            transition: opacity 0.15s, transform 0.1s;
        }

        .btn-book:hover { opacity: 0.88; }
        .btn-book:active { transform: scale(0.97); }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
            font-size: 14px;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .main { padding: 1.5rem 1rem 3rem; }
            .navbar { padding: 0 1rem; }
            thead th:nth-child(4),
            thead th:nth-child(5),
            tbody td:nth-child(4),
            tbody td:nth-child(5) { display: none; }
            .nav-brand-name { display: none; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <a class="nav-brand" href="#">
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
    <div class="nav-right">
        <div class="user-pill">
            <div class="user-avatar"><?php echo strtoupper(substr($email, 0, 1)); ?></div>
            <?php echo htmlspecialchars($email); ?>
        </div>
        <a href="logout.php" class="btn-logout">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Logout
        </a>
    </div>
</nav>

<!-- Main -->
<main class="main">

    <div class="page-header">
        <h1>Available Trains</h1>
        <p>Welcome back, <span><?php echo htmlspecialchars($email); ?></span> — select a train to book your journey.</p>
    </div>

    <?php
        $total_trains = mysqli_num_rows($result);
        $total_seats = 0;
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
            $total_seats += $row['available_seats'];
        }
    ?>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Total Trains</div>
            <div class="stat-value"><?php echo $total_trains; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Seats Available</div>
            <div class="stat-value"><?php echo $total_seats; ?></div>
        </div>
    </div>

    <div class="table-section">
        <div class="table-header">
            <span class="table-title">Train Schedule</span>
            <span class="badge"><?php echo $total_trains; ?> trains</span>
        </div>

        <?php if (count($rows) > 0): ?>
        <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Train Name</th>
                    <th>Route</th>
                    <th>Departure</th>
                    <th>Arrival</th>
                    <th>Seats</th>
                    <th>Fare</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <span class="train-name"><?php echo htmlspecialchars($row['train_name']); ?></span>
                    </td>
                    <td>
                        <div class="route">
                            <?php echo htmlspecialchars($row['source_station']); ?>
                            <span class="route-arrow">→</span>
                            <?php echo htmlspecialchars($row['destination_station']); ?>
                        </div>
                    </td>
                    <td><span class="time-badge"><?php echo htmlspecialchars($row['departure_time']); ?></span></td>
                    <td><span class="time-badge"><?php echo htmlspecialchars($row['arrival_time']); ?></span></td>
                    <td>
                        <?php $seats = $row['available_seats']; ?>
                        <span class="seats-pill <?php echo $seats > 10 ? 'seats-ok' : 'seats-low'; ?>">
                            <?php echo $seats > 10 ? '✓' : '!'; ?>
                            <?php echo $seats; ?> seats
                        </span>
                    </td>
                    <td>
                        <span class="fare">₹<?php echo number_format($row['fare']); ?></span>
                    </td>
                    <td>
                        <a href="book_ticket.php?train_id=<?php echo $row['train_id']; ?>" class="btn-book">
                            Book
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
            <div class="empty-state">No trains available at the moment. Please check back later.</div>
        <?php endif; ?>
    </div>

</main>

</body>
</html>