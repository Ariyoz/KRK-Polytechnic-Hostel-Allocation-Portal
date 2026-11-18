<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    // Localhost: files are at /hostel_portal/ — Live: files are at htdocs root
    $base   = (strpos($script, '/hostel_portal/') !== false) ? '/hostel_portal' : '';
    define('BASE_URL', $scheme . '://' . $host . $base);
}
$noHeader = $noHeader ?? false;

// Dashboard pages have a sidebar — no need for the top-nav toggle
$sidebarPages = [
    'dashboard.php', 'applications.php', 'students.php', 'student_detail.php',
    'rooms.php', 'upload_rooms.php', 'fees.php', 'reports.php',
    'email_logs.php', 'settings.php',
    'application.php', 'payment.php', 'profile.php', 'allocation_letter.php',
];
$hasSidebar = in_array(basename($_SERVER['PHP_SELF']), $sidebarPages);

// $pageCss: optional array of filenames in assets/css/, loaded after app.css.
// Set it before including this file, e.g. $pageCss = ['public.css'];
$pageCss = $pageCss ?? [];

$bodyClass = '';
if ($noHeader)   $bodyClass .= ' no-header';
if ($hasSidebar) $bodyClass .= ' has-sidebar';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?>KRK Polytechnic Hostel Portal</title>
<meta name="base_url" content="<?= BASE_URL ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
<?php foreach ($pageCss as $cssFile): ?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= htmlspecialchars($cssFile) ?>">
<?php endforeach; ?>
</head>
<body class="<?= trim($bodyClass) ?>">

<!-- Shared inline icon sprite used by the header/footer nav -->
<svg style="display:none" aria-hidden="true">
  <symbol id="icon-home" viewBox="0 0 24 24"><path d="M4 11.5 12 4l8 7.5V20a1 1 0 0 1-1 1h-4v-6H9v6H5a1 1 0 0 1-1-1Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></symbol>
  <symbol id="icon-bed" viewBox="0 0 24 24"><path d="M3 18v-7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v7M3 18v2M21 18v2M3 13h18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
  <symbol id="icon-login" viewBox="0 0 24 24"><path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4M10 16l5-4-5-4M3 12h11" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="icon-grid" viewBox="0 0 24 24"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></symbol>
  <symbol id="icon-card" viewBox="0 0 24 24"><path d="M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm0 4h18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></symbol>
  <symbol id="icon-user" viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 9a7 7 0 0 1 14 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
  <symbol id="icon-logout" viewBox="0 0 24 24"><path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4M14 16l5-4-5-4M19 12H8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="icon-shield" viewBox="0 0 24 24"><path d="M12 3 5 6v6c0 4.2 3 7.5 7 9 4-1.5 7-4.8 7-9V6Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></symbol>
  <symbol id="icon-list" viewBox="0 0 24 24"><path d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
  <symbol id="icon-menu" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
  <symbol id="icon-close" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
  <symbol id="icon-arrow-right" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
</svg>

<?php if (!$noHeader && !$hasSidebar): ?>
<header class="site-header">
  <div class="container site-header-inner">

    <a href="<?= BASE_URL ?>/index.php" class="brand">
      <!-- KRK shield mark: navy shield, amber outline, house glyph -->
      <svg class="brand-mark" viewBox="0 0 40 46" aria-hidden="true">
        <path d="M20 2 4 8v14c0 12 7 19 16 22 9-3 16-10 16-22V8Z" fill="#102a4a" stroke="#e8a317" stroke-width="2"/>
        <path d="M13 24l7-6 7 6M15 22v8h10v-8" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linejoin="round"/>
      </svg>
      <span class="brand-text">
        <strong>KRK Polytechnic</strong>
        <small>Hostel Allocation Portal</small>
      </span>
    </a>

    <button class="nav-toggle" id="navToggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="siteNav">
      <svg class="icon"><use href="#icon-menu"></use></svg>
    </button>

    <nav class="site-nav" id="siteNav">
      <ul class="nav-list">
        <?php if (isset($_SESSION['student_id'])): ?>
          <li><a href="<?= BASE_URL ?>/student/dashboard.php"><svg class="icon"><use href="#icon-grid"></use></svg> Dashboard</a></li>
          <li><a href="<?= BASE_URL ?>/student/application.php"><svg class="icon"><use href="#icon-bed"></use></svg> My hostel</a></li>
          <li><a href="<?= BASE_URL ?>/student/payment.php"><svg class="icon"><use href="#icon-card"></use></svg> School fees</a></li>
          <li><a href="<?= BASE_URL ?>/student/profile.php"><svg class="icon"><use href="#icon-user"></use></svg> Profile</a></li>
          <li><a href="<?= BASE_URL ?>/student/logout.php"><svg class="icon"><use href="#icon-logout"></use></svg> Log out</a></li>
        <?php elseif (isset($_SESSION['admin_id'])): ?>
          <li><a href="<?= BASE_URL ?>/admin/dashboard.php"><svg class="icon"><use href="#icon-grid"></use></svg> Dashboard</a></li>
          <li><a href="<?= BASE_URL ?>/admin/applications.php"><svg class="icon"><use href="#icon-list"></use></svg> Applications</a></li>
          <li><a href="<?= BASE_URL ?>/admin/logout.php"><svg class="icon"><use href="#icon-logout"></use></svg> Log out</a></li>
        <?php else: ?>
          <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
          <li><a href="<?= BASE_URL ?>/student/register.php">Hostel registration</a></li>
          <li><a href="<?= BASE_URL ?>/admin/login.php">Admin</a></li>
          <li><a href="<?= BASE_URL ?>/student/login.php" class="btn btn-primary btn-sm">Log in</a></li>
        <?php endif; ?>
      </ul>
    </nav>

    <div class="nav-overlay" id="navOverlay"></div>
  </div>
</header>
<?php endif; ?>
