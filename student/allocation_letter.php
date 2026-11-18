<?php
$pageTitle = "Allocation Letter";
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['student_id'])) { header("Location: login.php"); exit(); }

$sid = intval($_SESSION['student_id']);

$stmt = $conn->prepare("
    SELECT s.*, f.name AS faculty_name, d.name AS dept_name
    FROM students s
    LEFT JOIN faculties f ON s.faculty_id = f.id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $sid);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

$appStmt = $conn->prepare("
    SELECT ha.*, hr.room_number, hr.block, hr.gender AS room_gender
    FROM hostel_applications ha
    LEFT JOIN hostel_rooms hr ON ha.room_id = hr.id
    WHERE ha.student_id = ? AND ha.status = 'granted' AND ha.hostel_fee_paid = 1
    ORDER BY ha.applied_at DESC LIMIT 1
");
$appStmt->bind_param("i", $sid);
$appStmt->execute();
$app = $appStmt->get_result()->fetch_assoc();

$pageCss = ['student.css'];
include '../includes/header.php';
?>

<?php include 'sidebar.php'; ?>

<div class="page-head-row no-print mb-4">
  <a href="dashboard.php" class="btn btn-outline btn-sm">&larr; Back to dashboard</a>
  <?php if ($app): ?>
  <button class="btn btn-dark btn-sm" onclick="window.print()">Print slip</button>
  <?php endif; ?>
</div>

<?php if (!$app): ?>
<div class="alert alert-warning mb-3">
  <svg class="alert-icon"><use href="#icon-alert"></use></svg>
  <div>
    <strong>Not available yet.</strong>
    <p class="text-sm">Your allocation slip unlocks once your room is granted and your hostel fee is paid.</p>
  </div>
</div>
<a href="application.php" class="btn btn-dark">Go to my hostel</a>

<?php else: ?>

<div class="card slip" id="allocationLetter">
  <div class="slip-head">
    <div>
      <h2>KRK Polytechnic, Lagos</h2>
      <p class="text-sm muted">Office of the Dean of Student Affairs</p>
      <span class="eyebrow mt-2">Hostel allocation slip &middot; <?= htmlspecialchars($student['academic_session']) ?></span>
    </div>
    <div class="slip-photo">Passport photo</div>
  </div>

  <hr>

  <div class="info-tiles">
    <div class="info-tile"><span class="tile-label">Hostel</span><span class="tile-value">KRK Hall</span></div>
    <div class="info-tile"><span class="tile-label">Block</span><span class="tile-value">Block <?= htmlspecialchars($app['block']) ?></span></div>
    <div class="info-tile"><span class="tile-label">Room</span><span class="tile-value"><?= htmlspecialchars($app['room_number']) ?></span></div>
    <div class="info-tile"><span class="tile-label">Status</span><span class="tile-value is-positive">Confirmed</span></div>
  </div>

  <div class="kv-grid mt-5">
    <div class="kv-grid-cell"><span class="kv-label">Name</span><span class="kv-value"><?= htmlspecialchars(strtoupper($student['surname']).', '.$student['firstname'].($student['othername']?' '.$student['othername']:'')) ?></span></div>
    <div class="kv-grid-cell"><span class="kv-label">Matric number</span><span class="kv-value"><?= htmlspecialchars($student['matric_number']) ?></span></div>
    <div class="kv-grid-cell"><span class="kv-label">Faculty</span><span class="kv-value"><?= htmlspecialchars($student['faculty_name']) ?></span></div>
    <div class="kv-grid-cell"><span class="kv-label">Department</span><span class="kv-value"><?= htmlspecialchars($student['dept_name']) ?></span></div>
    <div class="kv-grid-cell"><span class="kv-label">Level</span><span class="kv-value"><?= htmlspecialchars($student['level']) ?></span></div>
    <div class="kv-grid-cell"><span class="kv-label">Gender</span><span class="kv-value"><?= ucfirst($student['gender']) ?></span></div>
    <div class="kv-grid-cell"><span class="kv-label">Phone</span><span class="kv-value"><?= htmlspecialchars($student['phone']) ?></span></div>
    <div class="kv-grid-cell"><span class="kv-label">Email</span><span class="kv-value"><?= htmlspecialchars($student['email']) ?></span></div>
    <div class="kv-grid-cell"><span class="kv-label">Hostel fee</span><span class="kv-value">Paid &middot; Ref <?= htmlspecialchars($app['txn_ref'] ?: '—') ?></span></div>
    <div class="kv-grid-cell"><span class="kv-label">Granted on</span><span class="kv-value"><?= date('d M Y', strtotime($app['updated_at'])) ?></span></div>
  </div>

  <h4 class="mt-5 mb-2">Conditions</h4>
  <ol class="slip-conditions">
    <li>Present this slip with your student ID card at the porter's lodge.</li>
    <li>The allocation is personal and cannot be transferred or sold.</li>
    <li>It may be revoked by the Dean of Student Affairs or the Registrar for breach of hostel rules.</li>
  </ol>

  <div class="slip-signatures">
    <div><div class="sig-line"></div>Student's signature</div>
    <div><div class="sig-line"></div>Hall warden</div>
    <div><div class="sig-line"></div>Dean of Student Affairs</div>
  </div>

  <div class="slip-footer">
    <span>Ref: KRK/HOSTEL/<?= strtoupper(substr(md5($sid.$app['id']), 0, 8)) ?></span>
    <span>Generated <?= date('d M Y, g:i a') ?></span>
  </div>
</div>

<?php endif; ?>

      </div><!-- /.portal-content -->
    </div><!-- /.portal-main -->
  </div><!-- /.portal -->
<?php include '../includes/footer.php'; ?>
