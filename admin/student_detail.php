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

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <h2 class="page-title"><i class="fa fa-user"></i> Student Detail</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div style="margin-bottom:16px;">
            <a href="students.php" class="btn btn-light btn-sm"><i class="fa fa-arrow-left"></i> Back to Students</a>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px;">

            <!-- Personal Info -->
            <div class="card">
                <div class="card-header"><i class="fa fa-id-card"></i> Personal Information</div>
                <div class="card-body">
                    <table class="detail-table">
                        <tr><td>Matric Number</td><td><strong><?= htmlspecialchars($student['matric_number']) ?></strong></td></tr>
                        <tr><td>Surname</td><td><?= htmlspecialchars($student['surname']) ?></td></tr>
                        <tr><td>First Name</td><td><?= htmlspecialchars($student['firstname']) ?></td></tr>
                        <tr><td>Other Name</td><td><?= htmlspecialchars($student['othername'] ?: '—') ?></td></tr>
                        <tr><td>Gender</td><td><?= ucfirst($student['gender']) ?></td></tr>
                        <tr><td>Phone</td><td><?= htmlspecialchars($student['phone']) ?></td></tr>
                        <tr><td>Email</td><td><?= htmlspecialchars($student['email']) ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- Academic Info -->
            <div class="card">
                <div class="card-header"><i class="fa fa-graduation-cap"></i> Academic &amp; Account Status</div>
                <div class="card-body">
                    <table class="detail-table">
                        <tr><td>Faculty</td><td><?= htmlspecialchars($student['faculty_name']) ?></td></tr>
                        <tr><td>Department</td><td><?= htmlspecialchars($student['dept_name']) ?></td></tr>
                        <tr><td>Level</td><td><?= htmlspecialchars($student['level']) ?></td></tr>
                        <tr><td>Semester</td><td><?= ucfirst($student['semester']) ?></td></tr>
                        <tr><td>Session</td><td><?= htmlspecialchars($student['academic_session']) ?></td></tr>
                        <tr><td>School Fees</td><td>
                            <?= $student['school_fees_paid']
                                ? '<span class="badge badge-success">Paid</span>'
                                : '<span class="badge badge-warning">Unpaid</span>' ?>
                        </td></tr>
                        <tr><td>Account Status</td><td>
                            <?= $student['is_activated']
                                ? '<span class="badge badge-success">Activated</span>'
                                : '<span class="badge badge-danger">Not Activated</span>' ?>
                        </td></tr>
                        <tr><td>Registered</td><td><?= date('d M Y H:i', strtotime($student['created_at'])) ?></td></tr>
                    </table>

                    <?php if ($_SESSION['admin_role'] === 'dsa'): ?>
                    <div style="margin-top:16px;">
                        <?php if ($student['is_activated']): ?>
                        <form method="POST" onsubmit="return confirm('Deactivate this account? The student will not be able to log in.');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                            <input type="hidden" name="toggle_activation" value="0">
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fa fa-ban"></i> Deactivate Account
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="POST" onsubmit="return confirm('Reactivate this student account?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                            <input type="hidden" name="toggle_activation" value="1">
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fa fa-check"></i> Activate Account
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Applications History -->
        <div class="card">
            <div class="card-header"><i class="fa fa-history"></i> Application History</div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($applications)): ?>
                    <p style="padding:20px; color:var(--gray);">No hostel applications found for this student.</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Block</th>
                            <th>Room</th>
                            <th>Status</th>
                            <th>Hostel Fee</th>
                            <th>Applied On</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($applications as $i => $a):
                        $b = ['pending'=>'warning','granted'=>'success','revoked'=>'danger'][$a['status']] ?? 'secondary';
                    ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><?= $a['block'] ? htmlspecialchars($a['block']) : '&mdash;' ?></td>
                            <td><?= $a['room_number'] ? htmlspecialchars($a['room_number']) : '&mdash;' ?></td>
                            <td><span class="badge badge-<?= $b ?>"><?= ucfirst($a['status']) ?></span></td>
                            <td><?= $a['hostel_fee_paid']
                                ? '<span class="badge badge-success">Paid</span>'
                                : '<span class="badge badge-secondary">Pending</span>' ?></td>
                            <td><?= date('d M Y H:i', strtotime($a['applied_at'])) ?></td>
                            <td><?= date('d M Y H:i', strtotime($a['updated_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<?php include '../includes/footer.php'; ?>
