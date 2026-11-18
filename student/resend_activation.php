<?php
$pageTitle = "Resend Activation";
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
        $success = 'If an unactivated account with that email exists, a new activation link has been sent.';
        $stmt = $conn->prepare("SELECT id, firstname, surname, is_activated FROM students WHERE email=?");
        $stmt->bind_param("s", $email); $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();

        if ($student && !$student['is_activated']) {
            $token = bin2hex(random_bytes(32));
            $upd   = $conn->prepare("UPDATE students SET activation_token=? WHERE id=?");
            $upd->bind_param("si", $token, $student['id']); $upd->execute();
            $link = BASE_URL . "/student/activate.php?token=$token";
            $body = "<p>Dear <strong>{$student['firstname']} {$student['surname']}</strong>,</p>
                     <p>Here is your new account activation link:</p>
                     <a href='" . htmlspecialchars($link) . "' class='btn'>Activate My Account</a>
                     <p style='margin-top:14px;font-size:12px;color:#888;'>Or copy this link: $link</p>";
            sendEmail($email, 'Account Activation (Resent) — KRK Hostel Portal', emailTemplate('Activate Your Account', $body));
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
      <h1>Resend activation</h1>
      <p class="muted">Didn't get your activation email? We'll send a fresh one.</p>
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
      <button type="submit" class="btn btn-primary btn-block">Resend activation email</button>
    </form>
    <p class="text-sm muted text-center">Already activated? <a href="login.php" class="sub-link">Log in</a></p>
    <?php endif; ?>

  </div>
</div>

<?php include '../includes/footer.php'; ?>
