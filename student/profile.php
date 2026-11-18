<?php
$pageTitle = "My Profile";
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['student_id'])) { header("Location: login.php"); exit(); }

$sid = intval($_SESSION['student_id']);
$error = $success = '';

$stmt = $conn->prepare("SELECT s.*,f.name AS faculty_name,d.name AS dept_name FROM students s LEFT JOIN faculties f ON s.faculty_id=f.id LEFT JOIN departments d ON s.department_id=d.id WHERE s.id=?");
$stmt->bind_param("i",$sid); $stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $phone  = trim($_POST['phone']??'');
    $newPwd = $_POST['new_password']??'';
    $curPwd = $_POST['current_password']??'';
    $cfmPwd = $_POST['confirm_new_password']??'';
    if ($newPwd) {
        if (!password_verify($curPwd,$student['password'])) $error='Current password is incorrect.';
        elseif (strlen($newPwd)<6) $error='New password must be at least 6 characters.';
        elseif ($newPwd !== $cfmPwd) $error='New passwords do not match.';
        else {
            $h=$conn->prepare("UPDATE students SET phone=?,password=? WHERE id=?");
            $h->bind_param("ssi",$phone,password_hash($newPwd,PASSWORD_DEFAULT),$sid);
            $h->execute(); $success='Profile and password updated successfully.';
        }
    } else {
        $u=$conn->prepare("UPDATE students SET phone=? WHERE id=?");
        $u->bind_param("si",$phone,$sid); $u->execute();
        $success='Phone number updated successfully.';
    }
    // Refresh
    $stmt->execute(); $student=$stmt->get_result()->fetch_assoc();
}
$pageCss = ['student.css'];
include '../includes/header.php';
?>

<?php include 'sidebar.php'; ?>

<div class="page-head">
  <h1>Profile</h1>
  <p class="sub">Your account details and password</p>
</div>

<?php if ($error): ?><div class="alert alert-danger mb-3"><svg class="alert-icon"><use href="#icon-alert"></use></svg><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success mb-3"><svg class="alert-icon"><use href="#icon-check"></use></svg><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="grid grid-2">
  <div class="card">
    <div class="card-head"><h3>Account</h3></div>
    <div class="profile-head">
      <div class="avatar avatar-lg"><?= strtoupper(substr($student['firstname'],0,1)) ?></div>
      <div>
        <strong><?= htmlspecialchars($student['surname'].', '.$student['firstname']) ?></strong>
        <div class="text-sm muted"><?= htmlspecialchars($student['matric_number']) ?></div>
      </div>
    </div>
    <div class="kv">
      <div class="kv-row"><span class="kv-label">Faculty</span><span class="kv-value"><?= htmlspecialchars($student['faculty_name']) ?></span></div>
      <div class="kv-row"><span class="kv-label">Department</span><span class="kv-value"><?= htmlspecialchars($student['dept_name']) ?></span></div>
      <div class="kv-row"><span class="kv-label">Level</span><span class="kv-value"><?= htmlspecialchars($student['level']) ?></span></div>
      <div class="kv-row"><span class="kv-label">Gender</span><span class="kv-value"><?= ucfirst($student['gender']) ?></span></div>
      <div class="kv-row"><span class="kv-label">Email</span><span class="kv-value"><?= htmlspecialchars($student['email']) ?></span></div>
      <div class="kv-row"><span class="kv-label">Session</span><span class="kv-value"><?= htmlspecialchars($student['academic_session']) ?></span></div>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Edit details</h3></div>
    <form method="POST">
      <div class="field-row">
        <div class="field">
          <label class="label">Surname</label>
          <input type="text" class="input" value="<?= htmlspecialchars($student['surname']) ?>" readonly>
        </div>
        <div class="field">
          <label class="label">First name</label>
          <input type="text" class="input" value="<?= htmlspecialchars($student['firstname']) ?>" readonly>
        </div>
      </div>
      <div class="field">
        <label class="label">Phone number</label>
        <input type="tel" name="phone" class="input" value="<?= htmlspecialchars($student['phone']) ?>" placeholder="08012345678">
      </div>

      <fieldset class="field-group">
        <legend>Change password <span class="legend-note">(optional)</span></legend>
        <div class="field">
          <label class="label">Current password</label>
          <div class="pwd-field">
            <input type="password" name="current_password" id="curPwd" class="input" placeholder="Enter current password" autocomplete="current-password">
            <button type="button" class="pwd-toggle" onclick="togglePwd('curPwd',this)" aria-label="Show password"><svg class="icon"><use href="#icon-eye"></use></svg></button>
          </div>
        </div>
        <div class="field">
          <label class="label">New password</label>
          <div class="pwd-field">
            <input type="password" name="new_password" id="newPwd" class="input" placeholder="Leave blank to keep current" autocomplete="new-password">
            <button type="button" class="pwd-toggle" onclick="togglePwd('newPwd',this)" aria-label="Show password"><svg class="icon"><use href="#icon-eye"></use></svg></button>
          </div>
        </div>
        <div class="field">
          <label class="label">Confirm new password</label>
          <div class="pwd-field">
            <input type="password" name="confirm_new_password" id="cfmPwd" class="input" placeholder="Repeat new password" autocomplete="new-password">
            <button type="button" class="pwd-toggle" onclick="togglePwd('cfmPwd',this)" aria-label="Show password"><svg class="icon"><use href="#icon-eye"></use></svg></button>
          </div>
        </div>
      </fieldset>

      <button type="submit" class="btn btn-primary btn-block">Save changes</button>
    </form>
  </div>
</div>

      </div><!-- /.portal-content -->
    </div><!-- /.portal-main -->
  </div><!-- /.portal -->
<?php include '../includes/footer.php'; ?>
