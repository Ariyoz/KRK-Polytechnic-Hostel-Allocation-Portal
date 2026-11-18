<?php
$pageTitle = "Account Activation";
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';
$type    = 'danger';

$token = trim($_GET['token'] ?? '');

if ($token) {
    $stmt = $conn->prepare("SELECT id, is_activated FROM students WHERE activation_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($row['is_activated']) {
            $message = 'Your account is already activated. Please log in.';
            $type    = 'info';
        } else {
            $upd = $conn->prepare("UPDATE students SET is_activated=1, activation_token=NULL WHERE id=?");
            $upd->bind_param("i", $row['id']);
            $upd->execute();
            $message = 'Your account is now active. Log in to pay your school fees and follow your hostel application.';
            $type    = 'success';
        }
    } else {
        $message = 'Invalid or expired activation link.';
    }
} else {
    $message = 'No activation token provided.';
}

$pageCss = ['auth.css'];
include '../includes/header.php';
?>

<div class="section">
  <div class="container auth-card-page">
    <div class="card stack text-center">
      <h1>Account activation</h1>
      <div class="alert alert-<?= $type ?>"><span><?= htmlspecialchars($message) ?></span></div>
      <a href="login.php" class="btn btn-primary">Go to login</a>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
