<?php
$pageTitle = "Allocation Letter";
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['student_id'])) { header("Location: login.php"); exit(); }

$sid = intval($_SESSION['student_id']);

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

$appStmt = $conn->prepare("
    SELECT ha.*, hr.room_number, hr.block, hr.gender AS room_gender
    FROM hostel_applications ha
    LEFT JOIN hostel_rooms hr ON ha.room_id = hr.id
    WHERE ha.student_id = ? AND ha.status = 'granted' AND ha.hostel_fee_paid = 1
    ORDER BY ha.applied_at DESC LIMIT 1
");
$appStmt->bind_param("i", $sid);
$appStmt->execute();
$app = $appStmt->get_result()->fetch_assoc();

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <h2 class="page-title"><i class="fa fa-file-alt"></i> Hostel Allocation Letter</h2>

        <?php if (!$app): ?>
            <div class="alert alert-warning">
                <strong>Not Available:</strong> Your allocation letter is only available once your room has been granted
                and your hostel fee payment confirmed.
            </div>
            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        <?php else: ?>

        <div style="margin-bottom:16px; display:flex; gap:10px; flex-wrap:wrap;">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fa fa-print"></i> Print Letter
            </button>
            <a href="dashboard.php" class="btn btn-light">
                <i class="fa fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Printable Letter -->
        <div class="allocation-letter" id="allocationLetter">
            <div class="letter-header">
                <div class="letter-logo-area">
                    <div class="letter-logo-circle">KRK</div>
                </div>
                <div class="letter-school-info">
                    <h2>KRK POLYTECHNIC, LAGOS</h2>
                    <p>Student Affairs Division &mdash; Hostel Allocation Unit</p>
                    <p>Lagos, Nigeria &bull; hostelportal@krkpoly.edu.ng</p>
                </div>
            </div>

            <hr class="letter-hr">

            <div class="letter-title">
                HOSTEL ROOM ALLOCATION LETTER
                <div class="letter-subtitle">Academic Session: <?= htmlspecialchars($student['academic_session']) ?></div>
            </div>

            <div class="letter-meta">
                <span>Ref: KRK/HOSTEL/<?= strtoupper(substr(md5($sid.$app['id']), 0, 8)) ?></span>
                <span>Date: <?= date('d F Y') ?></span>
            </div>

            <div class="letter-address">
                <p>To:</p>
                <p><strong><?= htmlspecialchars(strtoupper($student['surname']) . ', ' . $student['firstname'] . ' ' . $student['othername']) ?></strong></p>
                <p>Matric Number: <strong><?= htmlspecialchars($student['matric_number']) ?></strong></p>
                <p><?= htmlspecialchars($student['faculty_name']) ?> &mdash; <?= htmlspecialchars($student['dept_name']) ?></p>
            </div>

            <div class="letter-body">
                <p>Dear <strong><?= htmlspecialchars($student['firstname'] . ' ' . $student['surname']) ?></strong>,</p>

                <p>
                    Following your application for hostel accommodation for the
                    <strong><?= htmlspecialchars($student['academic_session']) ?></strong> academic session,
                    we are pleased to inform you that a room has been allocated to you in the KRK Polytechnic Student Hostel.
                    Your hostel fee payment has also been received.
                </p>

                <table class="letter-table">
                    <tr>
                        <td>Student Name</td>
                        <td><strong><?= htmlspecialchars($student['surname'] . ', ' . $student['firstname'] . ' ' . $student['othername']) ?></strong></td>
                    </tr>
                    <tr>
                        <td>Matric Number</td>
                        <td><strong><?= htmlspecialchars($student['matric_number']) ?></strong></td>
                    </tr>
                    <tr>
                        <td>Faculty</td>
                        <td><?= htmlspecialchars($student['faculty_name']) ?></td>
                    </tr>
                    <tr>
                        <td>Department</td>
                        <td><?= htmlspecialchars($student['dept_name']) ?></td>
                    </tr>
                    <tr>
                        <td>Level</td>
                        <td><?= htmlspecialchars($student['level']) ?></td>
                    </tr>
                    <tr>
                        <td>Gender</td>
                        <td><?= ucfirst($student['gender']) ?></td>
                    </tr>
                    <tr class="highlight-row">
                        <td>Allocated Block</td>
                        <td><strong>Block <?= htmlspecialchars($app['block']) ?></strong></td>
                    </tr>
                    <tr class="highlight-row">
                        <td>Room Number</td>
                        <td><strong><?= htmlspecialchars($app['room_number']) ?></strong></td>
                    </tr>
                    <tr>
                        <td>Academic Session</td>
                        <td><?= htmlspecialchars($student['academic_session']) ?></td>
                    </tr>
                    <tr>
                        <td>Hostel Fee Status</td>
                        <td><strong style="color:green;">PAID ✓</strong></td>
                    </tr>
                    <tr>
                        <td>Date of Allocation</td>
                        <td><?= date('d F Y', strtotime($app['updated_at'])) ?></td>
                    </tr>
                </table>

                <p style="margin-top:20px;">
                    Please present this letter along with a valid student ID at the hostel reception desk to collect your room key.
                    Kindly note that this allocation is <strong>non-transferable</strong> and is valid for the academic
                    session stated above only.
                </p>

                <p>Hostel rules and regulations will be provided at the reception. Any violation may lead to revocation of your
                   accommodation without refund.</p>
            </div>

            <div class="letter-signature">
                <div>
                    <div class="sig-line"></div>
                    <p><strong>Dean of Student Affairs (DSA)</strong></p>
                    <p>KRK Polytechnic, Lagos</p>
                </div>
                <div>
                    <div class="sig-line"></div>
                    <p><strong>Student Signature / Acknowledgement</strong></p>
                    <p>Date: __________________________</p>
                </div>
            </div>

            <div class="letter-footer">
                <em>This is a system-generated document. KRK Polytechnic &mdash; Hostel Allocation Portal &mdash; <?= date('Y') ?></em>
            </div>
        </div>

        <?php endif; ?>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
