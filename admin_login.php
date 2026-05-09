<?php
include("db.php");
session_start();

if (isset($_POST['login'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM admins 
              WHERE username='$username' 
              AND password='$password'";

    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $_SESSION['admin'] = $username;
        header("Location: admin_panel.php");
        exit();
    } else {
        $error = "Invalid Admin Credentials";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f4f3fb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-card {
            background: #ffffff;
            border: 1px solid #e5e3f5;
            border-radius: 16px;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 4px 24px rgba(83, 74, 183, 0.08);
        }

        .login-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: #EEEDFE;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .login-icon svg {
            width: 26px;
            height: 26px;
        }

        .login-title {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 500;
            color: #1a1830;
            margin-bottom: 4px;
        }

        .login-sub {
            font-size: 13px;
            color: #7b7a8e;
            margin-bottom: 2rem;
        }

        .error-msg {
            background: #fff0f0;
            border: 1px solid #f5c1c1;
            color: #a32d2d;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .field-group {
            margin-bottom: 1.25rem;
        }

        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 500;
            color: #7b7a8e;
            margin-bottom: 6px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .field-input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #e0dff0;
            border-radius: 10px;
            background: #f9f8fe;
            color: #1a1830;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .field-input::placeholder { color: #b0afc8; }

        .field-input:focus {
            border-color: #534AB7;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(83, 74, 183, 0.12);
        }

        .pw-wrap { position: relative; }

        .pw-wrap .field-input { padding-right: 44px; }

        .pw-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            color: #b0afc8;
            line-height: 1;
            transition: color 0.15s;
        }

        .pw-toggle:hover { color: #534AB7; }

        .btn-login {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: #534AB7;
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: background 0.15s, transform 0.1s;
            letter-spacing: 0.01em;
        }

        .btn-login:hover { background: #3C3489; }
        .btn-login:active { transform: scale(0.98); }

        .divider {
            border: none;
            border-top: 1px solid #f0eef8;
            margin: 1.75rem 0 1.25rem;
        }

        .footer-note {
            text-align: center;
            font-size: 12px;
            color: #b0afc8;
        }
    </style>
</head>
<body>

<div class="login-card">

    <div class="login-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="#534AB7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            <circle cx="12" cy="16" r="1" fill="#534AB7" stroke="none"/>
        </svg>
    </div>

    <h1 class="login-title">Admin Portal</h1>
    <p class="login-sub">Sign in to access the dashboard</p>

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
            <label class="field-label" for="username">Username</label>
            <input class="field-input" type="text" id="username" name="username" placeholder="Enter your username" required>
        </div>

        <div class="field-group">
            <label class="field-label" for="password">Password</label>
            <div class="pw-wrap">
                <input class="field-input" type="password" id="password" name="password" placeholder="••••••••" required>
                <button type="button" class="pw-toggle" id="pw-toggle" aria-label="Toggle password visibility">
                    <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit" name="login" class="btn-login">Sign in</button>

    </form>

    <hr class="divider">
    <p class="footer-note">Restricted access — authorized personnel only</p>

</div>

<script>
    const toggle = document.getElementById('pw-toggle');
    const pwInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');
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