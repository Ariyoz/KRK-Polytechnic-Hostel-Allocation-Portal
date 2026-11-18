<?php
$pageTitle = "Pay Hostel Fee";
require_once '../config/db.php';
require_once '../config/mailer.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['student_id'])) { header("Location: login.php"); exit(); }

$sid   = intval($_SESSION['student_id']);
$error = $success = '';
$hostelFee = intval(setting($conn, 'hostel_fee_amount', '25000'));
$hostelFeeFormatted = '₦'.number_format($hostelFee, 2);

$stmt = $conn->prepare("SELECT s.*,f.name AS faculty_name FROM students s LEFT JOIN faculties f ON s.faculty_id=f.id WHERE s.id=?");
$stmt->bind_param("i",$sid); $stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

$aStmt = $conn->prepare("SELECT ha.*,hr.room_number,hr.block FROM hostel_applications ha LEFT JOIN hostel_rooms hr ON ha.room_id=hr.id WHERE ha.student_id=? LIMIT 1");
$aStmt->bind_param("i",$sid); $aStmt->execute();
$app = $aStmt->get_result()->fetch_assoc();

if (!$student['school_fees_paid'])       $error = 'You must have your school fees verified before paying hostel fees.';
elseif (!$app)                           $error = 'You have not applied for hostel accommodation yet.';
elseif ($app['status']!=='granted')      $error = 'Your room has not been granted yet. Please wait for the DSA to review your application.';
elseif ($app['hostel_fee_paid'])         $success = 'Hostel fee already paid! Your accommodation registration is complete.';

if ($_SERVER['REQUEST_METHOD']==='POST' && !$error && !$success) {
    $txnRef = strtoupper(bin2hex(random_bytes(6)));
    $upd = $conn->prepare("UPDATE hostel_applications SET hostel_fee_paid=1,txn_ref=?,updated_at=NOW() WHERE student_id=?");
    $upd->bind_param("si",$txnRef,$sid);
    if ($upd->execute()) {
        $body = "<p>Dear <strong>{$student['firstname']} {$student['surname']}</strong>,</p>
                 <p>Your hostel fee of <strong>$hostelFeeFormatted</strong> has been received.</p>
                 <table style='width:100%;border-collapse:collapse;margin-top:12px;'>
                 <tr><td style='padding:8px;border:1px solid #ddd;'><strong>Ref</strong></td><td style='padding:8px;border:1px solid #ddd;'>$txnRef</td></tr>
                 <tr><td style='padding:8px;border:1px solid #ddd;'><strong>Room</strong></td><td style='padding:8px;border:1px solid #ddd;'>Block {$app['block']} — Room {$app['room_number']}</td></tr>
                 <tr><td style='padding:8px;border:1px solid #ddd;'><strong>Date</strong></td><td style='padding:8px;border:1px solid #ddd;'>".date('d M Y')."</td></tr>
                 </table>
                 <p style='margin-top:14px;'>Welcome to KRK Polytechnic Hostel! 🎉</p>";
        sendEmail($student['email'],'Hostel Fee Confirmation',emailTemplate('Payment Successful',$body));
        $success = "Payment confirmed! <strong>Ref: $txnRef</strong>. Receipt sent to your email.";
    } else {
        $error = 'Payment failed. Please try again.';
    }
}
$pageCss = ['student.css'];
include '../includes/header.php';
?>

<?php include 'sidebar.php'; ?>

<div class="page-head">
  <h1>Pay hostel fee</h1>
  <p class="sub">KRK Hall &middot; <?= htmlspecialchars($student['academic_session']) ?> session</p>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><svg class="alert-icon"><use href="#icon-alert"></use></svg><?= $error ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="card payment-success is-narrow">
  <div class="success-badge"><svg class="icon"><use href="#icon-check"></use></svg></div>
  <h3>Payment confirmed</h3>
  <p class="text-sm muted mb-3"><?= $success ?></p>
  <div class="btn-row is-center">
    <a href="allocation_letter.php" class="btn btn-dark">View allocation slip</a>
    <a href="dashboard.php" class="btn btn-outline">Dashboard</a>
  </div>
</div>

<?php elseif (!$error && $app): ?>
<div class="grid grid-2">
  <div class="card">
    <div class="card-head"><h3>Payment summary</h3></div>
    <div class="kv">
      <div class="kv-row"><span class="kv-label">Student</span><span class="kv-value"><?= htmlspecialchars($student['firstname'].' '.$student['surname']) ?></span></div>
      <div class="kv-row"><span class="kv-label">Matric no.</span><span class="kv-value"><?= htmlspecialchars($student['matric_number']) ?></span></div>
      <div class="kv-row"><span class="kv-label">Room</span><span class="kv-value">Block <?= htmlspecialchars($app['block']) ?> &middot; <?= htmlspecialchars($app['room_number']) ?></span></div>
      <div class="kv-row"><span class="kv-label">Session</span><span class="kv-value"><?= htmlspecialchars($student['academic_session']) ?></span></div>
    </div>
    <div class="fee-amount">
      <span class="tile-label">Hostel fee due</span>
      <div class="amount"><?= $hostelFeeFormatted ?></div>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Make payment</h3></div>
    <form method="POST">
      <div class="field">
        <label class="label">Payment method</label>
        <select name="payment_method" class="select">
          <option>Bank transfer / Remita</option>
          <option>Debit card (online)</option>
          <option>Mobile banking</option>
        </select>
      </div>
      <div class="field">
        <label class="label">Payer's name</label>
        <input type="text" class="input" value="<?= htmlspecialchars($student['firstname'].' '.$student['surname']) ?>" readonly>
      </div>
      <div class="alert alert-info mb-4">
        KRK Pay demo gateway &mdash; this is a simulated payment, no real money moves.
      </div>
      <button type="submit" class="btn btn-primary btn-block">Confirm &amp; pay <?= $hostelFeeFormatted ?></button>
    </form>
  </div>
</div>
<?php endif; ?>

      </div><!-- /.portal-content -->
    </div><!-- /.portal-main -->
  </div><!-- /.portal -->
<?php include '../includes/footer.php'; ?>
