<?php
$cp = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['admin_role'] ?? '';
$roleLabel = ['dsa'=>'Dean of Student Affairs','registrar'=>'Registrar Office','ict'=>'ICT Department'][$role] ?? strtoupper($role);
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$initials = '';
foreach (preg_split('/\s+/', trim($adminName)) as $part) {
    if ($part !== '') $initials .= strtoupper($part[0]);
    if (strlen($initials) >= 2) break;
}
if ($initials === '') $initials = 'AD';

// Quick badge counts
$_sbPending    = $conn->query("SELECT COUNT(*) AS c FROM hostel_applications WHERE status='pending'")->fetch_assoc()['c'];
$_sbUnverified = $conn->query("SELECT COUNT(*) AS c FROM students WHERE school_fees_paid=0 AND is_activated=1")->fetch_assoc()['c'];
?>
<div class="portal">
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="sidebar-logo-icon">
        <svg class="sidebar-logo-mark" viewBox="0 0 40 46" aria-hidden="true">
          <path d="M20 2 4 8v14c0 12 7 19 16 22 9-3 16-10 16-22V8Z" fill="#102a4a" stroke="#e8a317" stroke-width="2"/>
          <path d="M13 24l7-6 7 6M15 22v8h10v-8" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="sidebar-logo-text">
        <strong>KRK Polytechnic</strong>
        <span>Staff portal</span>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-section-title">Main</div>
      <a href="dashboard.php" class="<?= $cp === 'dashboard.php' ? 'active' : '' ?>">
        <svg class="s-icon"><use href="#icon-grid"></use></svg> Dashboard
      </a>
      <a href="applications.php" class="<?= $cp === 'applications.php' ? 'active' : '' ?>">
        <svg class="s-icon"><use href="#icon-list"></use></svg> Applications
        <?php if ($_sbPending > 0): ?>
          <span class="badge badge-warning sidebar-badge"><?= $_sbPending ?></span>
        <?php endif; ?>
      </a>
      <a href="students.php" class="<?= in_array($cp, ['students.php', 'student_detail.php']) ? 'active' : '' ?>">
        <svg class="s-icon"><use href="#icon-user"></use></svg> Students
      </a>
      <a href="rooms.php" class="<?= $cp === 'rooms.php' ? 'active' : '' ?>">
        <svg class="s-icon"><use href="#icon-bed"></use></svg> Hostel Rooms
      </a>
      <?php if (in_array($role, ['ict', 'dsa'])): ?>
      <a href="upload_rooms.php" class="<?= $cp === 'upload_rooms.php' ? 'active' : '' ?>">
        <svg class="s-icon" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg> Add Rooms
      </a>
      <?php endif; ?>

      <?php if (in_array($role, ['registrar', 'dsa'])): ?>
      <div class="sidebar-section-title">Finance</div>
      <a href="fees.php" class="<?= $cp === 'fees.php' ? 'active' : '' ?>">
        <svg class="s-icon" viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="12" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg> Fee Verification
        <?php if ($_sbUnverified > 0): ?>
          <span class="badge badge-warning sidebar-badge"><?= $_sbUnverified ?></span>
        <?php endif; ?>
      </a>
      <?php endif; ?>

      <div class="sidebar-section-title">Analytics</div>
      <a href="reports.php" class="<?= $cp === 'reports.php' ? 'active' : '' ?>">
        <svg class="s-icon" viewBox="0 0 24 24"><path d="M4 20V11M10 20V4M16 20v-7M3 20h18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg> Reports
      </a>
      <a href="email_logs.php" class="<?= $cp === 'email_logs.php' ? 'active' : '' ?>">
        <svg class="s-icon" viewBox="0 0 24 24"><path d="M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm0 0 8 7 8-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg> Email Logs
      </a>

      <?php if ($role === 'dsa'): ?>
      <div class="sidebar-section-title">System</div>
      <a href="settings.php" class="<?= $cp === 'settings.php' ? 'active' : '' ?>">
        <svg class="s-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg> Settings
      </a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
      <a href="logout.php"><svg class="s-icon"><use href="#icon-logout"></use></svg> Sign Out</a>
    </div>
  </aside>
  <div class="portal-overlay"></div>

  <div class="portal-main">
    <header class="portal-topbar">
      <button type="button" class="icon-btn sidebar-toggle" data-sidebar-toggle aria-label="Open menu">
        <svg class="icon"><use href="#icon-menu"></use></svg>
      </button>
      <div class="portal-topbar-session">Session 2025/2026 &middot; First semester</div>
      <div class="portal-topbar-right">
        <button type="button" class="icon-btn" aria-label="Notifications">
          <svg class="icon" viewBox="0 0 24 24"><path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 19a2 2 0 0 0 4 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </button>
        <div class="portal-user">
          <div class="avatar"><?= htmlspecialchars($initials) ?></div>
          <div class="portal-user-text">
            <strong><?= htmlspecialchars($adminName) ?></strong>
            <span><?= htmlspecialchars($roleLabel) ?></span>
          </div>
        </div>
      </div>
    </header>
    <div class="portal-content">
