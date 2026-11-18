<?php
$pageTitle = "My Hostel";
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['student_id'])) { header("Location: login.php"); exit(); }

$sid = intval($_SESSION['student_id']);
$error = $success = '';

$stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
$stmt->bind_param("i",$sid); $stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student['school_fees_paid'])
    $error = 'Your school fees have not been verified yet. Visit the Registrar office to get verified before applying.';

$appStmt = $conn->prepare("SELECT ha.*, hr.room_number, hr.block, hr.bed_space FROM hostel_applications ha LEFT JOIN hostel_rooms hr ON ha.room_id = hr.id WHERE ha.student_id=? AND ha.status!='revoked' LIMIT 1");
$appStmt->bind_param("i",$sid); $appStmt->execute();
$existing = $appStmt->get_result()->fetch_assoc();

$rooms = $conn->prepare("SELECT * FROM hostel_rooms WHERE status='available' AND gender=? ORDER BY block,room_number");
$rooms->bind_param("s",$student['gender']); $rooms->execute();
$availableRooms = $rooms->get_result();
$roomCount = $availableRooms->num_rows;

if ($_SERVER['REQUEST_METHOD']==='POST' && !$error && !$existing) {
    $room_id = intval($_POST['room_id']??0);
    if (!$room_id) { $error='Please select a room.'; }
    else {
        $rc = $conn->prepare("SELECT id FROM hostel_rooms WHERE id=? AND status='available' AND gender=?");
        $rc->bind_param("is",$room_id,$student['gender']); $rc->execute(); $rc->store_result();
        if ($rc->num_rows===0) { $error='That room is no longer available. Please choose another.'; }
        else {
            $ins = $conn->prepare("INSERT INTO hostel_applications (student_id,room_id) VALUES (?,?)");
            $ins->bind_param("ii",$sid,$room_id);
            if ($ins->execute()) {
                $upd = $conn->prepare("UPDATE hostel_rooms SET status='occupied' WHERE id=?");
                $upd->bind_param("i",$room_id); $upd->execute();
                $success = 'Application submitted! The DSA will review and notify you by email.';
                $existing = ['status'=>'pending'];
            } else { $error='Submission failed. Please try again.'; }
        }
    }
}
$pageCss = ['student.css'];
include '../includes/header.php';
?>

<?php include 'sidebar.php'; ?>

<div class="page-head page-head-row">
  <div>
    <h1>My hostel</h1>
    <p class="sub">KRK Hall &middot; <?= htmlspecialchars($student['academic_session']) ?> session</p>
  </div>
  <?php if ($existing && $existing['status']): $sc = ['pending'=>'warning','granted'=>'success'][$existing['status']] ?? 'neutral'; ?>
  <span class="badge badge-<?= $sc ?>"><?= $existing['status']==='granted' ? ($existing['hostel_fee_paid']??false ? 'Confirmed' : 'Room granted') : ucfirst($existing['status']) ?></span>
  <?php endif; ?>
</div>

<?php if ($error): ?>
<div class="alert alert-danger mb-3"><svg class="alert-icon"><use href="#icon-alert"></use></svg><?= $error ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success mb-3"><svg class="alert-icon"><use href="#icon-check"></use></svg><?= $success ?></div>
<?php endif; ?>

