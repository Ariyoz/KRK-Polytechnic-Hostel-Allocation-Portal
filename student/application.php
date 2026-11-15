<?php
$pageTitle = "Hostel Application";
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

$appStmt = $conn->prepare("SELECT * FROM hostel_applications WHERE student_id=? AND status!='revoked' LIMIT 1");
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
include '../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include 'sidebar.php'; ?>
  <main class="main-content">

    <div class="page-header">
      <div class="page-header-left">
        <h1><i class="fa fa-bed" style="color:var(--green-600);font-size:18px;margin-right:8px;"></i>Hostel Application</h1>
        <p>Select an available room for the <?= htmlspecialchars($student['academic_session']) ?> session</p>
      </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fa fa-check-circle"></i><?= $success ?></div>
    <?php endif; ?>

    <?php if ($existing && !$success): ?>
    <div class="card" style="max-width:580px;">
      <div class="card-header">
        <div class="card-title"><i class="fa fa-info-circle"></i>Application Already Submitted</div>
      </div>
      <div class="card-body">
        <p style="color:var(--n600);margin-bottom:16px;">
          You have an active hostel application. Current status:
          <span class="badge badge-<?= ['pending'=>'warning','granted'=>'success'][$existing['status']]??'secondary' ?>" style="margin-left:6px;"><?= ucfirst($existing['status']) ?></span>
        </p>
        <a href="dashboard.php" class="btn btn-primary btn-sm"><i class="fa fa-th-large"></i>Go to Dashboard</a>
      </div>
    </div>

    <?php elseif (!$error): ?>

    <!-- Availability badge -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;padding:14px 18px;background:#fff;border-radius:var(--r-md);border:1px solid var(--n200);box-shadow:var(--sh-xs);">
      <div style="width:36px;height:36px;border-radius:var(--r-sm);background:var(--green-50);color:var(--green-600);display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;">
        <i class="fa fa-bed"></i>
      </div>
      <div id="roomAvailBadge" style="font-size:14px;color:var(--n500);">Checking available rooms…</div>
    </div>

    <div class="card" style="max-width:860px;">
      <div class="card-header">
        <div class="card-title"><i class="fa fa-list-ul"></i>Select Your Room</div>
        <span style="font-size:12px;color:var(--n400);"><?= ucfirst($student['gender']) ?> rooms only</span>
      </div>
      <div class="card-body">
        <?php if ($roomCount === 0): ?>
        <div class="alert alert-warning" style="margin-bottom:0;"><i class="fa fa-exclamation-triangle"></i>No rooms are currently available for <?= ucfirst($student['gender']) ?> students. Please check back later.</div>
        <?php else: ?>
        <form method="POST" id="roomAppForm">
          <div class="form-group">
            <label>Available Rooms — <?= ucfirst($student['gender']) ?></label>
            <!-- Room card picker -->
            <div class="room-picker" id="roomPicker">
              <?php
              $availableRooms->data_seek(0);
              while ($room = $availableRooms->fetch_assoc()): ?>
              <label class="room-card-label">
                <input type="radio" name="room_id" value="<?= $room['id'] ?>" required>
                <div class="room-card">
                  <div class="room-card-icon"><i class="fa fa-bed"></i></div>
                  <div class="room-card-block">Block <strong><?= htmlspecialchars($room['block']) ?></strong></div>
                  <div class="room-card-num">Room <?= htmlspecialchars($room['room_number']) ?></div>
                  <div class="room-card-gender"><span class="badge badge-<?= $room['gender']==='male'?'info':'success' ?>"><?= ucfirst($room['gender']) ?></span></div>
                </div>
              </label>
              <?php endwhile; ?>
            </div>
            <p class="form-hint"><i class="fa fa-info-circle"></i> Only <?= strtolower($student['gender']) ?> rooms are shown. Click a card to select.</p>
          </div>
          <button type="submit" class="btn btn-primary" onclick="return confirm('Submit hostel room application?')">
            <i class="fa fa-check"></i> Submit Application
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  </main>
</div>

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
        badge.innerHTML='<strong style="color:var(--green-600)"><i class="fa fa-check-circle"></i> '+d.count+' room'+(d.count!==1?'s':'')+' available</strong><span style="color:var(--n400);margin-left:6px;">for '+gender+' students</span>';
      } else {
        badge.innerHTML='<strong style="color:var(--danger)"><i class="fa fa-times-circle"></i> No rooms available</strong><span style="color:var(--n400);margin-left:6px;">for '+gender+' students at this time</span>';
      }
    }).catch(()=>{ badge.textContent=''; });
})();
</script>
<?php include '../includes/footer.php'; ?>
