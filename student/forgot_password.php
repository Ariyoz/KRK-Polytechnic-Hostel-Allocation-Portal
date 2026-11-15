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

$noHeader = true;
include '../includes/header.php';
?>

<div class="funaab-auth">

  <!-- LEFT panel -->
  <div class="funaab-left">
    <div class="funaab-left-bg"></div>
    <div class="funaab-left-overlay"></div>
    <div class="funaab-left-inner">
      <div class="funaab-school-id">
        <div class="funaab-school-abbr">KRK</div>
        <div class="funaab-school-name">KRK Polytechnic</div>
        <div class="funaab-school-loc">Lagos, Nigeria</div>
      </div>
      <div class="funaab-portal-title">
        <h1>Reset Your<br>Password</h1>
        <p>Secure &middot; Simple &middot; Fast</p>
      </div>
      <ul class="funaab-features">
        <li><i class="fa fa-check-circle"></i> Enter your registered email address</li>
        <li><i class="fa fa-check-circle"></i> Receive a secure one-time reset link</li>
        <li><i class="fa fa-check-circle"></i> Link expires in 1 hour for security</li>
        <li><i class="fa fa-check-circle"></i> Set a strong new password</li>
      </ul>
      <div class="funaab-left-footer">&copy; <?= date('Y') ?> KRK Polytechnic &mdash; All rights reserved</div>
    </div>
  </div>

  <!-- RIGHT panel -->
  <div class="funaab-right">
    <div class="funaab-form-wrap">

      <a href="login.php" class="funaab-back-btn">
        <i class="fa fa-arrow-left"></i> Back to Login
      </a>

      <div class="funaab-form-header">
        <h2>Forgot Password?</h2>
        <p>Enter your registered email and we'll send a reset link.</p>
      </div>

      <?php if ($error): ?>
      <div class="funaab-alert funaab-alert-error">
        <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <?php if ($success): ?>
      <div style="text-align:center;padding:32px 20px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;margin-bottom:20px;">
        <div style="width:60px;height:60px;border-radius:50%;background:#16a34a;color:#fff;display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 16px;">
          <i class="fa fa-check"></i>
        </div>
        <h3 style="font-size:18px;color:#15803d;margin-bottom:8px;">Email Sent!</h3>
        <p style="font-size:14px;color:#166534;"><?= htmlspecialchars($success) ?></p>
      </div>
      <a href="login.php" class="funaab-submit-btn" style="text-decoration:none;">
        <i class="fa fa-arrow-left"></i> <span>Back to Login</span>
      </a>
      <?php else: ?>
      <form method="POST" class="funaab-form">
        <div class="funaab-field">
          <label for="email"><i class="fa fa-envelope"></i> Registered Email Address</label>
          <input type="email" id="email" name="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="your@email.com" required autofocus autocomplete="email">
        </div>
        <button type="submit" class="funaab-submit-btn">
          <i class="fa fa-paper-plane"></i> <span>Send Reset Link</span>
        </button>
      </form>
      <?php endif; ?>

      <p class="funaab-register-link">
        Remembered it? <a href="login.php">Sign in here</a>
      </p>
    </div>
  </div>

</div>
<?php include '../includes/footer.php'; ?>
