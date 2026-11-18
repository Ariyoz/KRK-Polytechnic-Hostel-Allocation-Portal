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
      <h1>Staff login</h1>
      <p>For the Dean of Student Affairs, hall representatives, Registrar and ICT staff who manage hostel applications and allocation.</p>

      <div class="role-list">
        <div class="role-list-item"><span class="badge badge-neutral">DSA &middot; Rep &middot; Registrar</span> Applications and allocation</div>
        <div class="role-list-item"><span class="badge badge-neutral">ICT Admin</span> Rooms, users, settings</div>
      </div>
    </div>

    <p class="auth-foot-note">&copy; <?= date('Y') ?> KRK Polytechnic, Lagos &mdash; Staff access only</p>
  </div>

  <div class="auth-split-main">
    <div class="auth-split-card stack">

      <div>
        <h2>Welcome back</h2>
        <p class="muted">Log in to the hostel admin portal.</p>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-danger"><span><?= htmlspecialchars($error) ?></span></div>
      <?php endif; ?>

      <form method="POST" class="stack">
        <div class="field">
          <label class="label" for="adminEmail">Staff email</label>
          <input class="input" type="email" id="adminEmail" name="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="you@krkpoly.edu.ng" required autofocus autocomplete="email">
        </div>

        <div class="field">
          <label class="label" for="adminPwd">Password</label>
          <div class="pwd-field">
            <input class="input" type="password" id="adminPwd" name="password"
                   placeholder="Your password" required autocomplete="current-password">
            <button type="button" class="pwd-toggle" onclick="togglePwd('adminPwd',this)" aria-label="Show password">
              <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-dark btn-block">Log in</button>
      </form>

      <p class="text-sm muted">Not staff? <a href="<?= BASE_URL ?>/student/login.php" class="sub-link">Student login</a></p>

    </div>
  </div>

</div>

<?php include '../includes/footer.php'; ?>
