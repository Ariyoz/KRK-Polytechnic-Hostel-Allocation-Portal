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
include '../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include 'sidebar.php'; ?>
  <main class="main-content">

    <div class="page-header">
      <div class="page-header-left">
        <h1><i class="fa fa-user-cog" style="color:var(--green-600);font-size:18px;margin-right:8px;"></i>My Profile</h1>
        <p>Manage your account details and password</p>
      </div>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><i class="fa fa-check-circle"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;max-width:900px;">

      <!-- Account info -->
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fa fa-id-card"></i>Account Information</div></div>
        <div class="card-body">
          <div style="display:flex;align-items:center;gap:16px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px solid var(--n100);">
            <div style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,var(--green-700),var(--green-500));display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:900;color:#fff;flex-shrink:0;">
              <?= strtoupper(substr($student['firstname'],0,1)) ?>
            </div>
            <div>
              <div style="font-size:17px;font-weight:700;color:var(--n900);"><?= htmlspecialchars($student['surname'].', '.$student['firstname']) ?></div>
              <div style="font-size:13px;color:var(--n500);"><?= htmlspecialchars($student['matric_number']) ?></div>
            </div>
          </div>
          <table class="detail-table">
            <tr><td>Faculty</td><td><?= htmlspecialchars($student['faculty_name']) ?></td></tr>
            <tr><td>Department</td><td><?= htmlspecialchars($student['dept_name']) ?></td></tr>
            <tr><td>Level</td><td><?= htmlspecialchars($student['level']) ?></td></tr>
            <tr><td>Gender</td><td><?= ucfirst($student['gender']) ?></td></tr>
            <tr><td>Email</td><td style="font-size:12.5px;"><?= htmlspecialchars($student['email']) ?></td></tr>
            <tr><td>Session</td><td><?= htmlspecialchars($student['academic_session']) ?></td></tr>
          </table>
        </div>
      </div>

      <!-- Edit form -->
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fa fa-edit"></i>Edit Details</div></div>
        <div class="card-body">
          <form method="POST">
            <div class="form-row">
              <div class="form-group">
                <label>Surname</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($student['surname']) ?>" readonly>
              </div>
              <div class="form-group">
                <label>First Name</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($student['firstname']) ?>" readonly>
              </div>
            </div>
            <div class="form-group">
              <label>Phone Number</label>
              <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($student['phone']) ?>" placeholder="08012345678">
            </div>
            <div style="border-top:1px solid var(--n100);padding-top:18px;margin-top:4px;margin-bottom:18px;">
              <div style="font-size:13px;font-weight:700;color:var(--n700);margin-bottom:14px;">Change Password <span style="font-weight:400;color:var(--n400);">(optional)</span></div>
              <div class="form-group">
                <label>Current Password</label>
                <div class="input-pwd-wrap">
                  <input type="password" name="current_password" id="curPwd" class="form-control" placeholder="Enter current password" autocomplete="current-password">
                  <button type="button" class="pwd-toggle" onclick="togglePwd('curPwd',this)" aria-label="Show/hide"><i class="fa fa-eye"></i></button>
                </div>
              </div>
              <div class="form-group">
                <label>New Password</label>
                <div class="input-pwd-wrap">
                  <input type="password" name="new_password" id="newPwd" class="form-control" placeholder="Leave blank to keep current" autocomplete="new-password">
                  <button type="button" class="pwd-toggle" onclick="togglePwd('newPwd',this)" aria-label="Show/hide"><i class="fa fa-eye"></i></button>
                </div>
              </div>
              <div class="form-group">
                <label>Confirm New Password</label>
                <div class="input-pwd-wrap">
                  <input type="password" name="confirm_new_password" id="cfmPwd" class="form-control" placeholder="Repeat new password" autocomplete="new-password">
                  <button type="button" class="pwd-toggle" onclick="togglePwd('cfmPwd',this)" aria-label="Show/hide"><i class="fa fa-eye"></i></button>
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-save"></i>Save Changes</button>
          </form>
        </div>
      </div>
    </div>

  </main>
</div>
<?php include '../includes/footer.php'; ?>
