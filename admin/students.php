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

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <h2 class="page-title"><i class="fa fa-users"></i> Registered Students</h2>

        <!-- Search -->
        <form method="GET" class="search-bar" style="margin-bottom:20px;">
            <div class="search-input-wrap">
                <i class="fa fa-search"></i>
                <input type="text" name="q" class="form-control"
                       placeholder="Search by name, matric, email..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search): ?><a href="students.php" class="btn btn-light">Clear</a><?php endif; ?>
        </form>

        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fa fa-users"></i> Students List</div>
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
                                <th>Gender</th>
                                <th>Faculty</th>
                                <th>Dept</th>
                                <th>Level</th>
                                <th>Email</th>
                                <th>Activated</th>
                                <th>Fees Paid</th>
                                <th>View</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $i = $offset + 1; while ($row = $students->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><strong><?= htmlspecialchars($row['matric_number']) ?></strong></td>
                                <td><?= htmlspecialchars($row['surname'] . ', ' . $row['firstname']) ?></td>
                                <td><?= ucfirst($row['gender']) ?></td>
                                <td style="font-size:12px;"><?= htmlspecialchars($row['faculty_name']) ?></td>
                                <td style="font-size:12px;"><?= htmlspecialchars($row['dept_name']) ?></td>
                                <td><?= htmlspecialchars($row['level']) ?></td>
                                <td style="font-size:12px;"><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= $row['is_activated']
                                    ? '<span class="badge badge-success">Yes</span>'
                                    : '<span class="badge badge-danger">No</span>' ?></td>
                                <td><?= $row['school_fees_paid']
                                    ? '<span class="badge badge-success">Paid</span>'
                                    : '<span class="badge badge-warning">Unpaid</span>' ?></td>
                                <td>
                                    <a href="student_detail.php?id=<?= $row['id'] ?>" class="btn btn-light btn-sm">
                                        <i class="fa fa-eye"></i>
                                    </a>
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
