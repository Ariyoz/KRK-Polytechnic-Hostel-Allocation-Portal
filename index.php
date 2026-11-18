<?php
$pageTitle = "Welcome";
require_once 'config/db.php';
$pageCss = ['public.css'];
include 'includes/header.php';

$currentSession = setting($conn, 'academic_session', '2025/2026');
$regOpen        = setting($conn, 'registration_open', '1');
$hostelFee      = (int) setting($conn, 'hostel_fee_amount', '25000');

// Bed spaces by gender, straight from hostel_rooms — no invented numbers.
$roomRows = $conn->query(
    "SELECT gender, COUNT(*) AS total, SUM(status='available') AS free FROM hostel_rooms GROUP BY gender"
)->fetch_all(MYSQLI_ASSOC);
$rooms = ['male' => ['total' => 0, 'free' => 0], 'female' => ['total' => 0, 'free' => 0]];
foreach ($roomRows as $r) {
    if (isset($rooms[$r['gender']])) {
        $rooms[$r['gender']]['total'] = (int) $r['total'];
        $rooms[$r['gender']]['free']  = (int) $r['free'];
    }
}
$totalRooms = $rooms['male']['total'] + $rooms['female']['total'];

function bedPct(array $g): int {
    if ($g['total'] === 0) return 0;
    return (int) round((($g['total'] - $g['free']) / $g['total']) * 100);
}
?>

<section class="hero">
  <div class="container hero-inner">
    <div class="hero-text">
      <p class="eyebrow">Hostel allocation &middot; <?= htmlspecialchars($currentSession) ?> session</p>
      <h1>Secure your bed space in KRK Hall</h1>
      <p class="hero-sub">
        Register for the hostel, get your school fees verified by the Registrar, and the
        Dean of Student Affairs will allocate your room. Pay the hostel fee online once
        your room is granted.
      </p>

      <div class="hero-actions">
        <?php if (isset($_SESSION['admin_id'])): ?>
          <a href="admin/dashboard.php" class="btn btn-primary">Go to dashboard</a>
          <a href="admin/logout.php" class="btn btn-outline">Log out</a>
        <?php elseif (isset($_SESSION['student_id'])): ?>
          <a href="student/dashboard.php" class="btn btn-primary">Go to my dashboard</a>
          <a href="student/logout.php" class="btn btn-outline">Log out</a>
        <?php else: ?>
          <?php if ($regOpen === '1'): ?>
          <a href="student/register.php" class="btn btn-primary">Register for the hostel</a>
          <?php endif; ?>
          <a href="student/login.php" class="btn btn-outline">I already registered</a>
        <?php endif; ?>
      </div>

      <?php if (!isset($_SESSION['admin_id']) && !isset($_SESSION['student_id'])): ?>
        <?php if ($regOpen !== '1'): ?>
        <p class="hero-note">
          <svg class="icon"><use href="#icon-shield"></use></svg>
          Hostel registration is currently closed.
        </p>
        <?php else: ?>
        <p class="hero-note">Hostel fee &#8358;<?= number_format($hostelFee) ?> per session &middot; open to fresh and returning students.</p>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="card hero-card">
      <div class="hero-card-head">
        <h3>Bed spaces right now</h3>
        <span class="badge badge-success">Live</span>
      </div>

      <div class="bed-row">
        <div class="bed-row-top">
          <span>Male rooms</span>
          <span><strong><?= $rooms['male']['free'] ?></strong> free</span>
        </div>
        <div class="progress">
          <div class="progress-bar" style="width:<?= bedPct($rooms['male']) ?>%"></div>
        </div>
        <p class="bed-row-sub"><?= $rooms['male']['total'] - $rooms['male']['free'] ?> of <?= $rooms['male']['total'] ?> rooms taken</p>
      </div>

      <div class="bed-row">
        <div class="bed-row-top">
          <span>Female rooms</span>
          <span><strong><?= $rooms['female']['free'] ?></strong> free</span>
        </div>
        <div class="progress">
          <div class="progress-bar" style="width:<?= bedPct($rooms['female']) ?>%"></div>
        </div>
        <p class="bed-row-sub"><?= $rooms['female']['total'] - $rooms['female']['free'] ?> of <?= $rooms['female']['total'] ?> rooms taken</p>
      </div>

      <p class="hero-card-foot">
        <?= $totalRooms ?> rooms in KRK Hall this session, one student per room. Rooms are allocated by gender.
      </p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <h2>How it works</h2>
    <div class="process-grid">
      <?php
      $steps = [
        ['Register',       'Fill the hostel form and choose an available room.'],
        ['Activate',       'Click the activation link sent to your email.'],
        ['Fees verified',  'The Registrar confirms your school fees payment.'],
        ['Room granted',   'The DSA reviews and grants your room. We email you.'],
        ['Pay hostel fee', 'Pay the hostel fee online and print your allocation slip.'],
      ];
      foreach ($steps as $i => $step): ?>
      <div class="process-step">
        <div class="process-step-num"><?= $i + 1 ?></div>
        <h4><?= htmlspecialchars($step[0]) ?></h4>
        <p><?= htmlspecialchars($step[1]) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container grid grid-2">
    <div class="card">
      <h3>Who can apply</h3>
      <p class="muted">
        Students of KRK Polytechnic registered for the <?= htmlspecialchars($currentSession) ?> session.
      </p>
      <div class="kv">
        <div class="kv-row"><span class="kv-label">Required ID</span><span class="kv-value">Matric number</span></div>
        <div class="kv-row"><span class="kv-label">Levels</span><span class="kv-value">ND1 &middot; ND2 &middot; HND1 &middot; HND2</span></div>
      </div>
    </div>

    <div class="card">
      <h3>Hostel rules in brief</h3>
      <ul class="rules-list">
        <li>One bed per student per session; an allocation cannot be transferred.</li>
        <li>School fees must be verified by the Registrar before a room is granted.</li>
        <li>The hostel fee is paid only after your room is granted.</li>
        <li>The DSA or Registrar may revoke an allocation for breach of hostel rules.</li>
      </ul>
    </div>
  </div>
</section>

<?php if ($regOpen === '1'): ?>
<section class="section">
  <div class="container">
    <div class="cta-band">
      <div>
        <h2>Ready to apply?</h2>
        <p>It takes about five minutes. Have your matric number ready.</p>
      </div>
      <a href="student/register.php" class="btn btn-primary">Start hostel registration</a>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
