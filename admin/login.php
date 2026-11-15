<?php
$pageTitle = "Admin Login";
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['admin_id'])) { header("Location: dashboard.php"); exit(); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pwd   = $_POST['password'] ?? '';
    if (!$email || !$pwd) { $error = 'Please enter your email and password.'; }
    else {
        $stmt = $conn->prepare("SELECT id,full_name,email,password,role FROM admins WHERE email=?");
        $stmt->bind_param("s",$email); $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            if (password_verify($pwd,$row['password'])) {
                $_SESSION['admin_id']   = $row['id'];
                $_SESSION['admin_name'] = $row['full_name'];
                $_SESSION['admin_role'] = $row['role'];
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

      <div class="funaab-school-id">
        <div class="funaab-school-abbr">KRK</div>
        <div class="funaab-school-name">KRK Polytechnic</div>
        <div class="funaab-school-loc">Lagos, Nigeria</div>
      </div>

      <div class="funaab-portal-title">
        <h1>Admin<br>Control Panel</h1>
        <p>Role-Based &middot; Secure &middot; Monitored</p>
      </div>

      <ul class="funaab-features">
        <li><i class="fa fa-check-circle"></i> DSA — Grant &amp; revoke allocations</li>
        <li><i class="fa fa-check-circle"></i> Registrar — Verify school fees</li>
        <li><i class="fa fa-check-circle"></i> ICT — Manage rooms &amp; settings</li>
        <li><i class="fa fa-check-circle"></i> All roles — Reports &amp; email logs</li>
      </ul>

      <div class="funaab-left-footer">
        &copy; <?= date('Y') ?> KRK Polytechnic &mdash; Admin Access Only
      </div>
    </div>
  </div>

  <!-- ══ RIGHT: Admin login form ══ -->
  <div class="funaab-right">
    <div class="funaab-form-wrap">

      <!-- Back button -->
      <a href="<?= BASE_URL ?>/index.php" class="funaab-back-btn">
        <i class="fa fa-arrow-left"></i> Back to Home
      </a>

      <div class="funaab-form-header">
        <h2>Welcome Back!</h2>
        <p>Login to your admin account</p>
      </div>

      <!-- Role tabs -->
      <div class="funaab-role-tabs">
        <a href="<?= BASE_URL ?>/student/login.php" class="funaab-role-tab">Student</a>
        <a href="login.php" class="funaab-role-tab active">Admin</a>
      </div>

      <?php if ($error): ?>
      <div class="funaab-alert funaab-alert-error">
        <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" class="funaab-form">
        <div class="funaab-field">
          <label for="adminEmail"><i class="fa fa-envelope"></i> Admin Email</label>
          <input type="email" id="adminEmail" name="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="admin@krkpoly.edu.ng" required autofocus autocomplete="email">
        </div>

        <div class="funaab-field">
          <label for="adminPwd"><i class="fa fa-lock"></i> Password</label>
          <div class="funaab-pwd-wrap">
            <input type="password" id="adminPwd" name="password"
                   placeholder="Enter your password" required autocomplete="current-password">
            <button type="button" onclick="togglePwd('adminPwd',this)" aria-label="Toggle">
              <i class="fa fa-eye"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="funaab-submit-btn">
          <i class="fa fa-sign-in-alt"></i> Login
        </button>
      </form>

      <div class="funaab-info-hint">
        <i class="fa fa-info-circle"></i>
        <span>Default: <code>dsa@krkpoly.edu.ng</code> / <code>Admin@2025</code></span>
      </div>

      <p class="funaab-register-link">
        Not an admin? <a href="<?= BASE_URL ?>/student/login.php">Student Login</a>
      </p>

    </div>
  </div>

</div>

<?php include '../includes/footer.php'; ?>
