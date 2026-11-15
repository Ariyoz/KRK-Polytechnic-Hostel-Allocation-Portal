<?php
$pageTitle = "Student Login";
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['student_id'])) { header("Location: dashboard.php"); exit(); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pwd   = $_POST['password'] ?? '';
    if (!$email || !$pwd) { $error = 'Please enter your email and password.'; }
    else {
        $stmt = $conn->prepare("SELECT id,surname,firstname,password,is_activated FROM students WHERE email=?");
        $stmt->bind_param("s",$email); $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            if (!$row['is_activated']) $error = 'Account not activated. Check your email for the activation link.';
            elseif (password_verify($pwd,$row['password'])) {
                $_SESSION['student_id']   = $row['id'];
                $_SESSION['student_name'] = $row['firstname'].' '.$row['surname'];
                header("Location: dashboard.php"); exit();
            } else $error = 'Invalid email or password.';
        } else $error = 'Invalid email or password.';
    }
}
$noHeader = true;
include '../includes/header.php';
?>

<div class="funaab-auth">

  <!-- ══ LEFT: School branding panel ══ -->
  <div class="funaab-left">
    <div class="funaab-left-bg"></div>
    <div class="funaab-left-overlay"></div>

    <div class="funaab-left-inner">

      <!-- School identity — text only, no logo -->
      <div class="funaab-school-id">
        <div class="funaab-school-abbr">KRK</div>
        <div class="funaab-school-name">KRK Polytechnic</div>
        <div class="funaab-school-loc">Lagos, Nigeria</div>
      </div>

      <!-- Portal title -->
      <div class="funaab-portal-title">
        <h1>Hostel Management<br>Portal</h1>
        <p>Secure &middot; Simple &middot; Efficient</p>
      </div>

      <!-- Feature bullets -->
      <ul class="funaab-features">
        <li><i class="fa fa-check-circle"></i> Real-time room allocation</li>
        <li><i class="fa fa-check-circle"></i> Online hostel fee payment</li>
        <li><i class="fa fa-check-circle"></i> Download allocation letter</li>
        <li><i class="fa fa-check-circle"></i> Instant email notifications</li>
      </ul>

      <div class="funaab-left-footer">
        &copy; <?= date('Y') ?> KRK Polytechnic &mdash; All rights reserved
      </div>
    </div>
  </div>

  <!-- ══ RIGHT: Login form ══ -->
  <div class="funaab-right">
    <div class="funaab-form-wrap">

      <!-- Back button -->
      <a href="<?= BASE_URL ?>/index.php" class="funaab-back-btn">
        <i class="fa fa-arrow-left"></i> Back to Home
      </a>

      <div class="funaab-form-header">
        <h2>Welcome Back!</h2>
        <p>Login to your student account</p>
      </div>

      <!-- Role tabs -->
      <div class="funaab-role-tabs">
        <a href="login.php" class="funaab-role-tab active">Student</a>
        <a href="<?= BASE_URL ?>/admin/login.php" class="funaab-role-tab">Admin</a>
      </div>

      <?php if ($error): ?>
      <div class="funaab-alert funaab-alert-error">
        <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" class="funaab-form">
        <div class="funaab-field">
          <label for="email"><i class="fa fa-envelope"></i> Email Address</label>
          <input type="email" id="email" name="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="your@email.com" required autofocus autocomplete="email">
        </div>

        <div class="funaab-field">
          <div class="funaab-field-row">
            <label for="loginPwd"><i class="fa fa-lock"></i> Password</label>
            <a href="forgot_password.php" class="funaab-forgot">Forgot Password?</a>
          </div>
          <div class="funaab-pwd-wrap">
            <input type="password" id="loginPwd" name="password"
                   placeholder="Enter your password" required autocomplete="current-password">
            <button type="button" onclick="togglePwd('loginPwd',this)" aria-label="Toggle">
              <i class="fa fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="funaab-remember">
          <label><input type="checkbox" name="remember"> Remember Me</label>
        </div>

        <button type="submit" class="funaab-submit-btn">
          <i class="fa fa-sign-in-alt"></i> Login
        </button>
      </form>

      <p class="funaab-register-link">
        Don't have an account? <a href="register.php">Register here</a>
      </p>

    </div>
  </div>

</div>

<?php include '../includes/footer.php'; ?>
