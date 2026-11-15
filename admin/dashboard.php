<?php
$pageTitle = "Admin Dashboard";
require_once '../config/db.php';
require_once '../config/mailer.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base   = (strpos($_SERVER['SCRIPT_NAME'], '/hostel_portal/') !== false) ? '/hostel_portal' : '';
    define('BASE_URL', $scheme . '://' . $_SERVER['HTTP_HOST'] . $base);
}
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$message = '';
$msgType = 'success';

// Show access error from role guard redirect
if (!empty($_SESSION['access_error'])) {
    $message = $_SESSION['access_error'];
    $msgType = 'danger';
    unset($_SESSION['access_error']);
}

// Quick grant/revoke from dashboard — DSA only
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) { die('Invalid CSRF token.'); }
    $role = $_SESSION['admin_role'] ?? '';
    if ($role !== 'dsa') {
        $message = 'Only the Dean of Student Affairs can grant or revoke room allocations.';
        $msgType = 'danger';
    } else {
        $action = $_POST['action'];
        $appId  = intval($_POST['id']);

        $stmt = $conn->prepare("
            SELECT ha.*, s.email, s.firstname, s.surname, hr.room_number, hr.block
            FROM hostel_applications ha
            JOIN students s ON ha.student_id = s.id
            LEFT JOIN hostel_rooms hr ON ha.room_id = hr.id
            WHERE ha.id = ?
        ");
        $stmt->bind_param("i", $appId);
        $stmt->execute();
        $app = $stmt->get_result()->fetch_assoc();

        if ($app) {
            if ($action === 'grant' && $app['status'] !== 'granted') {
                $upd = $conn->prepare("UPDATE hostel_applications SET status='granted' WHERE id=?");
                $upd->bind_param("i", $appId); $upd->execute();
                $body = "<p>Dear <strong>{$app['firstname']} {$app['surname']}</strong>,</p>
                         <p>Your hostel room has been <strong style='color:green'>GRANTED</strong>.</p>
                         <p><strong>Room:</strong> Block {$app['block']} &mdash; Room {$app['room_number']}</p>
                         <a href='" . BASE_URL . "/student/login.php' class='btn'>Login to Portal</a>";
                sendEmail($app['email'], 'Hostel Room Granted', emailTemplate('Room Allocation Granted', $body));
                $message = "Room GRANTED for " . htmlspecialchars($app['firstname'] . ' ' . $app['surname']) . ".";
            } elseif ($action === 'revoke' && $app['status'] !== 'revoked') {
                $upd = $conn->prepare("UPDATE hostel_applications SET status='revoked' WHERE id=?");
                $upd->bind_param("i", $appId); $upd->execute();
                if ($app['room_id']) {
                    $fr = $conn->prepare("UPDATE hostel_rooms SET status='available' WHERE id=?");
                    $fr->bind_param("i", $app['room_id']); $fr->execute();
                }
                $body = "<p>Dear <strong>{$app['firstname']} {$app['surname']}</strong>,</p>
                         <p>Your hostel room allocation has been <strong style='color:red'>REVOKED</strong>.</p>
                         <p>Please visit the Student Affairs office for more information.</p>";
                sendEmail($app['email'], 'Hostel Room Revoked', emailTemplate('Room Allocation Revoked', $body));
                $message = "Room REVOKED for " . htmlspecialchars($app['firstname'] . ' ' . $app['surname']) . ".";
                $msgType = 'warning';
            }
        }
    } // end DSA role check
}

