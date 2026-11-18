<?php
$cp = basename($_SERVER['PHP_SELF']);
$sName = $_SESSION['student_name'] ?? 'Student';
$nameParts = preg_split('/\s+/', trim($sName));
$initials = count($nameParts) > 1
    ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
    : strtoupper(substr($sName, 0, 1));

// Status lookup for sidebar badges
$_sbApp = null;
if (isset($_SESSION['student_id'])) {
    $_sbSid  = intval($_SESSION['student_id']);
    $_sbStmt = $conn->prepare("SELECT status, hostel_fee_paid FROM hostel_applications WHERE student_id=? ORDER BY applied_at DESC LIMIT 1");
    $_sbStmt->bind_param("i", $_sbSid);
    $_sbStmt->execute();
    $_sbApp = $_sbStmt->get_result()->fetch_assoc();
}

$navItems = [
    ['dashboard.php',         'icon-grid', 'Dashboard'],
    ['application.php',       'icon-bed',  'My hostel'],
    ['payment.php',           'icon-card', 'Hostel fee'],
    ['allocation_letter.php', 'icon-file', 'Allocation slip'],
    ['profile.php',           'icon-user', 'Profile'],
];
?>
<svg class="sprite" aria-hidden="true">
  <symbol id="icon-bell" viewBox="0 0 24 24"><path d="M6 10a6 6 0 1 1 12 0c0 4 1.5 5.5 2 6H4c.5-.5 2-2 2-6Zm4 9a2 2 0 0 0 4 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/></symbol>
  <symbol id="icon-file" viewBox="0 0 24 24"><path d="M7 3h7l4 4v14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm7 0v4h4M9 13h6M9 17h6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/></symbol>
  <symbol id="icon-check" viewBox="0 0 24 24"><path d="M5 12.5 10 17l9-10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="icon-clock" viewBox="0 0 24 24"><path d="M12 7v5l3.5 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="icon-lock" viewBox="0 0 24 24"><path d="M6 11V8a6 6 0 0 1 12 0v3M5 11h14v9a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></symbol>
  <symbol id="icon-alert" viewBox="0 0 24 24"><path d="M12 9v5M12 17h.01M10.3 3.9 2.6 18a1.5 1.5 0 0 0 1.3 2.2h16.2a1.5 1.5 0 0 0 1.3-2.2L13.7 3.9a1.5 1.5 0 0 0-2.6 0Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/></symbol>
  <symbol id="icon-eye" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></symbol>
</svg>

<div class="portal">
  <div class="portal-overlay" data-sidebar-toggle></div>

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
        <span>Student portal</span>
      </div>
    </div>
    <nav class="sidebar-nav">
      <?php foreach ($navItems as [$href, $icon, $label]): ?>
      <a href="<?= $href ?>" class="<?= $cp === $href ? 'active' : '' ?>">
        <svg class="s-icon"><use href="#<?= $icon ?>"></use></svg>
        <?= $label ?>
        <?php if ($href === 'application.php' && $_sbApp && $_sbApp['status'] === 'pending'): ?>
          <span class="badge badge-warning sidebar-badge">Pending</span>
        <?php elseif ($href === 'application.php' && $_sbApp && $_sbApp['status'] === 'granted' && !$_sbApp['hostel_fee_paid']): ?>
          <span class="badge badge-success sidebar-badge">Granted</span>
        <?php elseif ($href === 'payment.php' && $_sbApp && $_sbApp['status'] === 'granted' && !$_sbApp['hostel_fee_paid']): ?>
          <span class="badge badge-warning sidebar-badge">Due</span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
      <a href="logout.php">
        <svg class="s-icon"><use href="#icon-logout"></use></svg>
        Log out
      </a>
    </div>
  </aside>

  <div class="portal-main">
    <header class="portal-topbar">
      <div class="portal-topbar-left">
        <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-label="Open menu">
          <svg class="icon"><use href="#icon-menu"></use></svg>
        </button>
        <span class="portal-topbar-session"><?= htmlspecialchars($student['academic_session'] ?? '2025/2026') ?> session &middot; <?= ucfirst($student['semester'] ?? 'first') ?> semester</span>
      </div>
      <div class="portal-topbar-right">
        <button class="icon-btn" type="button" aria-label="Notifications">
          <svg class="icon"><use href="#icon-bell"></use></svg>
        </button>
        <div class="portal-user">
          <div class="avatar"><?= htmlspecialchars($initials) ?></div>
          <div class="portal-user-text">
            <strong><?= htmlspecialchars($sName) ?></strong>
            <span>Student</span>
          </div>
        </div>
      </div>
    </header>
    <div class="portal-content">
