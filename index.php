<?php
$pageTitle = "Welcome";
require_once 'config/db.php';
include 'includes/header.php';
$currentSession = setting($conn, 'academic_session', '2025/2026');
$regOpen        = setting($conn, 'registration_open', '1');
$availRooms     = $conn->query("SELECT COUNT(*) AS c FROM hostel_rooms WHERE status='available'")->fetch_assoc()['c'];
$totalStudents  = $conn->query("SELECT COUNT(*) AS c FROM students WHERE is_activated=1")->fetch_assoc()['c'];
$grantedApps    = $conn->query("SELECT COUNT(*) AS c FROM hostel_applications WHERE status='granted'")->fetch_assoc()['c'];
?>

<!-- ═══════════════════════════════════════════
     HERO
═══════════════════════════════════════════ -->
<section class="lp-hero">
  <div class="lp-hero-bg"></div>
  <div class="lp-hero-overlay"></div>
  <!-- grid dots -->
  <div class="lp-hero-grid"></div>
  <!-- floating orbs -->
  <div class="lp-orb lp-orb-1"></div>
  <div class="lp-orb lp-orb-2"></div>

  <div class="lp-hero-inner">
    <div class="lp-hero-text">
      <div class="lp-eyebrow">
        <span class="lp-eyebrow-dot"></span>
        <?= htmlspecialchars($currentSession) ?> Academic Session
      </div>
      <h1 class="lp-hero-h1">
        Your Hostel Room<br>
        <span class="lp-hero-h1-accent">Starts Here</span>
      </h1>
      <p class="lp-hero-sub">
        KRK Polytechnic's fully digital hostel portal. Apply online, get verified, and move in — no queues, no paperwork.
      </p>

      <div class="lp-hero-actions">
        <?php if (isset($_SESSION['admin_id'])): ?>
          <!-- Admin is logged in -->
          <a href="admin/dashboard.php" class="lp-btn-primary">
            <i class="fa fa-th-large"></i> Go to Dashboard
            <span class="lp-btn-arrow"><i class="fa fa-arrow-right"></i></span>
          </a>
          <a href="admin/logout.php" class="lp-btn-ghost">
            <i class="fa fa-sign-out-alt"></i> Logout
          </a>
        <?php elseif (isset($_SESSION['student_id'])): ?>
          <!-- Student is logged in -->
          <a href="student/dashboard.php" class="lp-btn-primary">
            <i class="fa fa-th-large"></i> My Dashboard
            <span class="lp-btn-arrow"><i class="fa fa-arrow-right"></i></span>
          </a>
          <a href="student/logout.php" class="lp-btn-ghost">
            <i class="fa fa-sign-out-alt"></i> Logout
          </a>
        <?php else: ?>
          <!-- Guest -->
          <?php if ($regOpen === '1'): ?>
          <a href="student/register.php" class="lp-btn-primary">
            Apply for a Room
            <span class="lp-btn-arrow"><i class="fa fa-arrow-right"></i></span>
          </a>
          <?php endif; ?>
          <a href="student/login.php" class="lp-btn-ghost">
            <i class="fa fa-sign-in-alt"></i> Student Login
          </a>
        <?php endif; ?>
      </div>

      <?php if (!isset($_SESSION['admin_id']) && !isset($_SESSION['student_id']) && $regOpen !== '1'): ?>
      <div class="lp-closed-badge">
        <i class="fa fa-lock"></i> Registration is currently closed
      </div>
      <?php endif; ?>
    </div>

    <!-- floating stat cards -->
    <div class="lp-hero-cards">
      <div class="lp-stat-glass lp-stat-glass-1">
        <div class="lp-stat-glass-icon"><i class="fa fa-bed"></i></div>
        <div class="lp-stat-glass-num"><?= $availRooms > 0 ? number_format($availRooms) : '200' ?>+</div>
        <div class="lp-stat-glass-label">Rooms Available</div>
      </div>
      <div class="lp-stat-glass lp-stat-glass-2">
        <div class="lp-stat-glass-icon" style="background:rgba(59,130,246,.2);color:#93c5fd;"><i class="fa fa-users"></i></div>
        <div class="lp-stat-glass-num"><?= number_format($totalStudents) ?></div>
        <div class="lp-stat-glass-label">Registered Students</div>
      </div>
      <div class="lp-stat-glass lp-stat-glass-3">
        <div class="lp-stat-glass-icon" style="background:rgba(34,197,94,.2);color:#4ade80;"><i class="fa fa-check-double"></i></div>
        <div class="lp-stat-glass-num"><?= number_format($grantedApps) ?>+</div>
        <div class="lp-stat-glass-label">Rooms Granted</div>
      </div>
    </div>
  </div>

  <!-- scroll cue -->
  <div class="lp-scroll-cue">
    <div class="lp-scroll-line"></div>
    <span>Scroll</span>
  </div>

  <!-- admin pill -->
  <a href="admin/login.php" class="lp-admin-pill">
    <span class="lp-admin-pill-icon"><i class="fa fa-shield-alt"></i></span>
    Admin Login
    <i class="fa fa-arrow-right" style="font-size:10px;opacity:.6;"></i>
  </a>