// Stats
$totalStudents    = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
$activatedStudents= $conn->query("SELECT COUNT(*) as c FROM students WHERE is_activated=1")->fetch_assoc()['c'];
$feePaidStudents  = $conn->query("SELECT COUNT(*) as c FROM students WHERE school_fees_paid=1")->fetch_assoc()['c'];
$pendingApps      = $conn->query("SELECT COUNT(*) as c FROM hostel_applications WHERE status='pending'")->fetch_assoc()['c'];
$grantedApps      = $conn->query("SELECT COUNT(*) as c FROM hostel_applications WHERE status='granted'")->fetch_assoc()['c'];
$revokedApps      = $conn->query("SELECT COUNT(*) as c FROM hostel_applications WHERE status='revoked'")->fetch_assoc()['c'];
$availableRooms   = $conn->query("SELECT COUNT(*) as c FROM hostel_rooms WHERE status='available'")->fetch_assoc()['c'];
$occupiedRooms    = $conn->query("SELECT COUNT(*) as c FROM hostel_rooms WHERE status='occupied'")->fetch_assoc()['c'];
$totalRooms       = $conn->query("SELECT COUNT(*) as c FROM hostel_rooms")->fetch_assoc()['c'];
$feePaidHostel    = $conn->query("SELECT COUNT(*) as c FROM hostel_applications WHERE hostel_fee_paid=1")->fetch_assoc()['c'];

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-left">
                <h1>Dashboard</h1>
                <p>Welcome back, <strong><?= htmlspecialchars($_SESSION['admin_name']) ?></strong> &mdash; <?= htmlspecialchars(strtoupper($_SESSION['admin_role'])) ?></p>
            </div>
            <div style="display:flex;gap:8px;">
                <a href="applications.php?status=pending" class="btn btn-primary btn-sm">
                    <i class="fa fa-clipboard-list"></i> <?= $pendingApps ?> Pending
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $msgType ?>"><?= $message ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fa fa-users"></i></div>
                <div class="stat-num"><?= $totalStudents ?></div>
                <div class="stat-label">Registered Students</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon"><i class="fa fa-user-check"></i></div>
                <div class="stat-num"><?= $activatedStudents ?></div>
                <div class="stat-label">Activated Accounts</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon"><i class="fa fa-money-bill-wave"></i></div>
                <div class="stat-num"><?= $feePaidStudents ?></div>
                <div class="stat-label">School Fees Paid</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon"><i class="fa fa-clock"></i></div>
                <div class="stat-num"><?= $pendingApps ?></div>
                <div class="stat-label">Pending Applications</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
                <div class="stat-num"><?= $grantedApps ?></div>
                <div class="stat-label">Rooms Granted</div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon"><i class="fa fa-times-circle"></i></div>
                <div class="stat-num"><?= $revokedApps ?></div>
                <div class="stat-label">Revoked</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon"><i class="fa fa-door-open"></i></div>
                <div class="stat-num"><?= $availableRooms ?></div>
                <div class="stat-label">Available Rooms</div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon"><i class="fa fa-bed"></i></div>
                <div class="stat-num"><?= $occupiedRooms ?></div>
                <div class="stat-label">Occupied Rooms</div>
            </div>
        </div>

        <!-- Occupancy Overview -->
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <div class="card-title"><i class="fa fa-chart-bar"></i> Occupancy Overview</div>
                <a href="reports.php" class="card-header-action">Full Report →</a>
            </div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:28px;">
                    <div class="progress-wrap">
                        <div class="progress-label"><span>Room Capacity</span><span><?= $totalRooms ?>/200</span></div>
                        <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= min(100,round($totalRooms/200*100)) ?>%;background:var(--p600);"></div></div>
                    </div>
                    <div class="progress-wrap">
                        <div class="progress-label"><span>Rooms Occupied</span><span><?= $occupiedRooms ?>/<?= max(1,$totalRooms) ?></span></div>
                        <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $totalRooms ? min(100,round($occupiedRooms/$totalRooms*100)) : 0 ?>%;background:var(--danger);"></div></div>
                    </div>
                    <div class="progress-wrap">
                        <div class="progress-label"><span>Hostel Fees Collected</span><span><?= $feePaidHostel ?>/<?= max(1,$grantedApps) ?></span></div>
                        <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $grantedApps ? min(100,round($feePaidHostel/$grantedApps*100)) : 0 ?>%;background:var(--success);"></div></div>
                    </div>
                    <div class="progress-wrap">
                        <div class="progress-label"><span>Fee-Eligible Students</span><span><?= $feePaidStudents ?>/<?= max(1,$totalStudents) ?></span></div>
                        <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $totalStudents ? min(100,round($feePaidStudents/$totalStudents*100)) : 0 ?>%;background:var(--g400);"></div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Applications -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fa fa-clipboard-list"></i> Recent Applications</div>
                <a href="applications.php" class="card-header-action">View All &rarr;</a>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Matric No.</th>
                                <th>Room</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $recent = $conn->query("
                            SELECT ha.id, s.surname, s.firstname, s.matric_number,
                                   hr.room_number, hr.block, ha.status, ha.applied_at
                            FROM hostel_applications ha
                            JOIN students s ON ha.student_id = s.id
                            LEFT JOIN hostel_rooms hr ON ha.room_id = hr.id
                            ORDER BY ha.applied_at DESC LIMIT 10
                        ");
                        $i = 1;
                        while ($row = $recent->fetch_assoc()):
                            $b = ['pending'=>'warning','granted'=>'success','revoked'=>'danger'][$row['status']] ?? 'secondary';
                        ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($row['surname'] . ', ' . $row['firstname']) ?></td>
                                <td><?= htmlspecialchars($row['matric_number']) ?></td>
                                <td><?= $row['room_number'] ? 'Blk '.$row['block'].' Rm '.$row['room_number'] : '&mdash;' ?></td>
                                <td><span class="badge badge-<?= $b ?>"><?= ucfirst($row['status']) ?></span></td>
                                <td><?= date('d M Y', strtotime($row['applied_at'])) ?></td>
                                <td style="white-space:nowrap;">
                                    <?php if ($row['status'] !== 'granted'): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Grant room to <?= htmlspecialchars(addslashes($row['firstname'])) ?>?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                        <input type="hidden" name="action" value="grant">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm">Grant</button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($row['status'] !== 'revoked'): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Revoke room for <?= htmlspecialchars(addslashes($row['firstname'])) ?>?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                        <input type="hidden" name="action" value="revoke">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Revoke</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<?php include '../includes/footer.php'; ?>
