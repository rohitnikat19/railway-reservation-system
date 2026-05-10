<?php
include("db.php");
session_start();

if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit();
}

$errors  = [];
$success = false;
$full_name = $email = $phone = '';

if (isset($_POST['register'])) {

    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];

    /* ── Validation ── */
    if (empty($full_name))
        $errors['full_name'] = "Full name is required.";
    elseif (strlen($full_name) < 2)
        $errors['full_name'] = "At least 2 characters.";

    if (empty($email))
        $errors['email'] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors['email'] = "Enter a valid email.";

    if (empty($phone))
        $errors['phone'] = "Phone number is required.";
    elseif (!preg_match('/^[0-9]{10,15}$/', $phone))
        $errors['phone'] = "Enter a valid phone number.";

    if (empty($password))
        $errors['password'] = "Password is required.";
    elseif (strlen($password) < 6)
        $errors['password'] = "Minimum 6 characters.";

    if (empty($confirm))
        $errors['confirm'] = "Please confirm your password.";
    elseif ($password !== $confirm)
        $errors['confirm'] = "Passwords do not match.";

    /* ── Duplicate email check ── */
    if (empty($errors)) {
        $check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        if (mysqli_stmt_num_rows($check) > 0)
            $errors['email'] = "An account with this email already exists.";
        mysqli_stmt_close($check);
    }

    /* ── Insert — plain password to match existing login ── */
    if (empty($errors)) {

        $stmt = mysqli_prepare($conn,
            "INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)"
        );

        if (!$stmt) {
            $errors['db'] = "DB prepare error: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $phone, $password);

            if (mysqli_stmt_execute($stmt)) {
                $success = true;
                $_SESSION['user'] = $email;
            } else {
                $errors['db'] = "Insert error: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RailBook — Create Account</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@500;600;700&family=Mulish:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:        #0d1b2a;
            --accent:      #f0a500;
            --accent-soft: rgba(240,165,0,0.12);
            --text:        #e8edf2;
            --text-muted:  #8fa3b8;
            --border:      rgba(255,255,255,0.07);
            --border-hover:rgba(255,255,255,0.15);
            --card-bg:     rgba(255,255,255,0.04);
            --green:       #22c55e;
            --green-soft:  rgba(34,197,94,0.10);
            --red:         #f87171;
            --red-soft:    rgba(248,113,113,0.08);
        }

        html, body { height: 100%; }

        body {
            font-family: 'Mulish', sans-serif;
            background: var(--navy); color: var(--text);
            min-height: 100vh; display: flex; flex-direction: column;
            background-image:
                radial-gradient(ellipse 80% 60% at 50% -20%, rgba(240,165,0,0.08) 0%, transparent 65%),
                radial-gradient(ellipse 60% 50% at 10% 110%, rgba(26,80,130,0.25) 0%, transparent 60%);
        }

        .tracks { position: fixed; inset: 0; pointer-events: none; z-index: 0; overflow: hidden; opacity: 0.04; }
        .track  { position: absolute; top: 0; bottom: 0; width: 1px;
                  background: linear-gradient(to bottom, transparent, var(--accent) 30%, var(--accent) 70%, transparent); }
        .track:nth-child(1){left:15%} .track:nth-child(2){left:35%}
        .track:nth-child(3){left:55%} .track:nth-child(4){left:75%} .track:nth-child(5){left:90%}

        /* Navbar */
        .navbar { position:relative; z-index:10; display:flex; align-items:center; justify-content:space-between;
                  padding:0 2.5rem; height:64px; border-bottom:1px solid var(--border);
                  background:rgba(13,27,42,0.75); backdrop-filter:blur(14px); }
        .nav-brand { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .nav-logo  { width:34px; height:34px; background:var(--accent); border-radius:9px;
                     display:flex; align-items:center; justify-content:center; }
        .nav-logo svg { width:20px; height:20px; }
        .nav-brand-name { font-family:'Syne',sans-serif; font-size:16px; font-weight:700; color:var(--text); }
        .nav-right { display:flex; align-items:center; gap:8px; }
        .nav-link  { padding:7px 14px; border-radius:8px; font-size:13px; font-weight:500;
                     color:var(--text-muted); text-decoration:none; transition:color 0.15s,background 0.15s; }
        .nav-link:hover { color:var(--text); background:rgba(255,255,255,0.05); }
        .nav-cta   { padding:7px 16px; border-radius:8px; border:1px solid rgba(255,255,255,0.12);
                     font-size:13px; font-weight:600; color:var(--text); text-decoration:none; transition:background 0.15s; }
        .nav-cta:hover { background:rgba(255,255,255,0.06); }

        /* Page */
        .page { position:relative; z-index:5; flex:1;
                display:flex; align-items:flex-start; justify-content:center; padding:2.5rem 1.5rem 4rem; }
        .register-wrap { width:100%; max-width:500px; animation:fadeUp 0.45s ease both; }

        /* Brand header */
        .brand-header { display:flex; align-items:center; gap:10px; justify-content:center; margin-bottom:2rem; }
        .brand-logo   { width:40px; height:40px; background:var(--accent); border-radius:11px;
                        display:flex; align-items:center; justify-content:center; }
        .brand-logo svg { width:22px; height:22px; }
        .brand-name   { font-family:'Syne',sans-serif; font-size:20px; font-weight:700; color:var(--text); }

        /* Card */
        .card { background:var(--card-bg); border:1px solid var(--border); border-radius:20px; padding:2.25rem 2rem; }
        .card-heading { font-family:'Syne',sans-serif; font-size:22px; font-weight:600; color:var(--text); margin-bottom:4px; }
        .card-sub { font-size:13px; color:var(--text-muted); margin-bottom:2rem; }
        .card-sub a { color:var(--accent); font-weight:600; text-decoration:none; }
        .card-sub a:hover { text-decoration:underline; }

        /* Section label */
        .section-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.09em;
                         color:var(--text-muted); display:flex; align-items:center; gap:8px; margin-bottom:1rem; }
        .section-label::after { content:''; flex:1; height:1px; background:var(--border); }

        /* Fields */
        .field-row   { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        .field-group { margin-bottom:1.1rem; }
        .field-label { display:flex; align-items:center; justify-content:space-between;
                       font-size:11px; font-weight:600; text-transform:uppercase;
                       letter-spacing:0.07em; color:var(--text-muted); margin-bottom:7px; }
        .field-err-inline { font-size:10.5px; font-weight:600; text-transform:none; letter-spacing:0; color:var(--red); }

        .field-input { width:100%; padding:11px 14px;
                       background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:10px;
                       color:var(--text); font-size:14px; font-family:'Mulish',sans-serif;
                       outline:none; transition:border-color 0.15s,background 0.15s,box-shadow 0.15s; }
        .field-input::placeholder { color:rgba(143,163,184,0.4); }
        .field-input:hover { border-color:var(--border-hover); }
        .field-input:focus { border-color:var(--accent); background:rgba(240,165,0,0.04); box-shadow:0 0 0 3px rgba(240,165,0,0.1); }
        .field-input.error { border-color:var(--red); background:var(--red-soft); }

        /* Password */
        .pw-wrap { position:relative; }
        .pw-wrap .field-input { padding-right:44px; }
        .pw-toggle { position:absolute; right:12px; top:50%; transform:translateY(-50%);
                     background:none; border:none; cursor:pointer; color:var(--text-muted); padding:0; line-height:1; transition:color 0.15s; }
        .pw-toggle:hover { color:var(--accent); }

        /* Strength */
        .strength-wrap  { margin-top:7px; }
        .strength-bar   { display:flex; gap:3px; margin-bottom:4px; }
        .strength-seg   { flex:1; height:3px; border-radius:2px; background:rgba(255,255,255,0.08); transition:background 0.3s; }
        .strength-label { font-size:10.5px; color:var(--text-muted); }
        .str-1 .strength-seg:nth-child(-n+1) { background:var(--red); }
        .str-2 .strength-seg:nth-child(-n+2) { background:#fb923c; }
        .str-3 .strength-seg:nth-child(-n+3) { background:var(--accent); }
        .str-4 .strength-seg:nth-child(-n+4) { background:var(--green); }

        /* Terms */
        .terms-row { display:flex; align-items:flex-start; gap:10px; margin-bottom:1.4rem;
                     font-size:12.5px; color:var(--text-muted); line-height:1.5; }
        .terms-row input[type="checkbox"] { width:16px; height:16px; flex-shrink:0;
                                            accent-color:var(--accent); margin-top:1px; cursor:pointer; }
        .terms-row a { color:var(--accent); font-weight:600; text-decoration:none; }
        .terms-row a:hover { text-decoration:underline; }

        /* Errors */
        .global-error { background:var(--red-soft); border:1px solid rgba(248,113,113,0.25); color:var(--red);
                        border-radius:10px; padding:11px 14px; font-size:13px; margin-bottom:1.25rem;
                        display:flex; align-items:flex-start; gap:8px; line-height:1.5; }

        /* Submit */
        .btn-register { width:100%; padding:13px; background:var(--accent); border:none; border-radius:11px;
                        color:var(--navy); font-size:14px; font-weight:700; font-family:'Mulish',sans-serif;
                        letter-spacing:0.02em; cursor:pointer;
                        display:flex; align-items:center; justify-content:center; gap:8px;
                        transition:opacity 0.15s,transform 0.1s; margin-bottom:1.25rem; }
        .btn-register:hover  { opacity:0.88; }
        .btn-register:active { transform:scale(0.98); }

        .or-divider { display:flex; align-items:center; gap:10px;
                      font-size:11px; color:var(--text-muted); margin-bottom:1rem; }
        .or-divider::before,.or-divider::after { content:''; flex:1; height:1px; background:var(--border); }

        .signin-link { text-align:center; font-size:13px; color:var(--text-muted); }
        .signin-link a { color:var(--accent); font-weight:600; text-decoration:none; }
        .signin-link a:hover { text-decoration:underline; }

        /* Success */
        .success-card  { background:var(--green-soft); border:1px solid rgba(34,197,94,0.25);
                         border-radius:20px; padding:3rem 2rem; text-align:center; }
        .success-icon  { width:68px; height:68px; border-radius:50%; background:rgba(34,197,94,0.15);
                         display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem;
                         animation:popIn 0.4s cubic-bezier(0.34,1.56,0.64,1) both; }
        .success-icon svg { width:34px; height:34px; color:var(--green); }
        .success-title { font-family:'Syne',sans-serif; font-size:22px; font-weight:700; color:var(--green); margin-bottom:6px; }
        .success-sub   { font-size:13px; color:var(--text-muted); margin-bottom:1.75rem; line-height:1.6; }
        .btn-dashboard { display:inline-flex; align-items:center; gap:8px; padding:12px 28px;
                         background:var(--green); border:none; border-radius:10px; color:#0d2a1a;
                         font-size:14px; font-weight:700; font-family:'Mulish',sans-serif; text-decoration:none;
                         transition:opacity 0.15s,transform 0.1s; }
        .btn-dashboard:hover  { opacity:0.88; }
        .btn-dashboard:active { transform:scale(0.98); }

        @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:none} }
        @keyframes popIn  { from{opacity:0;transform:scale(0.6)}       to{opacity:1;transform:scale(1)} }

        @media (max-width:540px) {
            .field-row { grid-template-columns:1fr; }
            .navbar { padding:0 1.25rem; }
            .nav-link { display:none; }
            .card { padding:1.75rem 1.25rem; }
        }
    </style>
</head>
<body>

<div class="tracks" aria-hidden="true">
    <div class="track"></div><div class="track"></div><div class="track"></div>
    <div class="track"></div><div class="track"></div>
</div>

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
    <div class="nav-right">
        <a href="index.php" class="nav-link">Home</a>
        <a href="user_login.php" class="nav-cta">Sign In</a>
    </div>
</nav>

<div class="page">
<div class="register-wrap">

<?php if ($success): ?>
<div class="success-card">
    <div class="success-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
    </div>
    <div class="success-title">Account Created!</div>
    <p class="success-sub">
        Welcome to RailBook, <strong style="color:var(--text);"><?php echo htmlspecialchars($full_name); ?></strong>!<br>
        You're now logged in and ready to book your journey.
    </p>
    <a href="dashboard.php" class="btn-dashboard">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
        </svg>
        Go to Dashboard
    </a>
</div>

<?php else: ?>

<div class="brand-header">
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

<div class="card">
    <h1 class="card-heading">Create your account</h1>
    <p class="card-sub">Already have an account? <a href="user_login.php">Sign in here</a></p>

    <?php if (isset($errors['db'])): ?>
    <div class="global-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px;">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <span><?php echo htmlspecialchars($errors['db']); ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>

        <div class="section-label">Personal Information</div>

        <div class="field-group">
            <div class="field-label">
                Full Name
                <?php if (isset($errors['full_name'])): ?>
                <span class="field-err-inline"><?php echo htmlspecialchars($errors['full_name']); ?></span>
                <?php endif; ?>
            </div>
            <input class="field-input <?php echo isset($errors['full_name']) ? 'error' : ''; ?>"
                   type="text" name="full_name"
                   placeholder="e.g. Ravi Kumar"
                   value="<?php echo htmlspecialchars($full_name); ?>"
                   autocomplete="name" required>
        </div>

        <div class="section-label">Contact Details</div>

        <div class="field-row">
            <div class="field-group">
                <div class="field-label">
                    Email Address
                    <?php if (isset($errors['email'])): ?>
                    <span class="field-err-inline"><?php echo htmlspecialchars($errors['email']); ?></span>
                    <?php endif; ?>
                </div>
                <input class="field-input <?php echo isset($errors['email']) ? 'error' : ''; ?>"
                       type="email" name="email"
                       placeholder="you@example.com"
                       value="<?php echo htmlspecialchars($email); ?>"
                       autocomplete="email" required>
            </div>

            <div class="field-group">
                <div class="field-label">
                    Phone Number
                    <?php if (isset($errors['phone'])): ?>
                    <span class="field-err-inline"><?php echo htmlspecialchars($errors['phone']); ?></span>
                    <?php endif; ?>
                </div>
                <input class="field-input <?php echo isset($errors['phone']) ? 'error' : ''; ?>"
                       type="tel" name="phone" id="phone"
                       placeholder="10-digit mobile"
                       value="<?php echo htmlspecialchars($phone); ?>"
                       maxlength="15" autocomplete="tel" required>
            </div>
        </div>

        <div class="section-label">Security</div>

        <div class="field-row">
            <div class="field-group">
                <div class="field-label">
                    Password
                    <?php if (isset($errors['password'])): ?>
                    <span class="field-err-inline"><?php echo htmlspecialchars($errors['password']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="pw-wrap">
                    <input class="field-input <?php echo isset($errors['password']) ? 'error' : ''; ?>"
                           type="password" name="password" id="password"
                           placeholder="Min. 6 characters"
                           autocomplete="new-password" required>
                    <button type="button" class="pw-toggle" onclick="togglePw('password','eye1')">
                        <svg id="eye1" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
                <div class="strength-wrap">
                    <div class="strength-bar" id="strength-bar">
                        <div class="strength-seg"></div><div class="strength-seg"></div>
                        <div class="strength-seg"></div><div class="strength-seg"></div>
                    </div>
                    <span class="strength-label" id="strength-label">Enter a password</span>
                </div>
            </div>

            <div class="field-group">
                <div class="field-label">
                    Confirm Password
                    <?php if (isset($errors['confirm'])): ?>
                    <span class="field-err-inline"><?php echo htmlspecialchars($errors['confirm']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="pw-wrap">
                    <input class="field-input <?php echo isset($errors['confirm']) ? 'error' : ''; ?>"
                           type="password" name="confirm_password" id="confirm_password"
                           placeholder="Re-enter password"
                           autocomplete="new-password" required>
                    <button type="button" class="pw-toggle" onclick="togglePw('confirm_password','eye2')">
                        <svg id="eye2" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="terms-row">
            <input type="checkbox" name="terms" id="terms" required>
            <label for="terms">
                I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a> of RailBook.
            </label>
        </div>

        <button type="submit" name="register" class="btn-register">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>
            </svg>
            Create Account
        </button>

        <div class="or-divider">or</div>
        <div class="signin-link">Already registered? <a href="user_login.php">Sign in to your account</a></div>

    </form>
</div>

<?php endif; ?>
</div>
</div>

<script>
function togglePw(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    icon.innerHTML = show
        ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>'
        : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
}

const pwInput     = document.getElementById('password');
const strengthBar = document.getElementById('strength-bar');
const strengthLbl = document.getElementById('strength-label');
const levels = ['Enter a password','Too weak','Could be stronger','Getting better!','Strong password ✓'];
const colors = ['var(--text-muted)','var(--red)','#fb923c','var(--accent)','var(--green)'];

pwInput.addEventListener('input', () => {
    const v = pwInput.value;
    let s = 0;
    if (v.length >= 6)  s++;
    if (v.length >= 10) s++;
    if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
    if (/[0-9]/.test(v)) s++;
    if (/[^A-Za-z0-9]/.test(v)) s = Math.min(4, s + 1);
    s = Math.min(4, s);
    const d = v.length === 0 ? 0 : Math.max(1, s);
    strengthBar.className = 'strength-bar str-' + d;
    strengthLbl.textContent = levels[d];
    strengthLbl.style.color = colors[d];
});

const confirmInput = document.getElementById('confirm_password');
confirmInput.addEventListener('input', () => {
    if (!confirmInput.value) { confirmInput.style.borderColor=''; confirmInput.style.boxShadow=''; return; }
    const match = confirmInput.value === pwInput.value;
    confirmInput.style.borderColor = match ? 'var(--green)' : 'var(--red)';
    confirmInput.style.boxShadow   = match ? '0 0 0 3px rgba(34,197,94,0.12)' : '0 0 0 3px rgba(248,113,113,0.12)';
});

document.getElementById('phone').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 15);
});
</script>

</body>
</html>