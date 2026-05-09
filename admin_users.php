<?php
include("db.php");
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$username = $_SESSION['admin'];

/* Delete user */
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM users WHERE user_id = $del_id");
    header("Location: admin_users.php?msg=deleted");
    exit();
}

/* Search / filter */
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where  = $search ? "WHERE email LIKE '%$search%'" : '';

$users_result = mysqli_query($conn, "SELECT * FROM users $where ORDER BY user_id DESC");
$total_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$total_users  = mysqli_fetch_assoc($total_result)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RailBook — Manage Users</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@500;600&family=Mulish:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:         #0d1b2a;
            --accent:       #f0a500;
            --accent-soft:  rgba(240,165,0,0.12);
            --text:         #e8edf2;
            --text-muted:   #8fa3b8;
            --border:       rgba(255,255,255,0.07);
            --border-hover: rgba(255,255,255,0.14);
            --card-bg:      rgba(255,255,255,0.04);
            --green:        #22c55e;
            --green-soft:   rgba(34,197,94,0.10);
            --blue:         #60a5fa;
            --blue-soft:    rgba(96,165,250,0.10);
            --red:          #f87171;
            --red-soft:     rgba(248,113,113,0.10);
            --purple:       #a78bfa;
            --purple-soft:  rgba(167,139,250,0.10);
            --sidebar-w:    220px;
        }

        body {
            font-family: 'Mulish', sans-serif;
            background: var(--navy);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            background-image:
                radial-gradient(ellipse 70% 40% at 80% -10%, rgba(240,165,0,0.04) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 20% 110%, rgba(26,80,130,0.15) 0%, transparent 60%);
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
            display: flex; align-items: center; gap: 10px;
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

        .brand-name {
            font-family: 'Syne', sans-serif;
            font-size: 15px; font-weight: 600;
            color: var(--text); line-height: 1.2;
        }

        .brand-role {
            font-size: 10px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.07em;
            color: var(--accent);
        }

        .sidebar-nav { padding: 1rem 0.75rem; flex: 1; }

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
            color: var(--accent); flex-shrink: 0;
        }

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
            transition: background 0.15s, color 0.15s;
        }
        .btn-logout:hover { background: rgba(255,255,255,0.05); color: var(--text); }

        /* ── Content ── */
        .content {
            margin-left: var(--sidebar-w);
            flex: 1;
            min-height: 100vh;
        }

        .topbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 2rem;
            height: 60px;
            border-bottom: 1px solid var(--border);
            background: rgba(13,27,42,0.6);
            backdrop-filter: blur(10px);
            position: sticky; top: 0; z-index: 100;
        }

        .topbar-left { display: flex; align-items: center; gap: 10px; }

        .breadcrumb {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; color: var(--text-muted);
        }

        .breadcrumb a { color: var(--text-muted); text-decoration: none; }
        .breadcrumb a:hover { color: var(--accent); }
        .breadcrumb span { color: var(--text); font-weight: 600; }

        /* ── Page body ── */
        .page-body {
            padding: 2rem;
            animation: fadeUp 0.4s ease both;
        }

        .page-header {
            display: flex; align-items: flex-start; justify-content: space-between;
            flex-wrap: wrap; gap: 1rem;
            margin-bottom: 1.75rem;
        }

        .page-header h1 {
            font-family: 'Syne', sans-serif;
            font-size: 24px; font-weight: 600;
            color: var(--text);
        }

        .page-header p { font-size: 13px; color: var(--text-muted); margin-top: 3px; }

        /* ── Stats strip ── */
        .stats-strip {
            display: flex; gap: 1rem; flex-wrap: wrap;
            margin-bottom: 1.75rem;
        }

        .mini-stat {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0.9rem 1.25rem;
            display: flex; align-items: center; gap: 12px;
            min-width: 160px;
        }

        .mini-stat-icon {
            width: 34px; height: 34px;
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
        }

        .mini-stat-icon svg { width: 17px; height: 17px; }

        .mini-stat-label {
            font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.06em;
            color: var(--text-muted); margin-bottom: 2px;
        }

        .mini-stat-val {
            font-family: 'Syne', sans-serif;
            font-size: 20px; font-weight: 600;
            color: var(--text);
        }

        /* ── Toast ── */
        .toast {
            display: flex; align-items: center; gap: 8px;
            background: var(--green-soft);
            border: 1px solid rgba(34,197,94,0.25);
            color: var(--green);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px; font-weight: 500;
            margin-bottom: 1.25rem;
        }

        /* ── Table card ── */
        .table-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        .table-toolbar {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
        }

        .table-title {
            font-family: 'Syne', sans-serif;
            font-size: 14px; font-weight: 600;
            color: var(--text);
            display: flex; align-items: center; gap: 7px;
        }

        .table-title svg { color: var(--accent); }

        .count-badge {
            background: var(--accent-soft);
            color: var(--accent);
            border-radius: 20px;
            padding: 3px 9px;
            font-size: 11px; font-weight: 600;
        }

        .search-wrap {
            position: relative;
            display: flex; align-items: center;
        }

        .search-wrap svg {
            position: absolute; left: 10px;
            color: var(--text-muted);
            pointer-events: none;
        }

        .search-input {
            padding: 8px 12px 8px 32px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 13px; font-family: 'Mulish', sans-serif;
            outline: none;
            width: 220px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .search-input::placeholder { color: rgba(143,163,184,0.4); }
        .search-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(240,165,0,0.1); }

        /* ── Table ── */
        table { width: 100%; border-collapse: collapse; }

        thead th {
            padding: 10px 1.25rem;
            text-align: left;
            font-size: 10px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.07em;
            color: var(--text-muted);
            background: rgba(255,255,255,0.02);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid rgba(255,255,255,0.04);
            transition: background 0.12s;
        }

        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: rgba(255,255,255,0.03); }

        tbody td {
            padding: 12px 1.25rem;
            font-size: 13px;
            color: var(--text);
            white-space: nowrap;
        }

        .td-muted { color: var(--text-muted); font-size: 12px; }

        .user-cell {
            display: flex; align-items: center; gap: 10px;
        }

        .user-avatar-sm {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: var(--blue-soft);
            border: 1px solid rgba(96,165,250,0.25);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700;
            color: var(--blue);
            flex-shrink: 0;
        }

        .user-name { font-weight: 600; font-size: 13px; }
        .user-email { font-size: 11px; color: var(--text-muted); }

        .id-badge {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 11.5px; font-family: monospace;
            color: var(--text-muted);
        }

        .action-btns { display: flex; align-items: center; gap: 6px; }

        .btn-view {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 12px;
            border: 1px solid var(--border);
            border-radius: 7px;
            background: transparent;
            color: var(--text-muted);
            font-size: 12px; font-family: 'Mulish', sans-serif; font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }

        .btn-view:hover { background: rgba(255,255,255,0.06); color: var(--text); border-color: var(--border-hover); }

        .btn-delete {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 12px;
            border: 1px solid rgba(248,113,113,0.2);
            border-radius: 7px;
            background: transparent;
            color: var(--red);
            font-size: 12px; font-family: 'Mulish', sans-serif; font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
        }

        .btn-delete:hover { background: var(--red-soft); border-color: rgba(248,113,113,0.4); }

        .empty-row td {
            text-align: center;
            padding: 3rem !important;
            color: var(--text-muted);
            font-size: 13px;
        }

        /* ── Pagination ── */
        .pagination {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.9rem 1.25rem;
            border-top: 1px solid var(--border);
            font-size: 12px; color: var(--text-muted);
        }

        /* ── Modal overlay ── */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            z-index: 500;
            align-items: center; justify-content: center;
        }

        .modal-overlay.open { display: flex; }

        .modal {
            background: #1a2e42;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            max-width: 380px; width: 90%;
            animation: popIn 0.25s cubic-bezier(0.34,1.56,0.64,1) both;
        }

        .modal-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            background: var(--red-soft);
            border: 1px solid rgba(248,113,113,0.2);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 1rem;
        }

        .modal-icon svg { width: 22px; height: 22px; color: var(--red); }

        .modal h3 {
            font-family: 'Syne', sans-serif;
            font-size: 17px; font-weight: 600;
            color: var(--text); margin-bottom: 6px;
        }

        .modal p { font-size: 13px; color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.6; }

        .modal-actions { display: flex; gap: 8px; }

        .btn-cancel {
            flex: 1; padding: 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: transparent;
            color: var(--text-muted);
            font-size: 13px; font-family: 'Mulish', sans-serif; font-weight: 500;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-cancel:hover { background: rgba(255,255,255,0.05); }

        .btn-confirm-delete {
            flex: 1; padding: 10px;
            border: none;
            border-radius: 8px;
            background: var(--red);
            color: #fff;
            font-size: 13px; font-family: 'Mulish', sans-serif; font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: flex; align-items: center; justify-content: center;
            transition: opacity 0.15s;
        }

        .btn-confirm-delete:hover { opacity: 0.85; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes popIn {
            from { opacity: 0; transform: scale(0.92); }
            to   { opacity: 1; transform: scale(1); }
        }

        @media (max-width: 720px) {
            .sidebar { transform: translateX(-100%); }
            .content { margin-left: 0; }
            .search-input { width: 160px; }
        }
    </style>
</head>
<body>

<!-- ── Sidebar ── -->
<aside class="sidebar">
    <a class="sidebar-brand" href="admin_panel.php">
        <div class="brand-logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="#0d1b2a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="7" width="20" height="12" rx="2"/>
                <path d="M6 7V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2"/>
                <line x1="6" y1="17" x2="6" y2="19"/>
                <line x1="18" y1="17" x2="18" y2="19"/>
                <line x1="9" y1="12" x2="15" y2="12"/>
            </svg>
        </div>
        <div>
            <div class="brand-name">RailBook</div>
            <div class="brand-role">Admin Console</div>
        </div>
    </a>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <a href="admin_panel.php" class="nav-item">
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
        <a href="admin_users.php" class="nav-item active">
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
                <polyline points="14 2 14 8 20 8"/>
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
            <div>
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

    <div class="topbar">
        <div class="topbar-left">
            <div class="breadcrumb">
                <a href="admin_panel.php">Dashboard</a>
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
                <span>Users</span>
            </div>
        </div>
    </div>

    <div class="page-body">

        <div class="page-header">
            <div>
                <h1>Manage Users</h1>
                <p>View, search, and manage all registered passengers.</p>
            </div>
        </div>

        <!-- Stats strip -->
        <div class="stats-strip">
            <div class="mini-stat">
                <div class="mini-stat-icon" style="background:var(--blue-soft);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2" stroke-linecap="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                    </svg>
                </div>
                <div>
                    <div class="mini-stat-label">Total Users</div>
                    <div class="mini-stat-val"><?php echo number_format($total_users); ?></div>
                </div>
            </div>
            <?php if ($search): ?>
            <div class="mini-stat">
                <div class="mini-stat-icon" style="background:var(--accent-soft);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </div>
                <div>
                    <div class="mini-stat-label">Search Results</div>
                    <div class="mini-stat-val"><?php echo mysqli_num_rows($users_result); ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Toast -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="toast">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
            User deleted successfully.
        </div>
        <?php endif; ?>

        <!-- Table -->
        <div class="table-card">
            <div class="table-toolbar">
                <div class="table-title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                    </svg>
                    All Users
                    <span class="count-badge"><?php echo number_format($total_users); ?></span>
                </div>
                <form method="GET" style="display:flex;gap:6px;align-items:center;">
                    <div class="search-wrap">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input class="search-input" type="text" name="search"
                               placeholder="Search by email…"
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <?php if ($search): ?>
                    <a href="admin_users.php" style="font-size:12px;color:var(--text-muted);text-decoration:none;padding:4px 8px;border:1px solid var(--border);border-radius:7px;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>ID</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $count = 0;
                if (mysqli_num_rows($users_result) > 0):
                    while ($row = mysqli_fetch_assoc($users_result)):
                        $count++;
                        $initials = strtoupper(substr($row['email'], 0, 1));
                ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar-sm"><?php echo $initials; ?></div>
                                <div>
                                    <div class="user-name"><?php echo htmlspecialchars($row['name'] ?? '—'); ?></div>
                                    <div class="user-email"><?php echo htmlspecialchars($row['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="id-badge">#<?php echo $row['user_id']; ?></span></td>
                        <td class="td-muted"><?php echo htmlspecialchars($row['phone'] ?? '—'); ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="admin_user_detail.php?id=<?php echo $row['user_id']; ?>" class="btn-view">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    View
                                </a>
                                <button class="btn-delete"
                                    onclick="openDeleteModal(<?php echo $row['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['email'])); ?>')">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                        <path d="M10 11v6"/><path d="M14 11v6"/>
                                        <path d="M9 6V4h6v2"/>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php
                    endwhile;
                else:
                ?>
                    <tr class="empty-row">
                        <td colspan="4">
                            <?php echo $search ? "No users found matching \"" . htmlspecialchars($search) . "\"" : "No users registered yet."; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>

            <div class="pagination">
                <span>Showing <?php echo $count; ?> of <?php echo $total_users; ?> users</span>
            </div>
        </div>

    </div>
</div>

<!-- ── Delete Confirmation Modal ── -->
<div class="modal-overlay" id="delete-modal">
    <div class="modal">
        <div class="modal-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                <path d="M9 6V4h6v2"/>
            </svg>
        </div>
        <h3>Delete User?</h3>
        <p id="modal-msg">This will permanently remove the user and cannot be undone.</p>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <a href="#" id="confirm-delete-btn" class="btn-confirm-delete">Delete</a>
        </div>
    </div>
</div>

<script>
    function openDeleteModal(userId, email) {
        document.getElementById('modal-msg').textContent =
            'This will permanently delete "' + email + '" and all their data.';
        document.getElementById('confirm-delete-btn').href =
            'admin_users.php?delete=' + userId;
        document.getElementById('delete-modal').classList.add('open');
    }

    function closeDeleteModal() {
        document.getElementById('delete-modal').classList.remove('open');
    }

    document.getElementById('delete-modal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });

    /* Auto-submit search on input with debounce */
    let debounce;
    document.querySelector('.search-input').addEventListener('input', function() {
        clearTimeout(debounce);
        debounce = setTimeout(() => this.closest('form').submit(), 400);
    });
</script>

</body>
</html>