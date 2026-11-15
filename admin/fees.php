<?php
$pageTitle = "Fee Verification";
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }

require_once 'auth_guard.php';
require_role(['dsa','registrar'], 'Only the Registrar or DSA can access fee verification.');

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$message = '';
$msgType = 'success';

// Toggle fee status — POST with CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sid'], $_POST['toggle'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
    $sid    = intval($_POST['sid']);
    $status = intval($_POST['toggle']);
    $q_keep = trim($_POST['q_keep'] ?? '');
    $upd    = $conn->prepare("UPDATE students SET school_fees_paid=? WHERE id=?");
    $upd->bind_param("ii", $status, $sid);
    $upd->execute();
    $flash  = $status ? 'paid' : 'unpaid';
    $redir  = 'fees.php?toggled=' . $flash . ($q_keep ? '&q=' . urlencode($q_keep) : '');
    header("Location: $redir"); exit();
}

$search = trim($_GET['q'] ?? '');

// Flash message from Post-Redirect-Get
$message = '';
$msgType = 'success';
if (isset($_GET['toggled'])) {
    $message = $_GET['toggled'] === 'paid' ? 'School fees marked as PAID.' : 'School fees marked as UNPAID.';
    $msgType = $_GET['toggled'] === 'paid' ? 'success' : 'warning';
}

$perPage = 25;
$page    = max(1, intval($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$whereSql = '';
if ($search) {
    $esc     = $conn->real_escape_string($search);
    $whereSql = " WHERE s.matric_number LIKE '%$esc%' OR s.surname LIKE '%$esc%' OR s.email LIKE '%$esc%'";
}

$totalRow   = $conn->query("SELECT COUNT(*) AS c FROM students s$whereSql")->fetch_assoc();
$totalRows  = $totalRow['c'];
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$sql = "
    SELECT s.id, s.matric_number, s.surname, s.firstname, s.gender,
           s.level, s.email, s.phone, s.school_fees_paid,
           f.name AS faculty_name
    FROM students s
    LEFT JOIN faculties f ON s.faculty_id = f.id
    $whereSql
    ORDER BY s.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$students = $conn->query($sql);

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <h2 class="page-title"><i class="fa fa-money-bill-wave"></i> School Fee Verification</h2>
        <p style="color:var(--n500); margin-bottom:20px; font-size:14px;">
            Only students verified as having paid minimum school fees can apply for hostel accommodation.
        </p>

        <?php if ($message): ?>
            <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="GET" class="search-bar" style="margin-bottom:20px;">
            <div class="search-input-wrap">
                <i class="fa fa-search"></i>
                <input type="text" name="q" class="form-control"
                       placeholder="Search by name, matric, email..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search): ?><a href="fees.php" class="btn btn-light">Clear</a><?php endif; ?>
        </form>

        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fa fa-list"></i> Students &mdash; Fee Status</div>
                <span style="font-size:13px; color:var(--n500);">
                    <?= $totalRows ?> student<?= $totalRows !== 1 ? 's' : '' ?>
                </span>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Matric No.</th>
                                <th>Name</th>
                                <th>Faculty</th>
                                <th>Level</th>
                                <th>Email</th>
                                <th>Fee Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $i = $offset + 1; while ($row = $students->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><strong><?= htmlspecialchars($row['matric_number']) ?></strong></td>
                                <td><?= htmlspecialchars($row['surname'] . ', ' . $row['firstname']) ?></td>
                                <td style="font-size:12px;"><?= htmlspecialchars($row['faculty_name']) ?></td>
                                <td><?= htmlspecialchars($row['level']) ?></td>
                                <td style="font-size:12px;"><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= $row['school_fees_paid']
                                    ? '<span class="badge badge-success">Paid</span>'
                                    : '<span class="badge badge-warning">Unpaid</span>' ?></td>
                                <td>
                                    <?php $newStatus = $row['school_fees_paid'] ? 0 : 1; ?>
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm('<?= $row['school_fees_paid'] ? 'Mark fees as UNPAID?' : 'Verify fees as PAID?' ?>');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                        <input type="hidden" name="sid" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="toggle" value="<?= $newStatus ?>">
                                        <input type="hidden" name="q_keep" value="<?= htmlspecialchars($search) ?>">
                                        <?php if ($row['school_fees_paid']): ?>
                                            <button type="submit" class="btn btn-danger btn-sm">Mark Unpaid</button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-success btn-sm">Verify Paid</button>
                                        <?php endif; ?>
                                    </form>
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
                <a href="?q=<?= urlencode($search) ?>&page=<?= $page-1 ?>" class="btn btn-light btn-sm">&laquo; Prev</a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="?q=<?= urlencode($search) ?>&page=<?= $p ?>"
                   class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-light' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?q=<?= urlencode($search) ?>&page=<?= $page+1 ?>" class="btn btn-light btn-sm">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </main>
</div>

<?php include '../includes/footer.php'; ?>
