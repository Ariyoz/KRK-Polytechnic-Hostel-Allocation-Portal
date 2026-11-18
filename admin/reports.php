<?php
$pageTitle = "Reports";
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }
require_once 'auth_guard.php';
require_admin(); // All roles can view reports

// ---- Aggregate stats ----
$totalStudents   = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
$activatedCount  = $conn->query("SELECT COUNT(*) AS c FROM students WHERE is_activated=1")->fetch_assoc()['c'];
$feePaidCount    = $conn->query("SELECT COUNT(*) AS c FROM students WHERE school_fees_paid=1")->fetch_assoc()['c'];
$pendingApps     = $conn->query("SELECT COUNT(*) AS c FROM hostel_applications WHERE status='pending'")->fetch_assoc()['c'];
$grantedApps     = $conn->query("SELECT COUNT(*) AS c FROM hostel_applications WHERE status='granted'")->fetch_assoc()['c'];
$revokedApps     = $conn->query("SELECT COUNT(*) AS c FROM hostel_applications WHERE status='revoked'")->fetch_assoc()['c'];
$hostelFeePaid   = $conn->query("SELECT COUNT(*) AS c FROM hostel_applications WHERE hostel_fee_paid=1")->fetch_assoc()['c'];
$availableRooms  = $conn->query("SELECT COUNT(*) AS c FROM hostel_rooms WHERE status='available'")->fetch_assoc()['c'];
$occupiedRooms   = $conn->query("SELECT COUNT(*) AS c FROM hostel_rooms WHERE status='occupied'")->fetch_assoc()['c'];
$maintenanceRooms= $conn->query("SELECT COUNT(*) AS c FROM hostel_rooms WHERE status='maintenance'")->fetch_assoc()['c'];
$totalRooms      = $availableRooms + $occupiedRooms + $maintenanceRooms;

