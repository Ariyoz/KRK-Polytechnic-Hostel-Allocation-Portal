<?php
$pageTitle = "Hostel Applications";
require_once '../config/db.php';
require_once '../config/mailer.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base   = (strpos($_SERVER['SCRIPT_NAME'], '/hostel_portal/') !== false) ? '/hostel_portal' : '';
    define('BASE_URL', $scheme . '://' . $_SERVER['HTTP_HOST'] . $base);
}
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }
require_once 'auth_guard.php';
require_admin();

$message = '';
$msgType = 'success';

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Grant/revoke — DSA only
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) { die('Invalid CSRF token.'); }
    if (!admin_can(['dsa'])) {
        $message = 'Only the Dean of Student Affairs can grant or revoke room allocations.';
        $msgType = 'danger';
    } else {
        $action = $_POST['action'];
        $appId  = intval($_POST['id']);

    // Get application + student info
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
            $upd->bind_param("i", $appId);
            $upd->execute();

            $body = "
                <p>Dear <strong>{$app['firstname']} {$app['surname']}</strong>,</p>
                <p>Congratulations! Your hostel room allocation has been <strong style='color:green;'>GRANTED</strong>.</p>
                <p><strong>Room:</strong> Block {$app['block']} &mdash; Room {$app['room_number']}</p>
                <p>Please log in to the portal to pay your hostel fee and complete your registration.</p>
                <a href='<?= BASE_URL ?>/student/login.php' class='btn'>Login to Portal</a>
            ";
            sendEmail($app['email'], 'Hostel Room Granted — KRK Polytechnic', emailTemplate('Room Allocation Granted', $body));
            $message = "Room successfully GRANTED for " . htmlspecialchars($app['firstname'] . ' ' . $app['surname']) . ". Email notification sent.";

        } elseif ($action === 'revoke' && $app['status'] !== 'revoked') {
            $upd = $conn->prepare("UPDATE hostel_applications SET status='revoked' WHERE id=?");
            $upd->bind_param("i", $appId);
            $upd->execute();

            if ($app['room_id']) {
                $freeRoom = $conn->prepare("UPDATE hostel_rooms SET status='available' WHERE id=?");
                $freeRoom->bind_param("i", $app['room_id']);
                $freeRoom->execute();
            }

            $body = "
                <p>Dear <strong>{$app['firstname']} {$app['surname']}</strong>,</p>
                <p>We regret to inform you that your hostel room allocation has been <strong style='color:red;'>REVOKED</strong>.</p>
                <p>If you believe this is an error, please visit the Student Affairs office.</p>
                <p>You may re-apply if rooms are available.</p>
            ";
            sendEmail($app['email'], 'Hostel Room Revoked — KRK Polytechnic', emailTemplate('Room Allocation Revoked', $body));
            $message = "Room REVOKED for " . htmlspecialchars($app['firstname'] . ' ' . $app['surname']) . ". Email notification sent.";
            $msgType = 'warning';
        }
    }
    } // end DSA role check
}

// --- Pagination ---
$statusFilter = $_GET['status'] ?? 'all';
$perPage = 20;
$page    = max(1, intval($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$whereSql = $statusFilter !== 'all'
    ? "WHERE ha.status = '" . $conn->real_escape_string($statusFilter) . "'"
    : '';

$totalRow = $conn->query("
    SELECT COUNT(*) as c FROM hostel_applications ha $whereSql
")->fetch_assoc();
$totalRows  = $totalRow['c'];
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$sql = "
    SELECT ha.id, s.surname, s.firstname, s.matric_number, s.gender,
           s.school_fees_paid, s.email, f.name AS faculty_name,
           hr.room_number, hr.block, ha.status, ha.hostel_fee_paid, ha.applied_at
    FROM hostel_applications ha
    JOIN students s ON ha.student_id = s.id
    LEFT JOIN faculties f ON s.faculty_id = f.id
    LEFT JOIN hostel_rooms hr ON ha.room_id = hr.id
    $whereSql
    ORDER BY ha.applied_at DESC
    LIMIT $perPage OFFSET $offset
";
$apps = $conn->query($sql);

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <h2 class="page-title"><i class="fa fa-clipboard-list"></i> Hostel Applications</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?= $msgType ?>"><?= $message ?></div>
        <?php endif; ?>

        <!-- Filter tabs -->
        <div class="filter-tabs">
            <?php foreach (['all','pending','granted','revoked'] as $f): ?>
                <a href="?status=<?= $f ?>" class="<?= $statusFilter === $f ? 'active' : '' ?>">
                    <?= ucfirst($f) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fa fa-clipboard-list"></i> Applications List</div>
                <span style="font-size:13px; color:var(--n500);">
                    <?= $totalRows ?> record<?= $totalRows !== 1 ? 's' : '' ?>
                </span>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Matric No.</th>
                                <th>Student Name</th>
                                <th>Gender</th>
                                <th>Faculty</th>
                                <th>Fees Paid</th>
                                <th>Room</th>
                                <th>Status</th>
                                <th>Hostel Fee</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $i = $offset + 1; while ($row = $apps->fetch_assoc()):
                            $b = ['pending'=>'warning','granted'=>'success','revoked'=>'danger'][$row['status']] ?? 'secondary';
                        ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><strong><?= htmlspecialchars($row['matric_number']) ?></strong></td>
                                <td><?= htmlspecialchars($row['surname'] . ', ' . $row['firstname']) ?></td>
                                <td><?= ucfirst($row['gender']) ?></td>
                                <td style="font-size:12px;"><?= htmlspecialchars($row['faculty_name']) ?></td>
                                <td><?= $row['school_fees_paid']
                                    ? '<span class="badge badge-success">Paid</span>'
                                    : '<span class="badge badge-danger">Unpaid</span>' ?></td>
                                <td><?= $row['room_number'] ? 'Blk '.$row['block'].' Rm '.$row['room_number'] : '—' ?></td>
                                <td><span class="badge badge-<?= $b ?>"><?= ucfirst($row['status']) ?></span></td>
                                <td><?= $row['hostel_fee_paid']
                                    ? '<span class="badge badge-success">Paid</span>'
                                    : '<span class="badge badge-secondary">Pending</span>' ?></td>
                                <td style="font-size:12px;"><?= date('d/m/Y', strtotime($row['applied_at'])) ?></td>
                                <td style="white-space:nowrap;">
                                    <?php if (admin_can(['dsa'])): ?>
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
                                    <?php else: ?>
                                    <span style="font-size:12px;color:var(--tx-muted);">View only</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination" style="margin-top:20px;">
            <?php if ($page > 1): ?>
                <a href="?status=<?= $statusFilter ?>&page=<?= $page-1 ?>" class="btn btn-light btn-sm">&laquo; Prev</a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="?status=<?= $statusFilter ?>&page=<?= $p ?>"
                   class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-light' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?status=<?= $statusFilter ?>&page=<?= $page+1 ?>" class="btn btn-light btn-sm">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </main>
</div>

<?php include '../includes/footer.php'; ?>
