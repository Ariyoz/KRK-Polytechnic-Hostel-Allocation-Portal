<?php
$pageTitle = "Hostel Rooms";
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }
require_once 'auth_guard.php';
require_admin(); // All roles can view rooms; edit restricted below

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$message = '';
$msgType = 'success';

// --- Update room status (ict/dsa only) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_id'], $_POST['new_status'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
    $role = $_SESSION['admin_role'];
    if (!in_array($role, ['ict','dsa'])) {
        $message = 'You do not have permission to edit room status.';
        $msgType = 'danger';
    } else {
        $rid    = intval($_POST['room_id']);
        $ns     = $_POST['new_status'];
        $valid  = ['available','occupied','maintenance'];
        if (in_array($ns, $valid)) {
            $upd = $conn->prepare("UPDATE hostel_rooms SET status=? WHERE id=?");
            $upd->bind_param("si", $ns, $rid);
            $upd->execute();
            $message = 'Room status updated to ' . ucfirst($ns) . '.';
        }
    }
}

// --- Delete room (ict/dsa, only if available) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_room_id'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
    $role = $_SESSION['admin_role'];
    if (!in_array($role, ['ict','dsa'])) {
        $message = 'You do not have permission to delete rooms.';
        $msgType = 'danger';
    } else {
        $rid    = intval($_POST['delete_room_id']);
        // Safety: only delete if no linked applications
        $check  = $conn->prepare("SELECT COUNT(*) AS c FROM hostel_applications WHERE room_id=?");
        $check->bind_param("i", $rid);
        $check->execute();
        $cnt = $check->get_result()->fetch_assoc()['c'];
        if ($cnt > 0) {
            $message = 'Cannot delete room — it has linked applications.';
            $msgType = 'danger';
        } else {
            $del = $conn->prepare("DELETE FROM hostel_rooms WHERE id=?");
            $del->bind_param("i", $rid);
            $del->execute();
            $message = 'Room deleted successfully.';
        }
    }
}

// Stats
$counts = $conn->query("SELECT status, COUNT(*) as c FROM hostel_rooms GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$countMap = [];
foreach ($counts as $c) $countMap[$c['status']] = $c['c'];
$total = array_sum(array_column($counts, 'c'));

// Filter + pagination
$filterStatus = $_GET['status'] ?? 'all';
$perPage = 25;
$page    = max(1, intval($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$whereSql = $filterStatus !== 'all'
    ? "WHERE status='" . $conn->real_escape_string($filterStatus) . "'"
    : '';

$totalRow   = $conn->query("SELECT COUNT(*) AS c FROM hostel_rooms $whereSql")->fetch_assoc();
$totalRows  = $totalRow['c'];
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$rooms = $conn->query("SELECT * FROM hostel_rooms $whereSql ORDER BY block, room_number LIMIT $perPage OFFSET $offset");

$canEdit = in_array($_SESSION['admin_role'], ['ict','dsa']);
$roleLabel = ['dsa'=>'Dean of Student Affairs','registrar'=>'Registrar Office','ict'=>'ICT Department'][$_SESSION['admin_role'] ?? ''] ?? strtoupper($_SESSION['admin_role'] ?? '');

$pageCss = ['admin.css','admin-extra.css'];
include '../includes/header.php';
?>

<?php include 'sidebar.php'; ?>

<div class="page-head page-head-row">
                <div>
                    <span class="eyebrow">Hostel management</span>
                    <h1>Hostel rooms</h1>
                    <p class="sub">
                        <?= $total ?> of 200 beds on file —
                        <span class="badge badge-success"><?= $countMap['available'] ?? 0 ?> available</span>
                        <span class="badge badge-danger"><?= $countMap['occupied'] ?? 0 ?> occupied</span>
                        <span class="badge badge-warning"><?= $countMap['maintenance'] ?? 0 ?> maintenance</span>
                    </p>
                </div>
                <?php if ($canEdit): ?>
                    <a href="upload_rooms.php" class="btn btn-dark btn-sm">Add rooms</a>
                <?php endif; ?>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="filter-row">
                <?php foreach (['all','available','occupied','maintenance'] as $f): ?>
                    <a href="?status=<?= $f ?>" class="btn btn-sm <?= $filterStatus === $f ? 'btn-dark' : 'btn-outline' ?>"><?= ucfirst($f) ?></a>
                <?php endforeach; ?>
                <span class="filter-spacer text-sm muted"><?= $totalRows ?> room<?= $totalRows !== 1 ? 's' : '' ?></span>
            </div>

            <div class="card">
                <?php if ($totalRows === 0): ?>
                    <div class="empty">
                        <svg class="icon empty-icon"><use href="#icon-bed"></use></svg>
                        <p class="empty-title">No rooms match this filter</p>
                        <p>Try a different status, or add rooms to the hostel.</p>
                    </div>
                <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Block</th>
                                <th>Room number</th>
                                <th>Gender</th>
                                <th>Status</th>
                                <th>Added on</th>
                                <?php if ($canEdit): ?><th>Actions</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $i = $offset + 1; while ($room = $rooms->fetch_assoc()):
                            $b = ['available'=>'success','occupied'=>'danger','maintenance'=>'warning'][$room['status']] ?? 'neutral';
                        ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($room['block']) ?></td>
                                <td><?= htmlspecialchars($room['room_number']) ?></td>
                                <td><?= ucfirst($room['gender']) ?></td>
                                <td><span class="badge badge-<?= $b ?>"><?= ucfirst($room['status']) ?></span></td>
                                <td><?= date('d M Y', strtotime($room['created_at'])) ?></td>
                                <?php if ($canEdit): ?>
                                <td class="room-actions">
                                    <!-- Change status dropdown -->
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                        <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                        <select name="new_status" class="select select-sm">
                                            <?php foreach (['available','occupied','maintenance'] as $s): ?>
                                            <option value="<?= $s ?>" <?= $room['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-outline btn-sm" title="Update status">Save</button>
                                    </form>
                                    <!-- Delete -->
                                    <form method="POST" class="inline-form"
                                          onsubmit="return confirm('Delete room <?= htmlspecialchars(addslashes($room['room_number'])) ?>? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                        <input type="hidden" name="delete_room_id" value="<?= $room['id'] ?>">
                                        <button type="submit" class="btn btn-ghost btn-sm" title="Delete room">Delete</button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pager">
                <?php if ($page > 1): ?>
                    <a href="?status=<?= $filterStatus ?>&page=<?= $page-1 ?>" class="btn btn-outline btn-sm">&laquo; Prev</a>
                <?php endif; ?>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="?status=<?= $filterStatus ?>&page=<?= $p ?>"
                       class="btn btn-sm <?= $p === $page ? 'btn-dark' : 'btn-outline' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?status=<?= $filterStatus ?>&page=<?= $page+1 ?>" class="btn btn-outline btn-sm">Next &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

    </div><!-- portal-content -->
  </div><!-- portal-main -->
</div><!-- portal -->

<?php include '../includes/footer.php'; ?>