</section>

<!-- ═══════════════════════════════════════════
     QUICK-ACTIONS STRIP
═══════════════════════════════════════════ -->
<div class="lp-strip-wrap">
  <div class="lp-strip">
    <div class="lp-strip-item">
      <div class="lp-strip-icon lp-si-blue"><i class="fa fa-bed"></i></div>
      <div class="lp-strip-text">
        <h4>200 Bed Spaces</h4>
        <p>Gender-matched rooms for eligible students</p>
      </div>
      <a href="student/register.php" class="lp-strip-link">Apply <i class="fa fa-arrow-right"></i></a>
    </div>
    <div class="lp-strip-divider"></div>
    <div class="lp-strip-item">
      <div class="lp-strip-icon lp-si-dark"><i class="fa fa-shield-alt"></i></div>
      <div class="lp-strip-text">
        <h4>Fee-Based Eligibility</h4>
        <p>Only verified school-fee payers may apply</p>
      </div>
      <a href="student/login.php" class="lp-strip-link">Check Status <i class="fa fa-arrow-right"></i></a>
    </div>
    <div class="lp-strip-divider"></div>
    <div class="lp-strip-item">
      <div class="lp-strip-icon lp-si-blue"><i class="fa fa-envelope-open-text"></i></div>
      <div class="lp-strip-text">
        <h4>Instant Notifications</h4>
        <p>Email alerts the moment your status changes</p>
      </div>
      <a href="admin/login.php" class="lp-strip-link">Admin <i class="fa fa-arrow-right"></i></a>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════
     STATS COUNTER
═══════════════════════════════════════════ -->
<section class="lp-stats-section">
  <div class="lp-container">
    <div class="lp-stats-grid">
      <?php
      $stats = [
        ['200',           'Bed Spaces',          'fa-bed',          'Available this session'],
        [$totalStudents,  'Students Registered',  'fa-users',        'Active verified accounts'],
        [$availRooms,     'Rooms Available',       'fa-door-open',    'Ready for allocation'],
        [$grantedApps,    'Rooms Allocated',       'fa-check-circle', 'Confirmed this session'],
      ];
      foreach ($stats as $s): ?>
      <div class="lp-stat-item">
        <div class="lp-stat-icon-wrap"><i class="fa <?= $s[2] ?>"></i></div>
        <div class="lp-stat-num stat-num" data-target="<?= is_numeric($s[0]) ? $s[0] : 200 ?>"><?= $s[0] ?></div>
        <div class="lp-stat-name"><?= $s[1] ?></div>
        <div class="lp-stat-desc"><?= $s[3] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════
     ABOUT SECTION
═══════════════════════════════════════════ -->
<section class="lp-section lp-about">
  <div class="lp-container lp-about-inner">
    <!-- Visual side -->
    <div class="lp-about-visual">
      <div class="lp-about-img-main">
        <img src="assets/images/third1.jpg" alt="KRK Polytechnic campus" class="lp-about-real-img">
        <div class="lp-about-badge">
          <div class="lp-about-badge-num"><?= $grantedApps ?>+</div>
          <div class="lp-about-badge-label">Rooms Granted</div>
        </div>
      </div>
      <div class="lp-about-img-row">
        <div class="lp-about-img-sm lp-about-img-sm-1">
          <img src="assets/images/third2.jpg" alt="Hostel building" class="lp-about-real-img">
        </div>
        <div class="lp-about-img-sm lp-about-img-sm-2">
          <img src="assets/images/third3.jpg" alt="Students" class="lp-about-real-img">
        </div>
      </div>
    </div>

    <!-- Text side -->
    <div class="lp-about-text">
      <div class="lp-pill-label"><i class="fa fa-info-circle"></i> About the Portal</div>
      <h2 class="lp-section-title">Hostel Rooms for Every<br><em>Eligible Student</em></h2>
      <p class="lp-section-sub">
        The KRK Polytechnic Hostel Allocation Portal brings the entire room assignment process online. From registration to fee payment, every step is handled digitally with full transparency.
      </p>
      <ul class="lp-checklist">
        <li><i class="fa fa-check-circle"></i> Apply for a room from anywhere, anytime</li>
        <li><i class="fa fa-check-circle"></i> Real-time email notifications on your status</li>
        <li><i class="fa fa-check-circle"></i> Download your official allocation letter instantly</li>
        <li><i class="fa fa-check-circle"></i> Three-tier admin: DSA, Registrar, and ICT</li>
      </ul>
      <div class="lp-about-actions">
        <a href="student/register.php" class="lp-btn-primary">
          Get Started <span class="lp-btn-arrow"><i class="fa fa-arrow-right"></i></span>
        </a>
        <a href="student/login.php" class="lp-btn-secondary">Already registered?</a>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════
     FEATURES GRID
