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
include '../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include 'sidebar.php'; ?>
  <main class="main-content">

    <div class="page-header">
      <div class="page-header-left">
        <h1><i class="fa fa-credit-card" style="color:var(--green-600);font-size:18px;margin-right:8px;"></i>Hostel Fee Payment</h1>
        <p>Secure your room by completing your hostel fee payment</p>
      </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <!-- Success state -->
    <div style="max-width:540px;">
      <div class="alert alert-success"><i class="fa fa-check-circle"></i><?= $success ?></div>
      <div class="card">
        <div class="card-body" style="text-align:center;padding:36px 24px;">
          <div style="width:72px;height:72px;border-radius:50%;background:var(--green-50);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:30px;color:var(--green-600);">
            <i class="fa fa-check"></i>
          </div>
          <h3 style="font-family:'DM Serif Display',serif;font-weight:400;font-size:22px;color:var(--n900);margin-bottom:8px;">Registration Complete!</h3>
          <p style="color:var(--n500);font-size:14px;margin-bottom:24px;">Your room is confirmed. Download your allocation letter to collect your key.</p>
          <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
            <a href="allocation_letter.php" class="btn btn-primary"><i class="fa fa-file-alt"></i>Download Letter</a>
            <a href="dashboard.php" class="btn btn-light"><i class="fa fa-th-large"></i>Dashboard</a>
          </div>
        </div>
      </div>
    </div>

    <?php elseif (!$error && $app): ?>
    <!-- Payment form -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;max-width:900px;">

      <!-- Order summary -->
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fa fa-receipt"></i>Payment Summary</div></div>
        <div class="card-body">
          <table class="detail-table">
            <tr><td>Student Name</td><td><strong><?= htmlspecialchars($student['firstname'].' '.$student['surname']) ?></strong></td></tr>
            <tr><td>Matric No.</td><td><?= htmlspecialchars($student['matric_number']) ?></td></tr>
            <tr><td>Block</td><td><strong>Block <?= htmlspecialchars($app['block']) ?></strong></td></tr>
            <tr><td>Room</td><td><strong><?= htmlspecialchars($app['room_number']) ?></strong></td></tr>
            <tr><td>Session</td><td><?= htmlspecialchars($student['academic_session']) ?></td></tr>
          </table>
          <div style="margin-top:20px;padding:16px;background:var(--green-50);border-radius:var(--r-md);border:1px solid var(--green-100);text-align:center;">
            <div style="font-size:13px;color:var(--green-700);font-weight:600;margin-bottom:4px;">AMOUNT DUE</div>
            <div style="font-size:36px;font-weight:900;color:var(--green-700);font-family:'Outfit',sans-serif;letter-spacing:-1px;"><?= $hostelFeeFormatted ?></div>
          </div>
        </div>
      </div>

      <!-- Payment method -->
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fa fa-lock"></i>Complete Payment</div></div>
        <div class="card-body">
          <form method="POST">
            <div class="form-group">
              <label>Payment Method</label>
              <select name="payment_method" class="form-control">
                <option>Bank Transfer / Remita</option>
                <option>Debit Card (Online)</option>
                <option>Mobile Banking</option>
              </select>
            </div>
            <div class="form-group">
              <label>Payer's Name</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($student['firstname'].' '.$student['surname']) ?>" readonly>
            </div>
            <div style="padding:14px;background:var(--gold-100);border-radius:var(--r);border:1px solid var(--gold-300);margin-bottom:18px;">
              <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--n700);font-weight:500;">
                <i class="fa fa-shield-alt" style="color:var(--gold-500);"></i>
                This is a simulated payment for the demo portal.
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="font-size:15px;padding:13px;">
              <i class="fa fa-lock"></i> Confirm &amp; Pay <?= $hostelFeeFormatted ?>
            </button>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </main>
</div>
<?php include '../includes/footer.php'; ?>
