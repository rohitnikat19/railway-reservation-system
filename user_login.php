<?php
include("db.php");
session_start();

if (isset($_POST['login'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? AND password = ?");
    mysqli_stmt_bind_param($stmt, "ss", $email, $password);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $_SESSION['user'] = $email;
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid Email or Password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RailBook — User Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@500;600&family=Mulish:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:       #0d1b2a;
            --navy-mid:   #1a2e42;
            --navy-light: #243b55;
            --accent:     #f0a500;
            --accent-soft: rgba(240,165,0,0.12);
            --text:       #e8edf2;
            --text-muted: #8fa3b8;
            --border:     rgba(255,255,255,0.07);
            --border-hover: rgba(255,255,255,0.14);
            --card-bg:    rgba(255,255,255,0.04);
        }

        body {
            font-family: 'Mulish', sans-serif;
            background: var(--navy);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background-image:
                radial-gradient(ellipse 70% 50% at 10% -5%,  rgba(240,165,0,0.06) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 90% 105%, rgba(26,80,130,0.22) 0%, transparent 60%);
        }

        /* ── Track decoration ── */
        .track-lines {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            opacity: 0.04;
        }

        .track-lines span {
            display: block;
            position: absolute;
            left: 50%;
            top: 0;
            width: 2px;
            height: 100%;
            background: var(--accent);
            transform: translateX(-50%);
        }

        .track-lines span:nth-child(2) { left: calc(50% - 32px); }
        .track-lines span:nth-child(3) { left: calc(50% + 32px); }

        /* ── Card ── */
        .login-wrap {
            width: 100%;
            max-width: 440px;
            animation: fadeUp 0.45s ease both;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 2rem;
            justify-content: center;
        }

        .brand-logo {
            width: 38px;
            height: 38px;
            background: var(--accent);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-logo svg { width: 22px; height: 22px; }

        .brand-name {
            font-family: 'Syne', sans-serif;
            font-size: 20px;
            font-weight: 600;
            color: var(--text);
            letter-spacing: 0.01em;
        }

        .login-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 2.5rem 2rem;
            backdrop-filter: blur(10px);
        }

        .card-heading {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 4px;
            letter-spacing: -0.01em;
        }

        .card-sub {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 2rem;
        }

        /* ── Error ── */
        .error-msg {
            background: rgba(220, 60, 60, 0.1);
            border: 1px solid rgba(220, 60, 60, 0.25);
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

        .field-input::placeholder { color: rgba(143,163,184,0.5); }

        .field-input:hover { border-color: var(--border-hover); }

        .field-input:focus {
            border-color: var(--accent);
            background: rgba(240,165,0,0.05);
            box-shadow: 0 0 0 3px rgba(240,165,0,0.1);
        }

        /* ── Password toggle ── */
        .pw-wrap { position: relative; }
        .pw-wrap .field-input { padding-right: 44px; }

        .pw-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            padding: 0;
            line-height: 1;
            transition: color 0.15s;
        }

        .pw-toggle:hover { color: var(--accent); }

        /* ── Button ── */
        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--accent);
            border: none;
            border-radius: 10px;
            color: var(--navy);
            font-size: 14px;
            font-weight: 700;
            font-family: 'Mulish', sans-serif;
            letter-spacing: 0.02em;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: opacity 0.15s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover  { opacity: 0.88; }
        .btn-login:active { transform: scale(0.98); }

        /* ── Divider ── */
        .divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 1.75rem 0 1.25rem;
        }

        .footer-note {
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
        }

        .footer-note a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .footer-note a:hover { text-decoration: underline; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- Subtle track decoration -->
<div class="track-lines" aria-hidden="true">
    <span></span><span></span><span></span>
</div>

<div class="login-wrap">

    <!-- Brand -->
    <div class="brand">
        <div class="brand-logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="#0d1b2a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="7" width="20" height="12" rx="2"/>
                <path d="M6 7V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2"/>
                <line x1="6" y1="17" x2="6" y2="19"/>
                <line x1="18" y1="17" x2="18" y2="19"/>
                <line x1="9" y1="12" x2="15" y2="12"/>
            </svg>
        </div>
        <span class="brand-name">RailBook</span>
    </div>

    <!-- Card -->
    <div class="login-card">
        <h1 class="card-heading">Welcome back</h1>
        <p class="card-sub">Sign in to manage your reservations</p>

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

        <form method="POST">

            <div class="field-group">
                <label class="field-label" for="email">Email Address</label>
                <input class="field-input" type="email" id="email" name="email"
                       placeholder="you@example.com"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       required autocomplete="email">
            </div>

            <div class="field-group">
                <label class="field-label" for="password">Password</label>
                <div class="pw-wrap">
                    <input class="field-input" type="password" id="password" name="password"
                           placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="pw-toggle" id="pw-toggle" aria-label="Toggle password visibility">
                        <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" name="login" class="btn-login">
                Sign In
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </button>

        </form>

        <hr class="divider">
        <p class="footer-note">
            Don't have an account? <a href="register.php">Create one</a>
        </p>
    </div>

</div>

<script>
    const toggle   = document.getElementById('pw-toggle');
    const pwInput  = document.getElementById('password');
    const eyeIcon  = document.getElementById('eye-icon');
    let shown = false;

    toggle.addEventListener('click', () => {
        shown = !shown;
        pwInput.type = shown ? 'text' : 'password';
        eyeIcon.innerHTML = shown
            ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>'
            : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    });
</script>

</body>
</html>