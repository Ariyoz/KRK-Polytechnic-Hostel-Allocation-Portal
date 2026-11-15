<?php
$cp = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['admin_role'] ?? '';
$roleLabel = ['dsa'=>'Dean of Student Affairs','registrar'=>'Registrar Office','ict'=>'ICT Department'][$role] ?? strtoupper($role);
// Quick badge counts
$_sbPending   = $conn->query("SELECT COUNT(*) AS c FROM hostel_applications WHERE status='pending'")->fetch_assoc()['c'];
$_sbUnverified = $conn->query("SELECT COUNT(*) AS c FROM students WHERE school_fees_paid=0 AND is_activated=1")->fetch_assoc()['c'];
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-icon" style="font-size:13px;font-weight:900;letter-spacing:-.5px;">KRK</div>
    <div class="sidebar-logo-text">
      <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></strong>
      <span><?= htmlspecialchars($roleLabel) ?></span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section-title">Main</div>
    <a href="dashboard.php"    class="<?= $cp==='dashboard.php'   ?'active':'' ?>"><span class="s-icon"><i class="fa fa-th-large"></i></span>Dashboard</a>
    <a href="applications.php" class="<?= $cp==='applications.php'?'active':'' ?>">
      <span class="s-icon"><i class="fa fa-clipboard-list"></i></span>Applications
      <?php if ($_sbPending > 0): ?>
        <span class="sidebar-badge sidebar-badge-warning"><?= $_sbPending ?></span>
      <?php endif; ?>
    </a>
    <a href="students.php"     class="<?= in_array($cp,['students.php','student_detail.php'])?'active':'' ?>"><span class="s-icon"><i class="fa fa-users"></i></span>Students</a>
    <a href="rooms.php"        class="<?= $cp==='rooms.php'       ?'active':'' ?>"><span class="s-icon"><i class="fa fa-bed"></i></span>Hostel Rooms</a>
    <?php if(in_array($role,['ict','dsa'])): ?>
    <a href="upload_rooms.php" class="<?= $cp==='upload_rooms.php'?'active':'' ?>"><span class="s-icon"><i class="fa fa-plus-circle"></i></span>Add Rooms</a>
    <?php endif; ?>
    <?php if(in_array($role,['registrar','dsa'])): ?>
    <div class="sidebar-section-title">Finance</div>
    <a href="fees.php" class="<?= $cp==='fees.php'?'active':'' ?>">
      <span class="s-icon"><i class="fa fa-money-bill-wave"></i></span>Fee Verification
      <?php if ($_sbUnverified > 0 && in_array($role,['registrar','dsa'])): ?>
        <span class="sidebar-badge sidebar-badge-warning"><?= $_sbUnverified ?></span>
      <?php endif; ?>
    </a>
    <?php endif; ?>
    <div class="sidebar-section-title">Analytics</div>
    <a href="reports.php"    class="<?= $cp==='reports.php'   ?'active':'' ?>"><span class="s-icon"><i class="fa fa-chart-pie"></i></span>Reports</a>
    <a href="email_logs.php" class="<?= $cp==='email_logs.php'?'active':'' ?>"><span class="s-icon"><i class="fa fa-envelope"></i></span>Email Logs</a>
    <?php if($role==='dsa'): ?>
    <div class="sidebar-section-title">System</div>
    <a href="settings.php" class="<?= $cp==='settings.php'?'active':'' ?>"><span class="s-icon"><i class="fa fa-cog"></i></span>Settings</a>
    <?php endif; ?>
  </nav>
  <div class="sidebar-footer">
    <a href="logout.php"><span class="s-icon"><i class="fa fa-sign-out-alt"></i></span>Sign Out</a>
  </div>
</aside>
