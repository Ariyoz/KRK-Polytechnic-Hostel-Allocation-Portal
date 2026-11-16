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
$heroPages    = ['index.php'];
$isHero       = in_array(basename($_SERVER['PHP_SELF']), $heroPages);
$solidClass   = $isHero ? '' : 'solid';
$noHeader     = $noHeader ?? false;

// Dashboard pages have a sidebar — no need for the top-nav hamburger
$sidebarPages = [
    'dashboard.php','applications.php','students.php','student_detail.php',
    'rooms.php','upload_rooms.php','fees.php','reports.php',
    'email_logs.php','settings.php',
    'application.php','payment.php','profile.php','allocation_letter.php',
];
$hasSidebar = in_array(basename($_SERVER['PHP_SELF']), $sidebarPages);
$bodyClass  = '';
if ($noHeader)   $bodyClass .= ' no-header';
if ($hasSidebar) $bodyClass .= ' has-sidebar';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle).' — ' : '' ?>KRK Polytechnic Hostel Portal</title>
<meta name="base_url" content="<?= BASE_URL ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script>try{localStorage.removeItem('theme');}catch(e){}</script>
</head>
<body class="<?= trim($bodyClass) ?>">

<?php if (!$noHeader): ?>
<header class="site-header <?= $solidClass ?>">

  <!-- Topbar -->
  <div class="topbar">
    <div class="topbar-inner">
      <div class="topbar-left">
        <span><i class="fa fa-envelope"></i> info@krkpoly.edu.ng</span>
        <span><i class="fa fa-map-marker-alt"></i> KRK Polytechnic, Lagos</span>
      </div>
      <div class="topbar-right">
        <?php if (isset($_SESSION['student_id'])): ?>
          <span class="topbar-user"><i class="fa fa-user-circle"></i> <?= htmlspecialchars($_SESSION['student_name'] ?? '') ?></span>
        <?php elseif (isset($_SESSION['admin_id'])): ?>
          <span class="topbar-user"><i class="fa fa-shield-alt"></i> <?= htmlspecialchars($_SESSION['admin_name'] ?? '') ?> &middot; <?= strtoupper($_SESSION['admin_role'] ?? '') ?></span>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/student/login.php">Student Login</a>
          <a href="<?= BASE_URL ?>/student/register.php" class="topbar-btn">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Navbar -->
  <nav class="navbar">
    <a href="<?= BASE_URL ?>/index.php" class="logo">
      <div class="logo-wordmark">
        <strong>KRK</strong><span>Polytechnic</span>
      </div>
      <div class="logo-text">
        <span class="logo-portal-name">Hostel Allocation Portal</span>
      </div>
    </a>

    <button class="hamburger" onclick="toggleMobileNav()" id="hamburgerBtn" aria-label="Open menu">
      <span></span><span></span><span></span>
    </button>

    <ul class="nav-links" id="mainNavLinks">
      <!-- Mobile close button -->
      <li class="nav-close-item">
        <button class="nav-close-btn" onclick="toggleMobileNav()" aria-label="Close menu">
          <i class="fa fa-times"></i> Close
        </button>
      </li>
      <?php if (isset($_SESSION['student_id'])): ?>
        <li><a href="<?= BASE_URL ?>/student/dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
        <li><a href="<?= BASE_URL ?>/student/application.php"><i class="fa fa-bed"></i> My Room</a></li>
        <li><a href="<?= BASE_URL ?>/student/payment.php"><i class="fa fa-credit-card"></i> Payment</a></li>
        <li><a href="<?= BASE_URL ?>/student/profile.php"><i class="fa fa-user"></i> Profile</a></li>
        <li><a href="<?= BASE_URL ?>/student/logout.php" class="nav-logout"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
      <?php elseif (isset($_SESSION['admin_id'])): ?>
        <li><a href="<?= BASE_URL ?>/admin/dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
        <li><a href="<?= BASE_URL ?>/admin/applications.php"><i class="fa fa-clipboard-list"></i> Applications</a></li>
        <li><a href="<?= BASE_URL ?>/admin/logout.php" class="nav-logout"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
      <?php else: ?>
        <li><a href="<?= BASE_URL ?>/index.php"><i class="fa fa-home"></i> Home</a></li>
        <li><a href="<?= BASE_URL ?>/student/login.php">Student Login</a></li>
        <li><a href="<?= BASE_URL ?>/admin/login.php">Admin</a></li>
        <li><a href="<?= BASE_URL ?>/student/register.php" class="nav-cta"><i class="fa fa-arrow-right"></i> Apply Now</a></li>
      <?php endif; ?>
    </ul>
  </nav>

  <div class="nav-overlay" id="navOverlay" onclick="toggleMobileNav()"></div>
</header>
<?php endif; ?>
