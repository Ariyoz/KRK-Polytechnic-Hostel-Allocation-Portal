<?php
$pageTitle = "Student Detail";
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$sid = intval($_GET['id'] ?? 0);
if (!$sid) { header("Location: students.php"); exit(); }

$message = '';
$msgType = 'success';

// Toggle account activation (DSA only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_activation'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
    if ($_SESSION['admin_role'] === 'dsa') {
        $newVal = intval($_POST['toggle_activation']);
        $upd    = $conn->prepare("UPDATE students SET is_activated=? WHERE id=?");
        $upd->bind_param("ii", $newVal, $sid);
        $upd->execute();
        $message = $newVal ? 'Account activated.' : 'Account deactivated.';
        $msgType = $newVal ? 'success' : 'warning';
    }
}

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

if (!$student) { header("Location: students.php"); exit(); }

// Get all applications for this student
$apps = $conn->prepare("
    SELECT ha.*, hr.room_number, hr.block
    FROM hostel_applications ha
    LEFT JOIN hostel_rooms hr ON ha.room_id = hr.id
    WHERE ha.student_id = ?
    ORDER BY ha.applied_at DESC
");
$apps->bind_param("i", $sid);
$apps->execute();
$applications = $apps->get_result()->fetch_all(MYSQLI_ASSOC);

$pageCss = ['admin.css'];
include '../includes/header.php';
?>

<?php include 'sidebar.php'; ?>

<a href="students.php" class="back-link">
    <svg class="icon" viewBox="0 0 24 24"><path d="M19 12H5M11 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Back to students
</a>

<div class="page-head">
    <div class="eyebrow">Admin &middot; Students</div>
    <h1><?= htmlspecialchars($student['firstname'] . ' ' . $student['surname']) ?></h1>
    <p class="sub"><?= htmlspecialchars($student['matric_number']) ?></p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="detail-grid">

    <div class="card">
        <div class="card-head"><h3>Personal information</h3></div>
        <div class="kv">
            <div class="kv-row"><span class="kv-label">Matric number</span><span class="kv-value"><?= htmlspecialchars($student['matric_number']) ?></span></div>
            <div class="kv-row"><span class="kv-label">Surname</span><span class="kv-value"><?= htmlspecialchars($student['surname']) ?></span></div>
            <div class="kv-row"><span class="kv-label">First name</span><span class="kv-value"><?= htmlspecialchars($student['firstname']) ?></span></div>
            <div class="kv-row"><span class="kv-label">Other name</span><span class="kv-value"><?= htmlspecialchars($student['othername'] ?: '—') ?></span></div>
            <div class="kv-row"><span class="kv-label">Gender</span><span class="kv-value"><?= ucfirst($student['gender']) ?></span></div>
            <div class="kv-row"><span class="kv-label">Phone</span><span class="kv-value"><?= htmlspecialchars($student['phone']) ?></span></div>
            <div class="kv-row"><span class="kv-label">Email</span><span class="kv-value"><?= htmlspecialchars($student['email']) ?></span></div>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>Academic &amp; account status</h3></div>
        <div class="kv">
            <div class="kv-row"><span class="kv-label">Faculty</span><span class="kv-value"><?= htmlspecialchars($student['faculty_name']) ?></span></div>
            <div class="kv-row"><span class="kv-label">Department</span><span class="kv-value"><?= htmlspecialchars($student['dept_name']) ?></span></div>
            <div class="kv-row"><span class="kv-label">Level</span><span class="kv-value"><?= htmlspecialchars($student['level']) ?></span></div>
            <div class="kv-row"><span class="kv-label">Semester</span><span class="kv-value"><?= ucfirst($student['semester']) ?></span></div>
            <div class="kv-row"><span class="kv-label">Session</span><span class="kv-value"><?= htmlspecialchars($student['academic_session']) ?></span></div>
            <div class="kv-row"><span class="kv-label">School fees</span><span class="kv-value">
                <?= $student['school_fees_paid']
                    ? '<span class="badge badge-success">Paid</span>'
                    : '<span class="badge badge-warning">Unpaid</span>' ?>
            </span></div>
            <div class="kv-row"><span class="kv-label">Account status</span><span class="kv-value">
                <?= $student['is_activated']
                    ? '<span class="badge badge-success">Activated</span>'
                    : '<span class="badge badge-danger">Not activated</span>' ?>
            </span></div>
            <div class="kv-row"><span class="kv-label">Registered</span><span class="kv-value"><?= date('d M Y H:i', strtotime($student['created_at'])) ?></span></div>
        </div>

        <?php if ($_SESSION['admin_role'] === 'dsa'): ?>
        <div class="mt-4">
            <?php if ($student['is_activated']): ?>
            <form method="POST" onsubmit="return confirm('Deactivate this account? The student will not be able to log in.');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="toggle_activation" value="0">
                <button type="submit" class="btn btn-danger btn-sm">Deactivate account</button>
            </form>
            <?php else: ?>
            <form method="POST" onsubmit="return confirm('Reactivate this student account?');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="toggle_activation" value="1">
                <button type="submit" class="btn btn-success btn-sm">Activate account</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-head"><h3>Application history</h3></div>
    <?php if (empty($applications)): ?>
        <div class="empty">
            <div class="empty-title">No applications yet</div>
            <p>This student has not applied for a hostel room.</p>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Block</th>
                    <th>Room</th>
                    <th>Status</th>
                    <th>Hostel fee</th>
                    <th>Applied on</th>
                    <th>Last updated</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $i => $a):
                $b = ['pending'=>'warning','granted'=>'success','revoked'=>'danger'][$a['status']] ?? 'neutral';
            ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= $a['block'] ? htmlspecialchars($a['block']) : '&mdash;' ?></td>
                    <td><?= $a['room_number'] ? htmlspecialchars($a['room_number']) : '&mdash;' ?></td>
                    <td><span class="badge badge-<?= $b ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td><?= $a['hostel_fee_paid']
                        ? '<span class="badge badge-success">Paid</span>'
                        : '<span class="badge badge-neutral">Pending</span>' ?></td>
                    <td><?= date('d M Y H:i', strtotime($a['applied_at'])) ?></td>
                    <td><?= date('d M Y H:i', strtotime($a['updated_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

    </div><!-- portal-content -->
  </div><!-- portal-main -->
</div><!-- portal -->

<?php include '../includes/footer.php'; ?>
