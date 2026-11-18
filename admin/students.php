<?php
$pageTitle = "Students";
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }
require_once 'auth_guard.php';
require_admin(); // All roles can view students

$search  = trim($_GET['q'] ?? '');
$perPage = 25;
$page    = max(1, intval($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$whereSql = '';
if ($search) {
    $esc = $conn->real_escape_string($search);
    $whereSql = " WHERE s.matric_number LIKE '%$esc%'
                   OR s.surname LIKE '%$esc%'
                   OR s.firstname LIKE '%$esc%'
                   OR s.email LIKE '%$esc%'";
}

$totalRow   = $conn->query("SELECT COUNT(*) AS c FROM students s$whereSql")->fetch_assoc();
$totalRows  = $totalRow['c'];
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$sql = "
    SELECT s.id, s.matric_number, s.surname, s.firstname, s.gender,
           s.level, s.semester, s.phone, s.email,
           s.school_fees_paid, s.is_activated,
           f.name AS faculty_name, d.name AS dept_name
    FROM students s
    LEFT JOIN faculties f ON s.faculty_id = f.id
    LEFT JOIN departments d ON s.department_id = d.id
    $whereSql
    ORDER BY s.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$students = $conn->query($sql);

$pageCss = ['admin.css'];
include '../includes/header.php';
?>

<?php include 'sidebar.php'; ?>

<div class="page-head">
    <div class="eyebrow">Admin</div>
    <h1>Registered students</h1>
    <p class="sub">Search the student register and open a record for full detail.</p>
</div>

<form method="GET" class="search-row">
    <input type="text" name="q" class="input" aria-label="Search students"
           placeholder="Search by name, matric number, email&hellip;" value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <?php if ($search): ?><a href="students.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
    <span class="count"><?= $totalRows ?> student<?= $totalRows !== 1 ? 's' : '' ?></span>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Matric no.</th>
                    <th>Name</th>
                    <th>Gender</th>
                    <th>Faculty</th>
                    <th>Dept</th>
                    <th>Level</th>
                    <th>Email</th>
                    <th>Activated</th>
                    <th>Fees paid</th>
                    <th>View</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($totalRows === 0): ?>
                <tr><td colspan="11">
                    <div class="empty">
                        <div class="empty-title">No students found</div>
                        <p><?= $search ? 'No records match "' . htmlspecialchars($search) . '".' : 'No students have registered yet.' ?></p>
                    </div>
                </td></tr>
            <?php endif; ?>
            <?php $i = $offset + 1; while ($row = $students->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($row['matric_number']) ?></strong></td>
                    <td><?= htmlspecialchars($row['surname'] . ', ' . $row['firstname']) ?></td>
                    <td><?= ucfirst($row['gender']) ?></td>
                    <td><?= htmlspecialchars($row['faculty_name']) ?></td>
                    <td><?= htmlspecialchars($row['dept_name']) ?></td>
                    <td><?= htmlspecialchars($row['level']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= $row['is_activated']
                        ? '<span class="badge badge-success">Yes</span>'
                        : '<span class="badge badge-danger">No</span>' ?></td>
                    <td><?= $row['school_fees_paid']
                        ? '<span class="badge badge-success">Paid</span>'
                        : '<span class="badge badge-warning">Unpaid</span>' ?></td>
                    <td>
                        <a href="student_detail.php?id=<?= $row['id'] ?>" class="btn btn-outline btn-sm">View</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<div class="pager">
    <?php if ($page > 1): ?>
        <a href="?q=<?= urlencode($search) ?>&page=<?= $page-1 ?>" class="btn btn-outline btn-sm">&laquo; Prev</a>
    <?php endif; ?>
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="?q=<?= urlencode($search) ?>&page=<?= $p ?>"
           class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-outline' ?>"><?= $p ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
        <a href="?q=<?= urlencode($search) ?>&page=<?= $page+1 ?>" class="btn btn-outline btn-sm">Next &raquo;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

    </div><!-- portal-content -->
  </div><!-- portal-main -->
</div><!-- portal -->

<?php include '../includes/footer.php'; ?>
