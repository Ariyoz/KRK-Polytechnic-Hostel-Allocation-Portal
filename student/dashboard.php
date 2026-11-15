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

include '../includes/header.php';

// Timeline
$timeline = [
    ['Registered',    true],
    ['Fees Verified', (bool)$student['school_fees_paid']],
    ['Applied',       $app && in_array($app['status'],['pending','granted','revoked'])],
    ['Room Granted',  $app && $app['status']==='granted'],
    ['Fee Paid',      $app && $app['hostel_fee_paid']],
];
$doneCount = count(array_filter(array_column($timeline, 1)));
?>

<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">

        <!-- Page header -->
        <div class="page-header">
            <div class="page-header-left">
                <h1>Welcome back, <?= htmlspecialchars($student['firstname']) ?> 👋</h1>
                <p><?= htmlspecialchars($student['faculty_name']) ?> &mdash; <?= htmlspecialchars($student['dept_name']) ?> &mdash; <?= htmlspecialchars($student['level']) ?></p>
            </div>
            <a href="profile.php" class="btn btn-light btn-sm"><i class="fa fa-user-cog"></i> Edit Profile</a>
        </div>

        <!-- Warnings -->
        <?php if (!$student['school_fees_paid']): ?>
        <div class="notice-banner">
            <i class="fa fa-exclamation-triangle"></i>
            <div class="notice-banner-text">
                <strong>School fees not yet verified</strong>
                <p>Visit the Registrar office to have your school fees payment confirmed before you can apply for a hostel room.</p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($app && $app['status']==='revoked'): ?>
        <div class="notice-banner" style="background:var(--danger-bg);border-color:#fecaca;">
            <i class="fa fa-times-circle" style="color:var(--danger);"></i>
            <div class="notice-banner-text" style="color:#991b1b;">
                <strong>Room allocation was revoked</strong>
                <p>Your previous room allocation was revoked. You may re-apply if rooms are available.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fa fa-id-card"></i></div>
                <div class="stat-num" style="font-size:14px;letter-spacing:0;"><?= htmlspecialchars($student['matric_number']) ?></div>
                <div class="stat-label">Matric Number</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon"><i class="fa fa-layer-group"></i></div>
                <div class="stat-num" style="font-size:20px;"><?= htmlspecialchars($student['level']) ?></div>
                <div class="stat-label">Current Level</div>
            </div>
            <div class="stat-card <?= $student['school_fees_paid']?'green':'red' ?>">
                <div class="stat-icon"><i class="fa fa-money-bill-wave"></i></div>
                <div class="stat-num" style="font-size:20px;"><?= $student['school_fees_paid']?'Verified':'Unpaid' ?></div>
                <div class="stat-label">School Fees</div>
            </div>
            <div class="stat-card <?= $app&&$app['status']==='granted'?'green':($app&&$app['status']==='revoked'?'red':'') ?>">
                <div class="stat-icon"><i class="fa fa-bed"></i></div>
                <div class="stat-num" style="font-size:20px;"><?= $app?ucfirst($app['status']):'No App' ?></div>
                <div class="stat-label">Room Status</div>
            </div>
        </div>

        <!-- Progress timeline -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <div class="card-title"><i class="fa fa-tasks"></i> Application Progress</div>
                <span style="font-size:12.5px;color:var(--n400);"><?= $doneCount ?>/<?= count($timeline) ?> steps done</span>
            </div>
            <div class="card-body" style="padding:20px 24px;">
                <div class="status-steps">
                    <?php foreach ($timeline as $i=>[$label,$done]):
                        $isCurrent = !$done && ($i===0 || $timeline[$i-1][1]);
                        $cls = $done?'done':($isCurrent?'current':'');
                    ?>
                    <div class="status-step <?= $cls ?>">
                        <div class="step-dot">
                            <i class="fa <?= $done?'fa-check':'fa-circle' ?>" style="font-size:10px;"></i>
                        </div>
                        <div class="step-label"><?= $label ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Two column -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

            <!-- Personal details -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fa fa-user"></i> Personal Details</div>
                    <a href="profile.php" class="card-header-action">Edit</a>
                </div>
                <div class="card-body">
                    <table class="detail-table">
                        <tr><td>Full Name</td><td><strong><?= htmlspecialchars($student['surname'].', '.$student['firstname'].($student['othername']?' '.$student['othername']:'')) ?></strong></td></tr>
                        <tr><td>Gender</td><td><?= ucfirst($student['gender']) ?></td></tr>
                        <tr><td>Department</td><td><?= htmlspecialchars($student['dept_name']) ?></td></tr>
                        <tr><td>Semester</td><td><?= ucfirst($student['semester']) ?> Semester</td></tr>
                        <tr><td>Phone</td><td><?= htmlspecialchars($student['phone']) ?></td></tr>
                        <tr><td>Email</td><td style="font-size:12.5px;"><?= htmlspecialchars($student['email']) ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- Room & application -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fa fa-bed"></i> Room &amp; Application</div>
                </div>
                <div class="card-body">
                    <?php if ($app): ?>
                        <table class="detail-table">
                            <tr><td>Applied On</td><td><?= date('d M Y',strtotime($app['applied_at'])) ?></td></tr>
                            <tr><td>Block</td><td><strong><?= $app['block']?'Block '.htmlspecialchars($app['block']):'—' ?></strong></td></tr>
                            <tr><td>Room No.</td><td><strong><?= $app['room_number']?htmlspecialchars($app['room_number']):'—' ?></strong></td></tr>
                            <tr><td>Status</td><td>
                                <?php $bc=['pending'=>'warning','granted'=>'success','revoked'=>'danger']; ?>
                                <span class="badge badge-<?= $bc[$app['status']]??'secondary' ?>"><?= ucfirst($app['status']) ?></span>
                            </td></tr>
                            <tr><td>Hostel Fee</td><td><?= $app['hostel_fee_paid']
                                ?'<span class="badge badge-success"><i class="fa fa-check"></i> Paid</span>'
                                :'<span class="badge badge-secondary">Pending</span>' ?></td></tr>
                        </table>
                        <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;">
                            <?php if ($app['status']==='granted'&&!$app['hostel_fee_paid']): ?>
                            <a href="payment.php" class="btn btn-primary btn-sm"><i class="fa fa-credit-card"></i> Pay Hostel Fee</a>
                            <?php endif; ?>
                            <?php if ($app['status']==='granted'&&$app['hostel_fee_paid']): ?>
                            <a href="allocation_letter.php" class="btn btn-primary btn-sm"><i class="fa fa-file-alt"></i> Allocation Letter</a>
                            <?php endif; ?>
                            <?php if ($app['status']==='revoked'&&$student['school_fees_paid']): ?>
                            <a href="application.php" class="btn btn-primary btn-sm"><i class="fa fa-redo"></i> Re-Apply</a>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($student['school_fees_paid']): ?>
                        <div style="text-align:center;padding:28px 0;">
                            <div style="font-size:44px;color:var(--n200);margin-bottom:12px;"><i class="fa fa-bed"></i></div>
                            <p style="color:var(--n500);font-size:14px;margin-bottom:16px;">You haven't applied for a room yet.</p>
                            <a href="application.php" class="btn btn-primary btn-sm"><i class="fa fa-bed"></i> Apply for a Room</a>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center;padding:28px 0;">
                            <div style="font-size:44px;color:var(--n200);margin-bottom:12px;"><i class="fa fa-lock"></i></div>
                            <p style="color:var(--n500);font-size:13px;">Room application available after school fees are verified.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
