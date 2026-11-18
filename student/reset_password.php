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
      <h1>Set new password</h1>
      <p class="muted">Enter and confirm your new password below.</p>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success"><span><?= htmlspecialchars($success) ?></span></div>
    <a href="login.php" class="btn btn-dark btn-block">Go to login</a>

    <?php elseif ($error && !$validToken): ?>
    <div class="alert alert-danger"><span><?= htmlspecialchars($error) ?></span></div>
    <a href="forgot_password.php" class="btn btn-dark btn-block">Request new reset link</a>

    <?php elseif ($validToken): ?>
    <?php if ($error): ?>
    <div class="alert alert-danger"><span><?= htmlspecialchars($error) ?></span></div>
    <?php endif; ?>
    <form method="POST" class="stack">
      <input type="hidden" name="token" value="<?= htmlspecialchars($rawToken) ?>">
      <div class="field">
        <label class="label" for="password">New password</label>
        <div class="pwd-field">
          <input class="input" type="password" id="password" name="password"
                 placeholder="At least 6 characters" required minlength="6" autocomplete="new-password">
          <button type="button" class="pwd-toggle" onclick="togglePwd('password',this)" aria-label="Show password">
            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
          </button>
        </div>
      </div>
      <div class="field">
        <label class="label" for="confirm_password">Confirm password</label>
        <div class="pwd-field">
          <input class="input" type="password" id="confirm_password" name="confirm_password"
                 placeholder="Repeat your new password" required autocomplete="new-password">
          <button type="button" class="pwd-toggle" onclick="togglePwd('confirm_password',this)" aria-label="Show password">
            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Save new password</button>
    </form>
    <?php endif; ?>

    <p class="text-sm muted text-center">Remembered it? <a href="login.php" class="sub-link">Log in</a></p>

  </div>
</div>

<?php include '../includes/footer.php'; ?>
