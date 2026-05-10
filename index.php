<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RailBook — Railway Reservation System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=Mulish:wght@400;500;600&display=swap" rel="stylesheet">
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
            --blue:        #60a5fa;
            --blue-soft:   rgba(96,165,250,0.10);
            --green:       #22c55e;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: 'Mulish', sans-serif;
            background: var(--navy);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* ── Background layers ── */
        .bg-layer {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }

        .bg-layer::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 50% -20%, rgba(240,165,0,0.09) 0%, transparent 65%),
                radial-gradient(ellipse 60% 50% at 10% 110%, rgba(26,80,130,0.28) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 90% 100%, rgba(96,165,250,0.08) 0%, transparent 60%);
        }

        /* Animated track lines */
        .tracks {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
            opacity: 0.045;
        }

        .track {
            position: absolute;
            top: 0; bottom: 0;
            width: 1px;
            background: linear-gradient(to bottom, transparent, var(--accent) 30%, var(--accent) 70%, transparent);
        }

        .track:nth-child(1) { left: 20%; }
        .track:nth-child(2) { left: 35%; }
        .track:nth-child(3) { left: 50%; }
        .track:nth-child(4) { left: 65%; }
        .track:nth-child(5) { left: 80%; }

        /* ── Navbar ── */
        .navbar {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 3rem;
            height: 66px;
            border-bottom: 1px solid var(--border);
            background: rgba(13,27,42,0.7);
            backdrop-filter: blur(14px);
        }

        .nav-brand {
            display: flex; align-items: center; gap: 11px;
            text-decoration: none;
        }

        .nav-logo {
            width: 36px; height: 36px;
            background: var(--accent);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }

        .nav-logo svg { width: 21px; height: 21px; }

        .nav-brand-name {
            font-family: 'Syne', sans-serif;
            font-size: 17px; font-weight: 700;
            color: var(--text);
            letter-spacing: 0.01em;
        }

        .nav-links {
            display: flex; align-items: center; gap: 0.5rem;
        }

        .nav-link {
            padding: 7px 16px;
            border-radius: 8px;
            font-size: 13px; font-weight: 500;
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.15s, background 0.15s;
        }

        .nav-link:hover { color: var(--text); background: rgba(255,255,255,0.05); }

        /* ── Hero ── */
        .hero {
            position: relative;
            z-index: 5;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 4rem 1.5rem 5rem;
        }

        .hero-eyebrow {
            display: inline-flex; align-items: center; gap: 7px;
            background: var(--accent-soft);
            border: 1px solid rgba(240,165,0,0.2);
            border-radius: 100px;
            padding: 5px 14px;
            font-size: 12px; font-weight: 600;
            color: var(--accent);
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
            animation: fadeUp 0.5s 0.1s ease both;
        }

        .eyebrow-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--accent);
            animation: pulse 2s infinite;
        }

        .hero-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(36px, 6vw, 64px);
            font-weight: 700;
            color: var(--text);
            line-height: 1.1;
            letter-spacing: -0.02em;
            margin-bottom: 1.25rem;
            max-width: 720px;
            animation: fadeUp 0.5s 0.2s ease both;
        }

        .hero-title span {
            color: var(--accent);
            position: relative;
        }

        .hero-title span::after {
            content: '';
            position: absolute;
            left: 0; right: 0;
            bottom: -4px;
            height: 2px;
            background: var(--accent);
            border-radius: 2px;
            opacity: 0.4;
        }

        .hero-sub {
            font-size: 16px;
            color: var(--text-muted);
            max-width: 480px;
            line-height: 1.7;
            margin-bottom: 3rem;
            animation: fadeUp 0.5s 0.3s ease both;
        }

        /* ── Login cards ── */
        .login-cards {
            display: flex;
            gap: 1.25rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeUp 0.5s 0.4s ease both;
        }

        .login-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2rem 1.75rem;
            width: 260px;
            text-align: left;
            text-decoration: none;
            color: var(--text);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: border-color 0.2s, transform 0.2s, box-shadow 0.2s;
            display: block;
        }

        .login-card::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 0.2s;
            border-radius: inherit;
        }

        .login-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        /* User card */
        .card-user { border-color: rgba(96,165,250,0.2); }
        .card-user::before { background: radial-gradient(ellipse at top left, rgba(96,165,250,0.08), transparent 70%); }
        .card-user:hover { border-color: rgba(96,165,250,0.45); }
        .card-user:hover::before { opacity: 1; }

        /* Admin card */
        .card-admin { border-color: rgba(240,165,0,0.2); }
        .card-admin::before { background: radial-gradient(ellipse at top left, rgba(240,165,0,0.08), transparent 70%); }
        .card-admin:hover { border-color: rgba(240,165,0,0.45); }
        .card-admin:hover::before { opacity: 1; }

        .card-icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 1.25rem;
        }

        .card-icon svg { width: 24px; height: 24px; }

        .card-user .card-icon { background: var(--blue-soft); border: 1px solid rgba(96,165,250,0.2); }
        .card-admin .card-icon { background: var(--accent-soft); border: 1px solid rgba(240,165,0,0.2); }

        .card-label {
            font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.08em;
            margin-bottom: 6px;
        }

        .card-user .card-label  { color: var(--blue); }
        .card-admin .card-label { color: var(--accent); }

        .card-title {
            font-family: 'Syne', sans-serif;
            font-size: 19px; font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }

        .card-desc {
            font-size: 12.5px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .card-btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 18px;
            border-radius: 10px;
            font-size: 13px; font-weight: 700;
            font-family: 'Mulish', sans-serif;
            letter-spacing: 0.01em;
            transition: opacity 0.15s, transform 0.1s;
        }

        .card-btn:active { transform: scale(0.97); }

        .card-user .card-btn {
            background: var(--blue);
            color: #0d1b2a;
        }

        .card-user .card-btn:hover { opacity: 0.88; }

        .card-admin .card-btn {
            background: var(--accent);
            color: #0d1b2a;
        }

        .card-admin .card-btn:hover { opacity: 0.88; }

        .card-arrow {
            position: absolute;
            top: 1.5rem; right: 1.5rem;
            width: 28px; height: 28px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            display: flex; align-items: center; justify-content: center;
            transition: background 0.15s, transform 0.15s;
        }

        .login-card:hover .card-arrow {
            background: rgba(255,255,255,0.1);
            transform: translate(2px, -2px);
        }

        /* ── Features strip ── */
        .features {
            position: relative; z-index: 5;
            display: flex; gap: 2rem;
            justify-content: center; flex-wrap: wrap;
            padding: 0 2rem 3rem;
            animation: fadeUp 0.5s 0.5s ease both;
        }

        .feature-item {
            display: flex; align-items: center; gap: 8px;
            font-size: 12.5px; color: var(--text-muted);
        }

        .feature-icon {
            width: 28px; height: 28px;
            border-radius: 8px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
        }

        .feature-icon svg { width: 13px; height: 13px; }

        /* ── Footer ── */
        .footer {
            position: relative; z-index: 5;
            text-align: center;
            padding: 1.25rem;
            border-top: 1px solid var(--border);
            font-size: 12px; color: var(--text-muted);
            background: rgba(13,27,42,0.5);
        }

        .footer span { color: var(--accent); font-weight: 600; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.35; }
        }

        @media (max-width: 600px) {
            .navbar { padding: 0 1.25rem; }
            .nav-links { display: none; }
            .login-card { width: 100%; max-width: 320px; }
            .features { gap: 1rem; }
        }
    </style>
