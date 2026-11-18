<?php
$pageTitle = "Student Dashboard";
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['student_id'])) { header("Location: login.php"); exit(); }

$sid = intval($_SESSION['student_id']);
$stmt = $conn->prepare("SELECT s.*,f.name AS faculty_name,d.name AS dept_name FROM students s LEFT JOIN faculties f ON s.faculty_id=f.id LEFT JOIN departments d ON s.department_id=d.id WHERE s.id=?");
$stmt->bind_param("i",$sid); $stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

$aStmt = $conn->prepare("SELECT ha.*,hr.room_number,hr.block FROM hostel_applications ha LEFT JOIN hostel_rooms hr ON ha.room_id=hr.id WHERE ha.student_id=? ORDER BY ha.applied_at DESC LIMIT 1");
$aStmt->bind_param("i",$sid); $aStmt->execute();
$app = $aStmt->get_result()->fetch_assoc();

// Recent account emails, shown as notifications
$notifStmt = $conn->prepare("SELECT subject, sent_at FROM email_logs WHERE recipient_email=? ORDER BY sent_at DESC LIMIT 5");
$notifStmt->bind_param("s", $student['email']);
$notifStmt->execute();
$notifications = $notifStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageCss = ['student.css'];
include '../includes/header.php';

$hostelFeeFormatted = '₦'.number_format((int)setting($conn, 'hostel_fee_amount', '25000'));

if (!$app)                                        { $hostelBadge = ['neutral', 'No hostel application yet']; }
elseif ($app['status'] === 'revoked')              { $hostelBadge = ['danger',  'Hostel: allocation revoked']; }
elseif ($app['status'] === 'granted' && $app['hostel_fee_paid']) { $hostelBadge = ['success', 'Hostel: confirmed']; }
elseif ($app['status'] === 'granted')              { $hostelBadge = ['warning', 'Hostel: room granted']; }
else                                                { $hostelBadge = ['warning', 'Hostel: pending allocation']; }

// Overview step cards
$steps = [
    [
        'tag'   => 'Step 1',
        'title' => 'Account',
        'state' => $student['is_activated'] ? 'done' : 'current',
        'desc'  => $student['is_activated'] ? 'Activated' : 'Not activated yet',
    ],
    [
        'tag'   => 'Step 2',
        'title' => 'School fees',
        'state' => $student['school_fees_paid'] ? 'done' : ($student['is_activated'] ? 'current' : 'locked'),
        'desc'  => $student['school_fees_paid'] ? 'Verified by the Registrar' : 'Not yet verified',
    ],
    [
        'tag'   => 'Step 3',
        'title' => 'Room allocation',
        'state' => ($app && $app['status'] === 'granted') ? 'done' : ($student['school_fees_paid'] ? 'current' : 'locked'),
        'desc'  => ($app && $app['status'] === 'granted') ? 'Granted by the DSA' : ($app ? 'Waiting for the DSA' : ($student['school_fees_paid'] ? 'You can apply now' : 'Opens after school fees are verified')),
    ],
    [
        'tag'   => 'Step 4',
        'title' => 'Hostel fee',
        'state' => ($app && $app['hostel_fee_paid']) ? 'done' : (($app && $app['status'] === 'granted') ? 'current' : 'locked'),
        'desc'  => ($app && $app['hostel_fee_paid']) ? "Paid $hostelFeeFormatted" : (($app && $app['status'] === 'granted') ? "Pay $hostelFeeFormatted to confirm" : 'Opens when your room is granted'),
    ],
];
$stepIcon = ['done' => 'check', 'current' => 'clock', 'locked' => 'lock'];

// Vertical application timeline
$timeline = [
    ['Registered and activated', (bool)$student['is_activated'], 'Your account on the portal.'],
    ['Minimum school fees paid', (bool)$student['school_fees_paid'], 'Needed before a room can be granted.'],
    ['Room allocation by the DSA', (bool)($app && $app['status'] === 'granted'),
        $app ? ($app['status'] === 'granted'
            ? 'Block '.htmlspecialchars($app['block']).' &middot; '.htmlspecialchars($app['room_number'])
            : 'Applied for Block '.htmlspecialchars($app['block']).' &middot; '.htmlspecialchars($app['room_number']).'. Waiting for the DSA.')
          : 'Apply for a room once your fees are verified.'],
    ["Pay hostel fee ($hostelFeeFormatted)", (bool)($app && $app['hostel_fee_paid']),
        ($app && $app['status'] === 'granted') ? 'Pay to confirm your bed space.' : 'Available after your room is granted.'],
    ['Print allocation slip', (bool)($app && $app['hostel_fee_paid']), 'Present it at the porter&rsquo;s lodge.'],
];
$firstUndone = null;
foreach ($timeline as $i => $t) { if (!$t[1]) { $firstUndone = $i; break; } }
?>

