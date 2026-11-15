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

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <h2 class="page-title"><i class="fa fa-chart-pie"></i> Reports &amp; Statistics</h2>

        <!-- Export buttons -->
        <div style="display:flex; gap:10px; margin-bottom:24px; flex-wrap:wrap;">
            <a href="?export=students"     class="btn btn-light btn-sm"><i class="fa fa-download"></i> Export Students CSV</a>
            <a href="?export=applications" class="btn btn-light btn-sm"><i class="fa fa-download"></i> Export Applications CSV</a>
            <a href="?export=rooms"        class="btn btn-light btn-sm"><i class="fa fa-download"></i> Export Rooms CSV</a>
        </div>

        <!-- Summary cards -->
        <div class="stats-grid" style="margin-bottom:30px;">
            <div class="stat-card"><div class="stat-num"><?= $totalStudents ?></div><div class="stat-label">Total Students</div></div>
            <div class="stat-card green"><div class="stat-num"><?= $feePaidCount ?></div><div class="stat-label">Fees Paid</div></div>
            <div class="stat-card orange"><div class="stat-num"><?= $pendingApps ?></div><div class="stat-label">Pending Apps</div></div>
            <div class="stat-card"><div class="stat-num"><?= $grantedApps ?></div><div class="stat-label">Granted</div></div>
            <div class="stat-card red"><div class="stat-num"><?= $revokedApps ?></div><div class="stat-label">Revoked</div></div>
            <div class="stat-card green"><div class="stat-num"><?= $hostelFeePaid ?></div><div class="stat-label">Hostel Fee Paid</div></div>
            <div class="stat-card green"><div class="stat-num"><?= $availableRooms ?></div><div class="stat-label">Available Rooms</div></div>
            <div class="stat-card red"><div class="stat-num"><?= $occupiedRooms ?></div><div class="stat-label">Occupied Rooms</div></div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px;">

            <!-- Gender breakdown -->
            <div class="card">
                <div class="card-header"><i class="fa fa-venus-mars"></i> Students by Gender</div>
                <div class="card-body" style="padding:0;">
                    <table>
                        <thead><tr><th>Gender</th><th>Total</th><th>Fee Paid</th><th>Applied</th><th>Granted</th></tr></thead>
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
                <div class="card-header"><i class="fa fa-layer-group"></i> Students by Level</div>
                <div class="card-body" style="padding:0;">
                    <table>
                        <thead><tr><th>Level</th><th>Total</th><th>Fee Paid</th></tr></thead>
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
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header"><i class="fa fa-university"></i> Students by Faculty</div>
            <div class="card-body" style="padding:0;">
                <table>
                    <thead><tr><th>Faculty</th><th>Total Students</th><th>Fee Paid</th><th>Applied for Hostel</th></tr></thead>
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
            <div class="card-header"><i class="fa fa-building"></i> Rooms by Block</div>
            <div class="card-body" style="padding:0;">
                <table>
                    <thead><tr><th>Block</th><th>Gender</th><th>Total</th><th>Available</th><th>Occupied</th><th>Maintenance</th><th>Occupancy %</th></tr></thead>
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
                            <?php $pct = $bs['total'] ? round($bs['occupied']/$bs['total']*100) : 0; ?>
                            <div class="progress-bar-wrap" style="min-width:80px;">
                                <div class="progress-bar-fill" style="width:<?= $pct ?>%; background:<?= $pct > 80 ? 'var(--danger)' : 'var(--success)' ?>;"></div>
                            </div>
                            <small><?= $pct ?>%</small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<?php include '../includes/footer.php'; ?>
