<?php
$pageTitle = "Email Logs";
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }
require_once 'auth_guard.php';
require_admin(); // All roles can view email logs

$perPage = 25;
$page    = max(1, intval($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$search   = trim($_GET['q'] ?? '');
$whereSql = '';
if ($search) {
    $esc     = $conn->real_escape_string($search);
    $whereSql = " WHERE recipient_email LIKE '%$esc%' OR subject LIKE '%$esc%'";
}

$totalRow   = $conn->query("SELECT COUNT(*) AS c FROM email_logs$whereSql")->fetch_assoc();
$totalRows  = $totalRow['c'];
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$stmt = $conn->query("
    SELECT * FROM email_logs $whereSql
    ORDER BY sent_at DESC
    LIMIT $perPage OFFSET $offset
");

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <h2 class="page-title"><i class="fa fa-envelope-open-text"></i> Email Notification Logs</h2>

        <!-- Search -->
        <form method="GET" style="margin-bottom:20px; display:flex; gap:10px;">
            <input type="text" name="q" class="form-control" style="max-width:340px;"
                   placeholder="Search by recipient or subject..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search): ?><a href="email_logs.php" class="btn btn-light">Clear</a><?php endif; ?>
        </form>

        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fa fa-envelope"></i> Email Logs</div>
                <span style="font-size:13px; color:var(--n500);">
                    <?= $totalRows ?> email<?= $totalRows !== 1 ? 's' : '' ?> total
                </span>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Recipient</th>
                                <th>Subject</th>
                                <th>Date Sent</th>
                                <th>View</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $i = $offset + 1; while ($log = $stmt->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($log['recipient_email']) ?></td>
                                <td><?= htmlspecialchars($log['subject']) ?></td>
                                <td><?= date('d M Y H:i', strtotime($log['sent_at'])) ?></td>
                                <td>
                                    <button class="btn btn-light btn-sm"
                                            onclick="showEmailBody(<?= $log['id'] ?>)">
                                        <i class="fa fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <!-- Hidden row with message body -->
                            <tr id="body-<?= $log['id'] ?>" style="display:none;">
                                <td colspan="5" style="background:#f8f9fa; padding:16px;">
                                    <div style="border:1px solid var(--border); border-radius:6px; overflow:hidden;">
                                        <div style="background:var(--primary); color:#fff; padding:8px 14px; font-size:13px;">
                                            <strong>To:</strong> <?= htmlspecialchars($log['recipient_email']) ?>
                                            &nbsp;&nbsp; <strong>Subject:</strong> <?= htmlspecialchars($log['subject']) ?>
                                        </div>
                                        <div style="padding:16px; background:#fff;">
                                            <?= $log['message'] /* safe: HTML stored by our own mailer */ ?>
                                        </div>
                                    </div>
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

<script>
function showEmailBody(id) {
    const row = document.getElementById('body-' + id);
    if (!row) return;
    const isVisible = row.style.display !== 'none';
    row.style.display = isVisible ? 'none' : 'table-row';
}
</script>

<?php include '../includes/footer.php'; ?>
