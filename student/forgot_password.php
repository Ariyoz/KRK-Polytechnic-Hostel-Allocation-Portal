<?php
$pageTitle = "Forgot Password";
require_once '../config/db.php';
require_once '../config/mailer.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base   = (strpos($_SERVER['SCRIPT_NAME'], '/hostel_portal/') !== false) ? '/hostel_portal' : '';
    define('BASE_URL', $scheme . '://' . $_SERVER['HTTP_HOST'] . $base);
}
if (isset($_SESSION['student_id'])) { header("Location: dashboard.php"); exit(); }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Always show success to prevent email enumeration
        $success = 'If an account exists for that email, a password reset link has been sent. Check your inbox.';

        $stmt = $conn->prepare("SELECT id, firstname, surname FROM students WHERE email=? AND is_activated=1");
        $stmt->bind_param("s", $email); $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();

        if ($student) {
            $token   = bin2hex(random_bytes(32));
            $expiry  = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $payload = $token . '|' . $expiry;
            $upd = $conn->prepare("UPDATE students SET activation_token=? WHERE id=?");
            $upd->bind_param("si", $payload, $student['id']); $upd->execute();

            $link = BASE_URL . '/student/reset_password.php?token=' . urlencode($token);
            $body = "<p>Dear <strong>{$student['firstname']} {$student['surname']}</strong>,</p>
                     <p>We received a request to reset your hostel portal password.</p>
                     <p>Click below to set a new password. This link expires in <strong>1 hour</strong>.</p>
                     <a href='" . htmlspecialchars($link) . "' class='btn'>Reset My Password</a>
                     <p style='margin-top:16px;font-size:12px;color:#888;'>If you did not request this, ignore this email.</p>";
            sendEmail($email, 'Password Reset — KRK Polytechnic Hostel Portal', emailTemplate('Password Reset Request', $body));
        }
    }
}

$pageCss  = ['auth.css'];
$noHeader = true;
include '../includes/header.php';
?>

<div class="auth-plain">

  <a href="<?= BASE_URL ?>/index.php" class="brand">
    <svg class="brand-mark" viewBox="0 0 40 46" aria-hidden="true">
      <path d="M20 2 4 8v14c0 12 7 19 16 22 9-3 16-10 16-22V8Z" fill="#102a4a" stroke="#e8a317" stroke-width="2"/>
      <path d="M13 24l7-6 7 6M15 22v8h10v-8" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linejoin="round"/>
    </svg>
    <span class="brand-text">
      <strong>KRK Polytechnic</strong>
      <small>Hostel Allocation Portal</small>
    </span>
  </a>

  <div class="card auth-plain-card stack">

    <a href="login.php" class="back-link">&larr; Back to login</a>

    <div>
      <h1>Forgot password?</h1>
      <p class="muted">Enter your registered email and we'll send a reset link.</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><span><?= htmlspecialchars($error) ?></span></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success"><span><?= htmlspecialchars($success) ?></span></div>
    <a href="login.php" class="btn btn-dark btn-block">Back to login</a>

    <?php else: ?>
    <form method="POST" class="stack">
      <div class="field">
        <label class="label" for="email">Registered email address</label>
        <input class="input" type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="your@email.com" required autofocus autocomplete="email">
      </div>
      <button type="submit" class="btn btn-primary btn-block">Send reset link</button>
    </form>
    <p class="text-sm muted text-center">Remembered it? <a href="login.php" class="sub-link">Log in</a></p>
    <?php endif; ?>

  </div>
</div>

<?php include '../includes/footer.php'; ?>