═══════════════════════════════════════════ -->
<section class="lp-section lp-features-section">
  <div class="lp-container">
    <div class="lp-section-head lp-center">
      <div class="lp-pill-label"><i class="fa fa-star"></i> Features</div>
      <h2 class="lp-section-title">Everything in One Place</h2>
      <p class="lp-section-sub lp-center-sub">From application to allocation, the entire process is online, fast and transparent.</p>
    </div>
    <div class="lp-features-grid">
      <?php
      $features = [
        ['fa-bed',        'Smart Room Allocation',  'Rooms matched by gender. The DSA approves all applications with full oversight.',    'blue'],
        ['fa-check-double','Fee-Based Eligibility', 'Only fee-verified students can apply — the Registrar controls eligibility.',          'dark'],
        ['fa-envelope',   'Instant Email Alerts',   'Students are notified the moment their room is granted, revoked or confirmed.',       'blue'],
        ['fa-file-alt',   'Printable Letter',        'Download your official allocation letter after payment is confirmed.',               'dark'],
        ['fa-shield-alt', 'Three Admin Roles',       'DSA, Registrar and ICT each have dedicated dashboards and permissions.',            'blue'],
        ['fa-mobile-alt', 'Fully Responsive',        'Works perfectly on phones, tablets and desktops — apply from anywhere.',           'dark'],
      ];
      foreach ($features as $f): ?>
      <div class="lp-feature-card lp-feature-<?= $f[3] ?>">
        <div class="lp-feature-icon"><i class="fa <?= $f[0] ?>"></i></div>
        <h3><?= $f[1] ?></h3>
        <p><?= $f[2] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════
     HOW IT WORKS
═══════════════════════════════════════════ -->
<section class="lp-section lp-steps-section">
  <div class="lp-container">
    <div class="lp-section-head lp-center">
      <div class="lp-pill-label"><i class="fa fa-list-ol"></i> Process</div>
      <h2 class="lp-section-title">How It Works</h2>
      <p class="lp-section-sub lp-center-sub">Six simple steps from registration to moving in.</p>
    </div>
    <div class="lp-steps-grid">
      <?php
      $steps = [
        ['Register',      'Create your account with matric number, faculty and personal details.'],
        ['Activate',       'Click the activation link in your email to unlock your account.'],
        ['Fees Verified',  'Visit the Registrar to confirm your school fees payment status.'],
        ['Apply for Room', 'Log in and select an available room matching your gender.'],
        ['DSA Grants',     'The Dean of Student Affairs reviews and approves your allocation.'],
        ['Pay & Move In',  'Pay the hostel fee and collect your key at reception.'],
      ];
      foreach ($steps as $i => $step): ?>
      <div class="lp-step-card">
        <div class="lp-step-num"><?= $i + 1 ?></div>
        <h4><?= $step[0] ?></h4>
        <p><?= $step[1] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════
     CTA BANNER
═══════════════════════════════════════════ -->
<?php if ($regOpen === '1'): ?>
<section class="lp-cta-section">
  <div class="lp-container">
    <div class="lp-cta-banner">
      <div class="lp-cta-orb-1"></div>
      <div class="lp-cta-orb-2"></div>
      <div class="lp-cta-inner">
        <div class="lp-cta-text">
          <div class="lp-pill-label lp-pill-label-white"><i class="fa fa-bolt"></i> Registration Open</div>
          <h2>Ready to Secure Your Hostel Room?</h2>
          <p><?= htmlspecialchars($currentSession) ?> hostel registration is now open. Apply before rooms fill up.</p>
        </div>
        <div class="lp-cta-actions">
          <a href="student/register.php" class="lp-cta-btn-primary">
            <i class="fa fa-user-plus"></i> Register as Student
          </a>
          <a href="student/login.php" class="lp-cta-btn-ghost">
            <i class="fa fa-sign-in-alt"></i> Already Registered?
          </a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
