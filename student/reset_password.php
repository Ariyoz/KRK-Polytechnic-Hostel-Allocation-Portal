<?php
$pageTitle = "Reset Password";
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base   = (strpos($_SERVER['SCRIPT_NAME'], '/hostel_portal/') !== false) ? '/hostel_portal' : '';
    define('BASE_URL', $scheme . '://' . $_SERVER['HTTP_HOST'] . $base);
}
if (isset($_SESSION['student_id'])) { header("Location: dashboard.php"); exit(); }

$error      = '';
$success    = '';
$validToken = false;
$studentId  = null;
$rawToken   = trim($_GET['token'] ?? $_POST['token'] ?? '');

if ($rawToken) {
    $pattern = $rawToken . '|%';
    $stmt = $conn->prepare("SELECT id, firstname, activation_token FROM students WHERE activation_token LIKE ?");
    $stmt->bind_param("s", $pattern); $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();

    if ($student) {
        $parts       = explode('|', $student['activation_token'], 2);
        $storedToken = $parts[0];
        $expiry      = $parts[1] ?? '';
        if (hash_equals($storedToken, $rawToken) && strtotime($expiry) > time()) {
            $validToken = true;
            $studentId  = $student['id'];
        } else {
            $error = 'This reset link has expired or already been used. Please request a new one.';
        }
    } else {
        $error = 'Invalid reset link. Please request a new one.';
    }
} else {
    $error = 'No reset token provided.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken && $studentId) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $upd  = $conn->prepare("UPDATE students SET password=?, activation_token=NULL WHERE id=?");
        $upd->bind_param("si", $hash, $studentId); $upd->execute();
        $success    = 'Your password has been reset successfully. You can now log in with your new password.';
        $validToken = false;
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
        <h1>Create a New<br>Password</h1>
        <p>Secure &middot; Simple &middot; Instant</p>
      </div>
      <ul class="funaab-features">
        <li><i class="fa fa-check-circle"></i> Use at least 6 characters</li>
        <li><i class="fa fa-check-circle"></i> Mix letters, numbers and symbols</li>
        <li><i class="fa fa-check-circle"></i> Your password is securely hashed</li>
        <li><i class="fa fa-check-circle"></i> Log in immediately after resetting</li>
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
        <h2>Set New Password</h2>
        <p>Enter and confirm your new password below.</p>
      </div>

      <?php if ($success): ?>
      <div style="text-align:center;padding:32px 20px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;margin-bottom:20px;">
        <div style="width:60px;height:60px;border-radius:50%;background:#16a34a;color:#fff;display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 16px;">
          <i class="fa fa-check"></i>
        </div>
        <h3 style="font-size:18px;color:#15803d;margin-bottom:8px;">Password Reset!</h3>
        <p style="font-size:14px;color:#166534;"><?= htmlspecialchars($success) ?></p>
      </div>
      <a href="login.php" class="funaab-submit-btn" style="text-decoration:none;">
        <i class="fa fa-sign-in-alt"></i> <span>Go to Login</span>
      </a>

      <?php elseif ($error && !$validToken): ?>
      <div class="funaab-alert funaab-alert-error">
        <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
      <a href="forgot_password.php" class="funaab-submit-btn" style="text-decoration:none;">
        <i class="fa fa-paper-plane"></i> <span>Request New Reset Link</span>
      </a>

      <?php elseif ($validToken): ?>
      <?php if ($error): ?>
      <div class="funaab-alert funaab-alert-error">
        <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>
      <form method="POST" id="registerForm" class="funaab-form">
        <input type="hidden" name="token" value="<?= htmlspecialchars($rawToken) ?>">
        <div class="funaab-field">
          <label for="password"><i class="fa fa-lock"></i> New Password</label>
          <div class="funaab-pwd-wrap">
            <input type="password" id="password" name="password"
                   placeholder="At least 6 characters" required minlength="6" autocomplete="new-password">
            <button type="button" onclick="togglePwd('password',this)" aria-label="Toggle">
              <i class="fa fa-eye"></i>
            </button>
          </div>
        </div>
        <div class="funaab-field">
          <label for="confirm_password"><i class="fa fa-lock"></i> Confirm Password</label>
          <div class="funaab-pwd-wrap">
            <input type="password" id="confirm_password" name="confirm_password"
                   placeholder="Repeat your new password" required autocomplete="new-password">
            <button type="button" onclick="togglePwd('confirm_password',this)" aria-label="Toggle">
              <i class="fa fa-eye"></i>
            </button>
          </div>
        </div>
        <button type="submit" class="funaab-submit-btn">
          <i class="fa fa-save"></i> <span>Save New Password</span>
        </button>
      </form>
      <?php endif; ?>

      <p class="funaab-register-link" style="margin-top:16px;">
        Remembered it? <a href="login.php">Sign in here</a>
      </p>
    </div>
  </div>

</div>
<?php include '../includes/footer.php'; ?>