</head>
<body>

<!-- Background -->
<div class="bg-layer"></div>
<div class="tracks" aria-hidden="true">
    <div class="track"></div>
    <div class="track"></div>
    <div class="track"></div>
    <div class="track"></div>
    <div class="track"></div>
</div>

<!-- Navbar -->
<nav class="navbar">
    <a class="nav-brand" href="index.php">
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
    <div class="nav-links">
        <a href="#" class="nav-link">About</a>
        <a href="#" class="nav-link">Help</a>
        <a href="user_login.php" class="nav-link">Sign In</a>
    </div>
</nav>

<!-- Hero -->
<section class="hero">

    <div class="hero-eyebrow">
        <span class="eyebrow-dot"></span>
        Railway Reservation System
    </div>

    <h1 class="hero-title">
        Book your journey<br>with <span>RailBook</span>
    </h1>

    <p class="hero-sub">
        Fast, simple, and reliable train ticket reservations.
        Choose your portal below to get started.
    </p>

    <!-- Login cards -->
    <div class="login-cards">

        <!-- User Login -->
        <a href="user_login.php" class="login-card card-user">
            <div class="card-arrow">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/>
                </svg>
            </div>
            <div class="card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div class="card-label">Passenger Portal</div>
            <div class="card-title">User Login</div>
            <div class="card-desc">Search trains, book tickets, manage your reservations and track your journeys.</div>
            <div class="card-btn">
                Get Started
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </div>
        </a>

        <!-- Admin Login -->
        <a href="admin_login.php" class="login-card card-admin">
            <div class="card-arrow">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/>
                </svg>
            </div>
            <div class="card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    <circle cx="12" cy="16" r="1" fill="var(--accent)" stroke="none"/>
                </svg>
            </div>
            <div class="card-label">Admin Portal</div>
            <div class="card-title">Admin Login</div>
            <div class="card-desc">Manage trains, users, bookings and payments from the admin dashboard.</div>
            <div class="card-btn">
                Admin Access
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </div>
        </a>

    </div>
</section>

<!-- Features strip -->
<div class="features">
    <div class="feature-item">
        <div class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        Instant Booking
    </div>
    <div class="feature-item">
        <div class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2" stroke-linecap="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
        </div>
        Secure Payments
    </div>
    <div class="feature-item">
        <div class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2" stroke-linecap="round">
                <rect x="2" y="7" width="20" height="12" rx="2"/>
                <path d="M6 7V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2"/>
            </svg>
        </div>
        Live Train Status
    </div>
    <div class="feature-item">
        <div class="feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--purple, #a78bfa)" stroke-width="2" stroke-linecap="round">
                <rect x="1" y="4" width="22" height="16" rx="2"/>
                <line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
        </div>
        Multiple Payment Modes
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    &copy; <?php echo date('Y'); ?> <span>RailBook</span> &mdash; Railway Reservation System. All rights reserved.
</footer>

</body>
</html>



