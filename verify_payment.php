<?php
/*
 * verify_payment.php
 * ─────────────────────────────────────────────────────────────────
 * Called by payment.php after Razorpay checkout succeeds.
 * Verifies the HMAC-SHA256 signature to confirm authenticity,
 * then saves the payment record to the database.
 * ─────────────────────────────────────────────────────────────────
 */

include("db.php");
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: user_login.php");
    exit();
}

/* ── Razorpay keys (must match payment.php) ── */
define('RAZORPAY_KEY_ID',     'rzp_test_SjF6JcF8p5ITDf');
define('RAZORPAY_KEY_SECRET', 'fU5xGfyjplje6ng98OiCxma3');

/* ── Validate POST fields ── */
$required = ['razorpay_payment_id', 'razorpay_order_id', 'razorpay_signature', 'booking_id'];

foreach ($required as $field) {
    if (empty($_POST[$field])) {
        die("Missing field: $field");
    }
}

$payment_id = $_POST['razorpay_payment_id'];
$order_id   = $_POST['razorpay_order_id'];
$signature  = $_POST['razorpay_signature'];
$booking_id = (int)$_POST['booking_id'];

/* ── Verify HMAC-SHA256 signature ── */
$expected_signature = hash_hmac(
    'sha256',
    $order_id . '|' . $payment_id,
    RAZORPAY_KEY_SECRET
);

if (!hash_equals($expected_signature, $signature)) {
    /* Signature mismatch — possible tampering */
    $_SESSION['payment_error'] = 'Payment verification failed. Please contact support.';
    header("Location: payment.php");
    exit();
}

/* ── Signature is valid — save payment to DB ── */
$payment_method = 'Razorpay';
$payment_status = 'Success';

$stmt = mysqli_prepare($conn,
    "INSERT INTO payments
     (booking_id, payment_method, payment_status, transaction_id)
     VALUES (?, ?, ?, ?)"
);

mysqli_stmt_bind_param($stmt, 'isss',
    $booking_id,
    $payment_method,
    $payment_status,
    $payment_id          /* Razorpay payment ID used as transaction ID */
);

if (mysqli_stmt_execute($stmt)) {
    /* Clean up order session, set success flag */
    unset($_SESSION['rzp_order_id'], $_SESSION['rzp_booking_id']);
    $_SESSION['payment_success'] = $booking_id;
    $_SESSION['payment_txn_id']  = $payment_id;

    header("Location: payment.php");
    exit();
} else {
    /* DB insert failed */
    $_SESSION['payment_error'] = 'Payment received but failed to save. Contact support with ID: ' . $payment_id;
    header("Location: payment.php");
    exit();
}