<?php
$cp = basename($_SERVER['PHP_SELF']);
$sName = $_SESSION['student_name'] ?? 'Student';
$initial = strtoupper(substr($sName, 0, 1));

// Quick status lookup for sidebar badges
$_sbApp = null;
if (isset($_SESSION['student_id'])) {
    $_sbSid  = intval($_SESSION['student_id']);
    $_sbStmt = $conn->prepare("SELECT status, hostel_fee_paid FROM hostel_applications WHERE student_id=? ORDER BY applied_at DESC LIMIT 1");
    $_sbStmt->bind_param("i", $_sbSid);
    $_sbStmt->execute();
    $_sbApp = $_sbStmt->get_result()->fetch_assoc();
}
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-icon student-icon" style="font-size:16px;font-weight:900;"><?= $initial ?></div>
    <div class="sidebar-logo-text">
      <strong><?= htmlspecialchars($sName) ?></strong>
      <span>Student Account</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section-title">My Portal</div>
    <a href="dashboard.php"       class="<?= $cp==='dashboard.php'      ?'active':'' ?>"><span class="s-icon"><i class="fa fa-th-large"></i></span>Dashboard</a>
    <a href="application.php"     class="<?= $cp==='application.php'    ?'active':'' ?>">
      <span class="s-icon"><i class="fa fa-bed"></i></span>Hostel Application
      <?php if ($_sbApp && $_sbApp['status']==='pending'): ?>
        <span class="sidebar-badge sidebar-badge-warning">Pending</span>
      <?php elseif ($_sbApp && $_sbApp['status']==='granted' && !$_sbApp['hostel_fee_paid']): ?>
        <span class="sidebar-badge sidebar-badge-success">Granted!</span>
      <?php endif; ?>
    </a>
    <a href="payment.php"         class="<?= $cp==='payment.php'        ?'active':'' ?>">
      <span class="s-icon"><i class="fa fa-credit-card"></i></span>Pay Hostel Fee
      <?php if ($_sbApp && $_sbApp['status']==='granted' && !$_sbApp['hostel_fee_paid']): ?>
        <span class="sidebar-badge sidebar-badge-warning">Due</span>
      <?php endif; ?>
    </a>
    <a href="allocation_letter.php" class="<?= $cp==='allocation_letter.php'?'active':'' ?>"><span class="s-icon"><i class="fa fa-file-alt"></i></span>Allocation Letter</a>
    <div class="sidebar-section-title">Account</div>
    <a href="profile.php" class="<?= $cp==='profile.php'?'active':'' ?>"><span class="s-icon"><i class="fa fa-user-cog"></i></span>My Profile</a>
  </nav>
  <div class="sidebar-footer">
    <a href="logout.php"><span class="s-icon"><i class="fa fa-sign-out-alt"></i></span>Sign Out</a>
  </div>
</aside>
