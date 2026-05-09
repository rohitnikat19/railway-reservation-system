<?php
include("db.php");
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$username = $_SESSION['admin'];

$users    = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$bookings = mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings");
$payments = mysqli_query($conn, "SELECT COUNT(*) as total FROM payments");
$revenue  = mysqli_query($conn, "SELECT SUM(total_fare) as total FROM bookings");
$trains   = mysqli_query($conn, "SELECT COUNT(*) as total FROM trains");

$user_count    = mysqli_fetch_assoc($users)['total'];
$booking_count = mysqli_fetch_assoc($bookings)['total'];
$payment_count = mysqli_fetch_assoc($payments)['total'];
$total_revenue = mysqli_fetch_assoc($revenue)['total'] ?? 0;
$train_count   = mysqli_fetch_assoc($trains)['total'];

/* Recent bookings */
$recent_bookings = mysqli_query($conn, "
    SELECT b.booking_id, b.passenger_name, b.seat_count, b.total_fare,
           u.email, t.train_name, t.source_station, t.destination_station
    FROM bookings b
    JOIN users u  ON b.user_id  = u.user_id
    JOIN trains t ON b.train_id = t.train_id
    ORDER BY b.booking_id DESC
    LIMIT 8
");

/* Recent payments */
$recent_payments = mysqli_query($conn, "
    SELECT p.transaction_id, p.payment_method, p.payment_status, b.total_fare, b.passenger_name
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    ORDER BY p.payment_id DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RailBook — Admin Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@500;600&family=Mulish:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:        #0d1b2a;
            --navy-mid:    #1a2e42;
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
            --purple:      #a78bfa;
            --purple-soft: rgba(167,139,250,0.10);
            --sidebar-w:   220px;
        }

        body {
            font-family: 'Mulish', sans-serif;
            background: var(--navy);
            color: var(--text);
            min-height: 100vh;
            display: flex;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: rgba(13,27,42,0.95);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 200;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 1.25rem 1.25rem 1rem;
            border-bottom: 1px solid var(--border);
            text-decoration: none;
        }

        .brand-logo {
            width: 34px; height: 34px;
            background: var(--accent);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .brand-logo svg { width: 20px; height: 20px; }

        .brand-text { }
        .brand-name {
            font-family: 'Syne', sans-serif;
            font-size: 15px; font-weight: 600;
            color: var(--text);
            line-height: 1.2;
        }
        .brand-role {
            font-size: 10px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.07em;
            color: var(--accent);
        }

        .sidebar-nav {
            padding: 1rem 0.75rem;
            flex: 1;
        }

        .nav-section-label {
            font-size: 10px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.09em;
            color: var(--text-muted);
            padding: 0 0.5rem;
            margin: 1rem 0 0.4rem;
        }

        .nav-item {
            display: flex; align-items: center; gap: 9px;
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 13px; font-weight: 500;
            color: var(--text-muted);
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
            margin-bottom: 2px;
        }

        .nav-item:hover { background: rgba(255,255,255,0.05); color: var(--text); }
        .nav-item.active { background: var(--accent-soft); color: var(--accent); }
        .nav-item svg { width: 15px; height: 15px; flex-shrink: 0; }

        .sidebar-footer {
            padding: 1rem 0.75rem;
            border-top: 1px solid var(--border);
        }

        .admin-pill {
            display: flex; align-items: center; gap: 9px;
            padding: 8px 10px;
            border-radius: 10px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            margin-bottom: 8px;
        }

        .admin-avatar {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: var(--accent-soft);
            border: 1px solid var(--accent);
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700;
            color: var(--accent);
            flex-shrink: 0;
        }

        .admin-info { overflow: hidden; }
        .admin-name {
            font-size: 12px; font-weight: 600;
            color: var(--text);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .admin-label { font-size: 10px; color: var(--text-muted); }

        .btn-logout {
            display: flex; align-items: center; gap: 8px;
            width: 100%;
            padding: 8px 10px;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            background: transparent;
            color: var(--text-muted);
            font-size: 12px; font-family: 'Mulish', sans-serif; font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }
        .btn-logout:hover { background: rgba(255,255,255,0.05); color: var(--text); }

        /* ── Main content ── */
        .content {
            margin-left: var(--sidebar-w);
            flex: 1;
            min-height: 100vh;
            background-image:
                radial-gradient(ellipse 70% 40% at 80% -10%, rgba(240,165,0,0.05) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 20% 110%, rgba(26,80,130,0.18) 0%, transparent 60%);
        }

        /* ── Top bar ── */
        .topbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 2rem;
            height: 60px;
            border-bottom: 1px solid var(--border);
            background: rgba(13,27,42,0.6);
            backdrop-filter: blur(10px);
            position: sticky; top: 0; z-index: 100;
        }

        .topbar-title {
            font-family: 'Syne', sans-serif;
            font-size: 15px; font-weight: 600;
            color: var(--text);
        }

        .topbar-right { display: flex; align-items: center; gap: 10px; }

        .live-badge {
            display: flex; align-items: center; gap: 6px;
            background: var(--green-soft);
            border: 1px solid rgba(34,197,94,0.2);
            border-radius: 100px;
            padding: 4px 12px;
            font-size: 11px; font-weight: 600;
            color: var(--green);
        }

        .live-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--green);
            animation: pulse 2s infinite;
        }

        /* ── Page body ── */
        .page-body {
            padding: 2rem;
            animation: fadeUp 0.4s ease both;
        }

        .page-header { margin-bottom: 1.75rem; }

        .page-header h1 {
            font-family: 'Syne', sans-serif;
            font-size: 24px; font-weight: 600;
            color: var(--text);
            letter-spacing: -0.01em;
        }

        .page-header p { font-size: 13px; color: var(--text-muted); margin-top: 3px; }

        /* ── Stat cards ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.25rem;
            transition: border-color 0.15s;
        }

        .stat-card:hover { border-color: var(--border-hover); }

        .stat-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 0.9rem;
        }

        .stat-icon svg { width: 18px; height: 18px; }

        .stat-label {
            font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.07em;
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .stat-value {
            font-family: 'Syne', sans-serif;
            font-size: 28px; font-weight: 600;
            color: var(--text);
            line-height: 1;
        }

        /* ── Two-col grid ── */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        /* ── Table card ── */
        .table-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
        }

        .table-card-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
        }

        .table-card-title {
            font-family: 'Syne', sans-serif;
            font-size: 13px; font-weight: 600;
            color: var(--text);
            display: flex; align-items: center; gap: 7px;
        }

        .table-card-title svg { color: var(--accent); }

        .badge {
            background: var(--accent-soft);
            color: var(--accent);
            border-radius: 20px;
            padding: 3px 9px;
            font-size: 11px; font-weight: 600;
        }

        table { width: 100%; border-collapse: collapse; }

        thead th {
            padding: 9px 1.25rem;
            text-align: left;
            font-size: 10px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.07em;
            color: var(--text-muted);
            background: rgba(255,255,255,0.02);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        tbody tr { border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.12s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: rgba(255,255,255,0.03); }

        tbody td {
            padding: 11px 1.25rem;
            font-size: 12.5px;
            color: var(--text);
            white-space: nowrap;
        }

        .td-muted { color: var(--text-muted); font-size: 12px; }

        .route-cell {
            display: flex; align-items: center; gap: 5px;
            font-size: 12px; color: var(--text-muted);
        }

        .route-cell strong { color: var(--text); font-weight: 600; }

        .status-pill {
            display: inline-flex; align-items: center; gap: 4px;
            border-radius: 20px; padding: 3px 9px;
            font-size: 11px; font-weight: 600;
        }

        .status-success { background: var(--green-soft); color: var(--green); }
        .status-pending { background: var(--accent-soft); color: var(--accent); }

        .method-badge {
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 11px; font-weight: 600;
            color: var(--text-muted);
        }

        .fare-val {
            font-family: 'Syne', sans-serif;
            font-size: 13px; font-weight: 600;
            color: var(--accent);
        }

        .empty-row td {
            text-align: center;
            padding: 2rem !important;
            color: var(--text-muted);
            font-size: 13px;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.4; }
        }

        @media (max-width: 900px) {
            .two-col { grid-template-columns: 1fr; }
        }

        @media (max-width: 720px) {
            .sidebar { transform: translateX(-100%); }
            .content { margin-left: 0; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<!-- ── Sidebar ── -->
<aside class="sidebar">
    <a class="sidebar-brand" href="#">
        <div class="brand-logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="#0d1b2a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="7" width="20" height="12" rx="2"/>
                <path d="M6 7V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2"/>
                <line x1="6" y1="17" x2="6" y2="19"/>
                <line x1="18" y1="17" x2="18" y2="19"/>
                <line x1="9" y1="12" x2="15" y2="12"/>
            </svg>
        </div>
        <div class="brand-text">
            <div class="brand-name">RailBook</div>
            <div class="brand-role">Admin Console</div>
        </div>
    </a>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <a href="admin_panel.php" class="nav-item active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
            </svg>
            Dashboard
        </a>

        <div class="nav-section-label">Manage</div>
        <a href="admin_trains.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <rect x="2" y="7" width="20" height="12" rx="2"/>
                <path d="M6 7V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2"/>
                <line x1="6" y1="17" x2="6" y2="19"/><line x1="18" y1="17" x2="18" y2="19"/>
            </svg>
            Trains
        </a>
        <a href="admin_users.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Users
        </a>
        <a href="admin_bookings.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
            </svg>
            Bookings
        </a>
        <a href="admin_payments.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <rect x="1" y="4" width="22" height="16" rx="2"/>
                <line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
            Payments
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-pill">
            <div class="admin-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
            <div class="admin-info">
                <div class="admin-name"><?php echo htmlspecialchars($username); ?></div>
                <div class="admin-label">Administrator</div>
            </div>
        </div>
        <a href="admin_logout.php" class="btn-logout">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Sign out
        </a>
    </div>
</aside>

<!-- ── Main Content ── -->
<div class="content">

    <!-- Topbar -->
    <div class="topbar">
        <span class="topbar-title">Dashboard</span>
        <div class="topbar-right">
            <div class="live-badge">
                <span class="live-dot"></span>
                Live
            </div>
        </div>
    </div>

    <div class="page-body">

        <div class="page-header">
            <h1>System Overview</h1>
            <p>Welcome back, <strong style="color:var(--accent);font-weight:600;"><?php echo htmlspecialchars($username); ?></strong> — here's what's happening today.</p>
        </div>

        <!-- Stat cards -->
        <div class="stats-grid">

            <div class="stat-card">
                <div class="stat-icon" style="background:var(--blue-soft);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2" stroke-linecap="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                    </svg>
                </div>
                <div class="stat-label">Total Users</div>
                <div class="stat-value"><?php echo number_format($user_count); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background:var(--purple-soft);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                </div>
                <div class="stat-label">Total Bookings</div>
                <div class="stat-value"><?php echo number_format($booking_count); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background:var(--green-soft);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2" stroke-linecap="round">
                        <rect x="1" y="4" width="22" height="16" rx="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                </div>
                <div class="stat-label">Total Payments</div>
                <div class="stat-value"><?php echo number_format($payment_count); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background:var(--accent-soft);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value" style="color:var(--accent);">₹<?php echo number_format($total_revenue); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(251,146,60,0.12);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fb923c" stroke-width="2" stroke-linecap="round">
                        <rect x="2" y="7" width="20" height="12" rx="2"/>
                        <path d="M6 7V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2"/>
                    </svg>
                </div>
                <div class="stat-label">Total Trains</div>
                <div class="stat-value"><?php echo number_format($train_count); ?></div>
            </div>

        </div>

        <!-- Two-column tables -->
        <div class="two-col">

            <!-- Recent bookings -->
            <div class="table-card">
                <div class="table-card-header">
                    <div class="table-card-title">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        Recent Bookings
                    </div>
                    <span class="badge">Last 8</span>
                </div>
                <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Passenger</th>
                            <th>Route</th>
                            <th>Fare</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($recent_bookings) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($recent_bookings)): ?>
                        <tr>
                            <td class="td-muted">#<?php echo $row['booking_id']; ?></td>
                            <td>
                                <div style="font-weight:600;font-size:12.5px;"><?php echo htmlspecialchars($row['passenger_name']); ?></div>
                                <div class="td-muted" style="font-size:11px;"><?php echo htmlspecialchars($row['email']); ?></div>
                            </td>
                            <td>
                                <div class="route-cell">
                                    <strong><?php echo htmlspecialchars($row['source_station']); ?></strong>
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                    <strong><?php echo htmlspecialchars($row['destination_station']); ?></strong>
                                </div>
                                <div class="td-muted" style="font-size:11px;"><?php echo htmlspecialchars($row['train_name']); ?></div>
                            </td>
                            <td class="fare-val">₹<?php echo number_format($row['total_fare']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="empty-row"><td colspan="4">No bookings yet</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Recent payments -->
            <div class="table-card">
                <div class="table-card-header">
                    <div class="table-card-title">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <rect x="1" y="4" width="22" height="16" rx="2"/>
                            <line x1="1" y1="10" x2="23" y2="10"/>
                        </svg>
                        Recent Payments
                    </div>
                    <span class="badge">Last 5</span>
                </div>
                <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Transaction</th>
                            <th>Passenger</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($recent_payments) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($recent_payments)): ?>
                        <tr>
                            <td class="td-muted" style="font-family:monospace;font-size:11.5px;"><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                            <td style="font-weight:600;font-size:12.5px;"><?php echo htmlspecialchars($row['passenger_name']); ?></td>
                            <td><span class="method-badge"><?php echo htmlspecialchars($row['payment_method']); ?></span></td>
                            <td class="fare-val">₹<?php echo number_format($row['total_fare']); ?></td>
                            <td>
                                <span class="status-pill <?php echo $row['payment_status'] === 'Success' ? 'status-success' : 'status-pending'; ?>">
                                    <?php echo htmlspecialchars($row['payment_status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="empty-row"><td colspan="5">No payments yet</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>