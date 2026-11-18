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
$pageCss  = ['auth.css'];
$noHeader = true;
include '../includes/header.php';
?>

<div class="auth-split">

  <div class="auth-split-panel">
    <a href="<?= BASE_URL ?>/index.php" class="brand">
      <svg class="brand-mark" viewBox="0 0 40 46" aria-hidden="true">
        <path d="M20 2 4 8v14c0 12 7 19 16 22 9-3 16-10 16-22V8Z" fill="#102a4a" stroke="#e8a317" stroke-width="2"/>
        <path d="M13 24l7-6 7 6M15 22v8h10v-8" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linejoin="round"/>
      </svg>
      <span class="brand-text">
        <strong>KRK Polytechnic, Lagos</strong>
        <small>Hostel Allocation Portal</small>
      </span>
    </a>

    <div>
      <h1>Welcome back</h1>
      <p>Log in to continue to your hostel portal &mdash; check your application, pay your fees and download your allocation slip.</p>

      <div class="role-list">
        <div class="role-list-item"><span class="badge badge-neutral">Student</span> Dashboard, school fees, my hostel</div>
      </div>
    </div>

    <p class="auth-foot-note">&copy; <?= date('Y') ?> KRK Polytechnic, Lagos</p>
  </div>

  <div class="auth-split-main">
    <div class="auth-split-card stack">

      <div>
        <h2>Welcome back</h2>
        <p class="muted">Log in to continue to your hostel portal.</p>
      </div>

      <div class="alert alert-info">
        <span>Just activated your account? Log in with the email you registered with.</span>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-danger">
        <span>
          <?= htmlspecialchars($error) ?>
          <?php if (strpos($error, 'not activated') !== false): ?>
            <a href="resend_activation.php" class="sub-link">Resend link</a>
          <?php endif; ?>
        </span>
      </div>
      <?php endif; ?>

      <form method="POST" class="stack">
        <div class="field">
          <label class="label" for="email">Email address</label>
          <input class="input" type="email" id="email" name="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="your@email.com" required autofocus autocomplete="email">
        </div>

        <div class="field">
          <div class="field-label-row">
            <label class="label" for="loginPwd">Password</label>
            <a href="forgot_password.php" class="sub-link">Forgot password?</a>
          </div>
          <div class="pwd-field">
            <input class="input" type="password" id="loginPwd" name="password"
                   placeholder="Your password" required autocomplete="current-password">
            <button type="button" class="pwd-toggle" onclick="togglePwd('loginPwd',this)" aria-label="Show password">
              <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-dark btn-block">Log in</button>
      </form>

      <p class="text-sm muted">
        Not registered for the hostel yet? <a href="register.php" class="sub-link">Start hostel registration</a>
      </p>

    </div>
  </div>

</div>

<?php include '../includes/footer.php'; ?>
