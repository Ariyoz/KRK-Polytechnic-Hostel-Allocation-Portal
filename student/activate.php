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
            $message = 'Your account is already activated. Please login.';
            $type    = 'info';
        } else {
            $upd = $conn->prepare("UPDATE students SET is_activated=1, activation_token=NULL WHERE id=?");
            $upd->bind_param("i", $row['id']);
            $upd->execute();
            $message = 'Your account has been successfully activated! You can now login.';
            $type    = 'success';
        }
    } else {
        $message = 'Invalid or expired activation link.';
    }
} else {
    $message = 'No activation token provided.';
}

include '../includes/header.php';
?>

<div class="container-sm">
    <div class="card" style="margin-top:60px;">
        <div class="card-header"><i class="fa fa-check-circle"></i> Account Activation</div>
        <div class="card-body" style="text-align:center; padding:40px;">
            <div class="alert alert-<?= $type ?>"><?= $message ?></div>
            <a href="login.php" class="btn btn-primary">Go to Login</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