<?php if ($existing && !$success): ?>

  <?php if ($existing['status']==='granted'): ?>
  <div class="alert alert-success mb-3">
    <svg class="alert-icon"><use href="#icon-check"></use></svg>
    <div>
      <strong>Your room is active.</strong>
      <p class="text-sm">The Dean of Student Affairs granted you Block <?= htmlspecialchars($existing['block']) ?> &middot; <?= htmlspecialchars($existing['room_number']) ?>. <?= $existing['hostel_fee_paid'] ? 'Your bed space is confirmed.' : 'Pay the hostel fee to confirm your bed space.' ?></p>
    </div>
  </div>
  <?php else: ?>
  <div class="alert alert-warning mb-3">
    <svg class="alert-icon"><use href="#icon-clock"></use></svg>
    <div>
      <strong>Waiting for the Dean of Student Affairs.</strong>
      <p class="text-sm">You applied for Block <?= htmlspecialchars($existing['block'] ?? '') ?> &middot; <?= htmlspecialchars($existing['room_number'] ?? '') ?>. We will email you once a room is granted.</p>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-head"><h3>Your room</h3></div>
    <div class="info-tiles">
      <div class="info-tile"><span class="tile-label">Hostel</span><span class="tile-value">KRK Hall</span></div>
      <div class="info-tile"><span class="tile-label">Block</span><span class="tile-value">Block <?= htmlspecialchars($existing['block'] ?? '—') ?></span></div>
      <div class="info-tile"><span class="tile-label">Room</span><span class="tile-value"><?= htmlspecialchars($existing['room_number'] ?? '—') ?></span></div>
      <div class="info-tile"><span class="tile-label">Beds</span><span class="tile-value"><?= htmlspecialchars($existing['bed_space'] ?? '—') ?></span></div>
    </div>

    <h4 class="mt-5 mb-3">Checklist</h4>
    <div class="kv">
      <div class="kv-row"><span class="kv-label">Account activated</span><span class="kv-value"><?= $student['is_activated'] ? 'Done' : 'Pending' ?></span></div>
      <div class="kv-row"><span class="kv-label">Minimum school fees</span><span class="kv-value"><?= $student['school_fees_paid'] ? 'Paid' : 'Not paid' ?></span></div>
      <div class="kv-row"><span class="kv-label">Room granted by the DSA</span><span class="kv-value"><?= $existing['status']==='granted' && !empty($existing['updated_at']) ? date('d M Y', strtotime($existing['updated_at'])) : 'Pending' ?></span></div>
      <div class="kv-row"><span class="kv-label">Hostel fee</span><span class="kv-value"><?= $existing['hostel_fee_paid'] ? 'Paid' : 'Not paid yet' ?></span></div>
      <div class="kv-row"><span class="kv-label">Allocation slip</span><span class="kv-value"><?= $existing['hostel_fee_paid'] ? 'Ready' : 'After hostel fee' ?></span></div>
    </div>

    <?php if ($existing['status']==='granted' && !$existing['hostel_fee_paid']): ?>
      <a href="payment.php" class="btn btn-dark mt-5">Pay hostel fee</a>
    <?php elseif ($existing['hostel_fee_paid']): ?>
      <a href="allocation_letter.php" class="btn btn-dark mt-5">View allocation slip</a>
    <?php endif; ?>

    <?php if ($existing['status']==='granted'): ?>
      <p class="hint mt-4">Your preferred room can no longer be changed because a room has been granted.</p>
    <?php endif; ?>
  </div>

<?php elseif (!$error): ?>

  <p id="roomAvailBadge" class="avail-badge muted mb-4">Checking available rooms&hellip;</p>

  <div class="card">
    <div class="card-head">
      <h3>Available rooms</h3>
      <span class="text-sm muted"><?= ucfirst($student['gender']) ?> rooms only</span>
    </div>

    <?php if ($roomCount === 0): ?>
    <div class="alert alert-warning">
      <svg class="alert-icon"><use href="#icon-alert"></use></svg>
      No rooms are currently available for <?= strtolower($student['gender']) ?> students. Please check back later.
    </div>
    <?php else: ?>
    <form method="POST" id="roomAppForm">
      <div class="room-grid">
        <?php
        $availableRooms->data_seek(0);
        while ($room = $availableRooms->fetch_assoc()):
        ?>
        <label class="room-option">
          <input type="radio" name="room_id" value="<?= $room['id'] ?>" required>
          <div class="room-option-card">
            <strong>Block <?= htmlspecialchars($room['block']) ?> &middot; <?= htmlspecialchars($room['room_number']) ?></strong>
            <div class="beds">
              <?php for ($b=0; $b<$room['bed_space']; $b++): ?><span class="bed bed-free"></span><?php endfor; ?>
            </div>
            <p class="text-sm muted"><?= $room['bed_space'] ?> of <?= $room['bed_space'] ?> beds free</p>
          </div>
        </label>
        <?php endwhile; ?>
      </div>
      <p class="hint mt-4 mb-3">Only <?= strtolower($student['gender']) ?> rooms are shown. Click a card to select it, then submit.</p>
      <button type="submit" class="btn btn-dark" onclick="return confirm('Submit hostel room application?')">Register for this room</button>
    </form>
    <?php endif; ?>
  </div>
<?php endif; ?>

      </div><!-- /.portal-content -->
    </div><!-- /.portal-main -->
  </div><!-- /.portal -->

<script>
(function(){
  const badge = document.getElementById('roomAvailBadge');
  if (!badge) return;
  const base = (document.querySelector('meta[name="base_url"]')||{}).content || '';
  const gender = '<?= addslashes($student['gender']) ?>';
  fetch(base+'/ajax/room_count.php?gender='+encodeURIComponent(gender))
    .then(r=>r.json())
    .then(d=>{
      if(d.count>0){
        badge.innerHTML='<span class="badge badge-success">'+d.count+' room'+(d.count!==1?'s':'')+' available</span><span>for '+gender+' students</span>';
      } else {
        badge.innerHTML='<span class="badge badge-danger">No rooms available</span><span>for '+gender+' students right now</span>';
      }
    }).catch(()=>{ badge.textContent=''; });
})();
</script>
<?php include '../includes/footer.php'; ?>
