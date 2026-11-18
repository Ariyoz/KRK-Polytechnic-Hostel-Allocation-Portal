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

$roleLabel = ['dsa'=>'Dean of Student Affairs','registrar'=>'Registrar Office','ict'=>'ICT Department'][$_SESSION['admin_role'] ?? ''] ?? strtoupper($_SESSION['admin_role'] ?? '');

$pageCss = ['admin.css','admin-extra.css'];
include '../includes/header.php';
?>

<?php include 'sidebar.php'; ?>

<div class="page-head">
                <span class="eyebrow">Analytics</span>
                <h1>Email notification logs</h1>
                <p class="sub">Every automated email the portal has sent to students and staff.</p>
            </div>

            <form method="GET" class="filter-row">
                <input type="text" name="q" class="input"
                       placeholder="Search by recipient or subject" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-dark btn-sm">Search</button>
                <?php if ($search): ?><a href="email_logs.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
                <span class="filter-spacer text-sm muted"><?= $totalRows ?> email<?= $totalRows !== 1 ? 's' : '' ?></span>
            </form>

            <div class="card">
                <?php if ($totalRows === 0): ?>
                    <div class="empty">
                        <svg class="icon empty-icon"><use href="#icon-list"></use></svg>
                        <p class="empty-title">No emails found</p>
                        <p>Try a different search term.</p>
                    </div>
                <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Recipient</th>
                                <th>Subject</th>
                                <th>Date sent</th>
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
                                    <button class="btn btn-outline btn-sm"
                                            onclick="showEmailBody(<?= $log['id'] ?>)">View</button>
                                </td>
                            </tr>
                            <!-- Hidden row with message body -->
                            <tr id="body-<?= $log['id'] ?>" class="is-hidden">
                                <td colspan="5" class="log-preview">
                                    <div class="log-preview-head">
                                        <strong>To:</strong> <?= htmlspecialchars($log['recipient_email']) ?>
                                        &nbsp;&nbsp; <strong>Subject:</strong> <?= htmlspecialchars($log['subject']) ?>
                                    </div>
                                    <div class="log-preview-body">
                                        <?= $log['message'] /* safe: HTML stored by our own mailer */ ?>
                                    </div>
                                </td>
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
                    <a href="?q=<?= urlencode($search) ?>&page=<?= $page-1 ?>" class="btn btn-outline btn-sm">&laquo; Prev</a>
                <?php endif; ?>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="?q=<?= urlencode($search) ?>&page=<?= $p ?>"
                       class="btn btn-sm <?= $p === $page ? 'btn-dark' : 'btn-outline' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?q=<?= urlencode($search) ?>&page=<?= $page+1 ?>" class="btn btn-outline btn-sm">Next &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

    </div><!-- portal-content -->
  </div><!-- portal-main -->
</div><!-- portal -->

<script>
function showEmailBody(id) {
    const row = document.getElementById('body-' + id);
    if (!row) return;
    row.classList.toggle('is-hidden');
}
</script>

<?php include '../includes/footer.php'; ?>