<?php include 'sidebar.php'; ?>

<div class="page-head page-head-row">
  <div>
    <h1>Welcome back, <?= htmlspecialchars($student['firstname']) ?></h1>
    <p class="sub"><?= htmlspecialchars($student['matric_number']) ?> &middot; <?= htmlspecialchars($student['dept_name']) ?> &middot; <?= htmlspecialchars($student['level']) ?> &middot; <?= ucfirst($student['gender']) ?></p>
  </div>
  <span class="badge badge-<?= $hostelBadge[0] ?>"><?= $hostelBadge[1] ?></span>
</div>

<?php if (!$student['school_fees_paid']): ?>
<div class="alert alert-warning mb-3">
  <svg class="alert-icon"><use href="#icon-alert"></use></svg>
  <div>
    <strong>School fees not yet verified.</strong>
    <p class="text-sm">Visit the Registrar's office to have your payment confirmed before you can apply for a hostel room.</p>
  </div>
</div>
<?php endif; ?>

<?php if ($app && $app['status'] === 'revoked'): ?>
<div class="alert alert-danger mb-3">
  <svg class="alert-icon"><use href="#icon-alert"></use></svg>
  <div>
    <strong>Your room allocation was revoked.</strong>
    <p class="text-sm">You may re-apply for a room if one is still available.</p>
  </div>
</div>
<?php endif; ?>

<div class="step-card-grid">
  <?php foreach ($steps as $s): ?>
  <div class="step-card is-<?= $s['state'] ?>">
    <div class="step-card-top">
      <div class="step-card-icon"><svg class="icon"><use href="#icon-<?= $stepIcon[$s['state']] ?>"></use></svg></div>
      <span class="step-card-tag"><?= $s['tag'] ?><?= $s['state'] === 'current' ? ' &middot; now' : '' ?></span>
    </div>
    <h4><?= $s['title'] ?></h4>
    <p><?= $s['desc'] ?></p>
  </div>
  <?php endforeach; ?>
</div>

<div class="dash-columns">
  <div class="card">
    <div class="card-head">
      <h3>Your hostel application</h3>
      <a href="application.php" class="card-action">Open my hostel</a>
    </div>
    <div class="timeline">
      <?php foreach ($timeline as $i => [$label, $done, $desc]):
          $isCurrent = !$done && $i === $firstUndone;
      ?>
      <div class="timeline-item <?= $done ? 'is-done' : ($isCurrent ? 'is-current' : '') ?>">
        <div class="timeline-dot">
          <?php if ($done): ?><svg class="icon"><use href="#icon-check"></use></svg><?php else: ?><?= $i + 1 ?><?php endif; ?>
        </div>
        <h4><?= $label ?></h4>
        <p><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-head"><h3>Quick actions</h3></div>
      <div class="quick-actions">
        <?php if (!$student['school_fees_paid']): ?>
          <button class="btn btn-outline" disabled>Apply for a room (fees not verified)</button>
        <?php elseif (!$app): ?>
          <a href="application.php" class="btn btn-dark">Apply for a hostel room</a>
        <?php elseif ($app['status'] === 'granted' && !$app['hostel_fee_paid']): ?>
          <a href="payment.php" class="btn btn-dark">Pay hostel fee</a>
        <?php elseif ($app['status'] === 'granted' && $app['hostel_fee_paid']): ?>
          <a href="allocation_letter.php" class="btn btn-dark">View allocation slip</a>
        <?php elseif ($app['status'] === 'revoked'): ?>
          <a href="application.php" class="btn btn-dark">Re-apply for a room</a>
        <?php else: ?>
          <a href="application.php" class="btn btn-outline">View my application</a>
        <?php endif; ?>
        <a href="profile.php" class="btn btn-outline">Edit profile</a>
      </div>
    </div>

    <?php if ($notifications): ?>
    <div class="card">
      <div class="card-head"><h3>Notifications</h3></div>
      <div class="notif-list">
        <?php foreach ($notifications as $n): ?>
        <div class="notif-item">
          <span class="badge badge-neutral">Email</span>
          <p><?= htmlspecialchars($n['subject']) ?></p>
          <time><?= date('j M, g:i a', strtotime($n['sent_at'])) ?></time>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

      </div><!-- /.portal-content -->
    </div><!-- /.portal-main -->
  </div><!-- /.portal -->
<?php include '../includes/footer.php'; ?>