// ---- By gender ----
$genderStats = $conn->query("
    SELECT s.gender, COUNT(*) AS total,
           SUM(s.school_fees_paid) AS fee_paid,
           COUNT(ha.id) AS applied,
           SUM(ha.status='granted') AS granted
    FROM students s
    LEFT JOIN hostel_applications ha ON ha.student_id = s.id AND ha.status != 'revoked'
    GROUP BY s.gender
")->fetch_all(MYSQLI_ASSOC);

// ---- By faculty ----
$facultyStats = $conn->query("
    SELECT f.name AS faculty, COUNT(s.id) AS total_students,
           SUM(s.school_fees_paid) AS fee_paid,
           COUNT(ha.id) AS applied
    FROM faculties f
    LEFT JOIN students s ON s.faculty_id = f.id
    LEFT JOIN hostel_applications ha ON ha.student_id = s.id
    GROUP BY f.id ORDER BY total_students DESC
")->fetch_all(MYSQLI_ASSOC);

// ---- By level ----
$levelStats = $conn->query("
    SELECT level, COUNT(*) AS total,
           SUM(school_fees_paid) AS fee_paid
    FROM students GROUP BY level ORDER BY level
")->fetch_all(MYSQLI_ASSOC);

// ---- Rooms by block ----
$blockStats = $conn->query("
    SELECT block, gender,
           COUNT(*) AS total,
           SUM(status='available') AS available,
           SUM(status='occupied') AS occupied,
           SUM(status='maintenance') AS maintenance
    FROM hostel_rooms
    GROUP BY block, gender ORDER BY block
")->fetch_all(MYSQLI_ASSOC);

// ---- Export CSV ----
if (isset($_GET['export'])) {
    $export = $_GET['export'];
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="krk_hostel_report_' . $export . '_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');

    if ($export === 'students') {
        fputcsv($out, ['Matric No', 'Surname', 'First Name', 'Gender', 'Faculty', 'Department', 'Level', 'Email', 'Phone', 'Fees Paid', 'Activated', 'Registered On']);
        $rows = $conn->query("
            SELECT s.matric_number, s.surname, s.firstname, s.gender, f.name AS faculty, d.name AS dept,
                   s.level, s.email, s.phone, s.school_fees_paid, s.is_activated, s.created_at
            FROM students s
            LEFT JOIN faculties f ON s.faculty_id=f.id
            LEFT JOIN departments d ON s.department_id=d.id
            ORDER BY s.created_at DESC
        ");
        while ($r = $rows->fetch_assoc()) {
            $r['school_fees_paid'] = $r['school_fees_paid'] ? 'Yes' : 'No';
            $r['is_activated']     = $r['is_activated'] ? 'Yes' : 'No';
            fputcsv($out, $r);
        }
    } elseif ($export === 'applications') {
        fputcsv($out, ['Matric No', 'Student Name', 'Gender', 'Faculty', 'Block', 'Room', 'Status', 'Hostel Fee Paid', 'Applied On']);
        $rows = $conn->query("
            SELECT s.matric_number, CONCAT(s.surname,', ',s.firstname) AS name, s.gender,
                   f.name AS faculty, hr.block, hr.room_number,
                   ha.status, ha.hostel_fee_paid, ha.applied_at
            FROM hostel_applications ha
            JOIN students s ON ha.student_id=s.id
            LEFT JOIN faculties f ON s.faculty_id=f.id
            LEFT JOIN hostel_rooms hr ON ha.room_id=hr.id
            ORDER BY ha.applied_at DESC
        ");
        while ($r = $rows->fetch_assoc()) {
            $r['hostel_fee_paid'] = $r['hostel_fee_paid'] ? 'Yes' : 'No';
            fputcsv($out, $r);
        }
    } elseif ($export === 'rooms') {
        fputcsv($out, ['Room Number', 'Block', 'Gender', 'Status', 'Added On']);
        $rows = $conn->query("SELECT room_number, block, gender, status, created_at FROM hostel_rooms ORDER BY block, room_number");
        while ($r = $rows->fetch_assoc()) fputcsv($out, $r);
    }

    fclose($out);
    exit;
}

$roleLabel = ['dsa'=>'Dean of Student Affairs','registrar'=>'Registrar Office','ict'=>'ICT Department'][$_SESSION['admin_role'] ?? ''] ?? strtoupper($_SESSION['admin_role'] ?? '');
$totalApps = $pendingApps + $grantedApps + $revokedApps;
$pct = function ($part, $whole) { return $whole ? round(($part / $whole) * 100) : 0; };

$pageCss = ['admin.css','admin-extra.css'];
include '../includes/header.php';
?>

<?php include 'sidebar.php'; ?>

<div class="page-head page-head-row">
                <div>
                    <span class="eyebrow">Analytics</span>
                    <h1>Reports &amp; statistics</h1>
                    <p class="sub"><?= $totalStudents ?> registered students &middot; <?= $feePaidCount ?> fee-verified &middot; <?= $hostelFeePaid ?> hostel fee paid.</p>
                </div>
                <div class="filter-row mb-0">
                    <a href="?export=students" class="btn btn-outline btn-sm">Export students CSV</a>
                    <a href="?export=applications" class="btn btn-outline btn-sm">Export applications CSV</a>
                    <a href="?export=rooms" class="btn btn-outline btn-sm">Export rooms CSV</a>
                </div>
            </div>

            <div class="grid grid-2 mb-5">

                <!-- Applications overview -->
                <div class="card">
                    <div class="card-head"><h3>Applications</h3><span class="text-sm muted"><?= $totalApps ?> total</span></div>
                    <div class="bar-list">
                        <div class="bar-row">
                            <span class="bar-name">Pending</span>
                            <div class="bar-track"><div class="bar-fill is-amber" style="width:<?= $pct($pendingApps, $totalApps) ?>%"></div></div>
                            <span class="bar-count"><?= $pendingApps ?></span>
                        </div>
                        <div class="bar-row">
                            <span class="bar-name">Granted</span>
                            <div class="bar-track"><div class="bar-fill is-green" style="width:<?= $pct($grantedApps, $totalApps) ?>%"></div></div>
                            <span class="bar-count"><?= $grantedApps ?></span>
                        </div>
                        <div class="bar-row">
                            <span class="bar-name">Revoked</span>
                            <div class="bar-track"><div class="bar-fill is-red" style="width:<?= $pct($revokedApps, $totalApps) ?>%"></div></div>
                            <span class="bar-count"><?= $revokedApps ?></span>
                        </div>
                    </div>
                </div>

                <!-- Rooms overview -->
                <div class="card">
                    <div class="card-head"><h3>Rooms</h3><span class="text-sm muted"><?= $totalRooms ?> total</span></div>
                    <div class="bar-list">
                        <div class="bar-row">
                            <span class="bar-name">Available</span>
                            <div class="bar-track"><div class="bar-fill is-green" style="width:<?= $pct($availableRooms, $totalRooms) ?>%"></div></div>
                            <span class="bar-count"><?= $availableRooms ?></span>
                        </div>
                        <div class="bar-row">
                            <span class="bar-name">Occupied</span>
                            <div class="bar-track"><div class="bar-fill is-red" style="width:<?= $pct($occupiedRooms, $totalRooms) ?>%"></div></div>
                            <span class="bar-count"><?= $occupiedRooms ?></span>
                        </div>
                        <div class="bar-row">
                            <span class="bar-name">Maintenance</span>
                            <div class="bar-track"><div class="bar-fill is-amber" style="width:<?= $pct($maintenanceRooms, $totalRooms) ?>%"></div></div>
                            <span class="bar-count"><?= $maintenanceRooms ?></span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="grid grid-2 mb-5">

                <!-- Gender breakdown -->
                <div class="card">
                    <div class="card-head"><h3>Students by gender</h3></div>
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Gender</th><th>Total</th><th>Fee paid</th><th>Applied</th><th>Granted</th></tr></thead>
                            <tbody>
                            <?php foreach ($genderStats as $g): ?>
                            <tr>
                                <td><?= ucfirst($g['gender']) ?></td>
                                <td><?= $g['total'] ?></td>
                                <td><?= $g['fee_paid'] ?></td>
                                <td><?= $g['applied'] ?></td>
                                <td><?= $g['granted'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Level breakdown -->
                <div class="card">
                    <div class="card-head"><h3>Students by level</h3></div>
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Level</th><th>Total</th><th>Fee paid</th></tr></thead>
                            <tbody>
                            <?php foreach ($levelStats as $l): ?>
                            <tr>
                                <td><?= htmlspecialchars($l['level']) ?></td>
                                <td><?= $l['total'] ?></td>
                                <td><?= $l['fee_paid'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Faculty breakdown -->
            <div class="card report-section">
                <div class="card-head"><h3>Students by faculty</h3></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Faculty</th><th>Total students</th><th>Fee paid</th><th>Applied for hostel</th></tr></thead>
                        <tbody>
                        <?php foreach ($facultyStats as $f): ?>
                        <tr>
                            <td><?= htmlspecialchars($f['faculty']) ?></td>
                            <td><?= $f['total_students'] ?></td>
                            <td><?= $f['fee_paid'] ?></td>
                            <td><?= $f['applied'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Rooms by block -->
            <div class="card">
                <div class="card-head"><h3>Rooms by block</h3></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Block</th><th>Gender</th><th>Total</th><th>Available</th><th>Occupied</th><th>Maintenance</th><th>Occupancy</th></tr></thead>
                        <tbody>
                        <?php foreach ($blockStats as $bs): ?>
                        <tr>
                            <td><strong>Block <?= htmlspecialchars($bs['block']) ?></strong></td>
                            <td><?= ucfirst($bs['gender']) ?></td>
                            <td><?= $bs['total'] ?></td>
                            <td><span class="badge badge-success"><?= $bs['available'] ?></span></td>
                            <td><span class="badge badge-danger"><?= $bs['occupied'] ?></span></td>
                            <td><span class="badge badge-warning"><?= $bs['maintenance'] ?></span></td>
                            <td>
                                <?php $occPct = $pct($bs['occupied'], $bs['total']); ?>
                                <div class="progress">
                                    <div class="progress-bar <?= $occPct > 80 ? 'is-danger' : '' ?>" style="width:<?= $occPct ?>%;"></div>
                                </div>
                                <span class="text-sm muted"><?= $occPct ?>%</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

    </div><!-- portal-content -->
  </div><!-- portal-main -->
</div><!-- portal -->

<?php include '../includes/footer.php'; ?>
