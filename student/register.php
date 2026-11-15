<?php
$pageTitle = "Student Registration";
require_once '../config/db.php';
require_once '../config/mailer.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base   = (strpos($_SERVER['SCRIPT_NAME'], '/hostel_portal/') !== false) ? '/hostel_portal' : '';
    define('BASE_URL', $scheme . '://' . $_SERVER['HTTP_HOST'] . $base);
}

$regOpen        = setting($conn, 'registration_open', '1');
$currentSession = setting($conn, 'academic_session', '2025/2026');
$error   = '';
$success = '';
$faculties = $conn->query("SELECT * FROM faculties ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($regOpen !== '1') {
        $error = 'Student registration is currently closed.';
    } else {
        $matric   = trim($_POST['matric_number'] ?? '');
        $surname  = trim($_POST['surname'] ?? '');
        $fname    = trim($_POST['firstname'] ?? '');
        $oname    = trim($_POST['othername'] ?? '');
        $gender   = $_POST['gender'] ?? '';
        $fac_id   = intval($_POST['faculty_id'] ?? 0);
        $dept_id  = intval($_POST['department_id'] ?? 0);
        $level    = trim($_POST['level'] ?? '');
        $session  = $currentSession;
        $semester = $_POST['semester'] ?? '';
        $phone    = trim($_POST['phone'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $pwd      = $_POST['password'] ?? '';
        $cpwd     = $_POST['confirm_password'] ?? '';

        if (!$matric||!$surname||!$fname||!$gender||!$fac_id||!$dept_id||!$level||!$semester||!$phone||!$email||!$pwd) {
            $error = 'All required fields must be filled.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif ($pwd !== $cpwd) {
            $error = 'Passwords do not match.';
        } elseif (strlen($pwd) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $check = $conn->prepare("SELECT id FROM students WHERE matric_number=? OR email=?");
            $check->bind_param("ss", $matric, $email);
            $check->execute(); $check->store_result();
            if ($check->num_rows > 0) {
                $error = 'A student with this matric number or email already exists.';
            } else {
                $hashed = password_hash($pwd, PASSWORD_DEFAULT);
                $token  = bin2hex(random_bytes(32));
                $stmt = $conn->prepare("INSERT INTO students
                    (matric_number,surname,firstname,othername,gender,faculty_id,department_id,level,academic_session,semester,phone,email,password,activation_token)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param("sssssiisssssss",$matric,$surname,$fname,$oname,$gender,$fac_id,$dept_id,$level,$session,$semester,$phone,$email,$hashed,$token);
                if ($stmt->execute()) {
                    $link = BASE_URL . "/student/activate.php?token=$token";
                    $body = "<p>Dear <strong>$fname $surname</strong>,</p>
                             <p>Thank you for registering. Click below to activate your account:</p>
                             <a href='$link' class='btn'>Activate My Account</a>
                             <p style='margin-top:14px;font-size:12px;color:#888;'>Or copy: $link</p>";
                    sendEmail($email, 'Activate Your Hostel Portal Account', emailTemplate('Account Activation', $body));
                    $success = 'Registration successful! An activation link has been sent to <strong>' . htmlspecialchars($email) . '</strong>. Please check your inbox.';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
            $check->close();
        }
    }
}
$noHeader = true;
include '../includes/header.php';
?>

<div class="auth-screen auth-screen-register">

  <!-- ══ LEFT PANEL ══ -->
  <div class="auth-left">
    <div class="auth-left-bg"></div>
    <div class="auth-left-noise"></div>
    <div class="auth-orb auth-orb-1"></div>
    <div class="auth-orb auth-orb-2"></div>
    <div class="auth-orb auth-orb-3"></div>

    <div class="auth-left-inner">
      <div class="auth-brand">
        <div class="auth-brand-logo">
          <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="KRK Polytechnic">
        </div>
        <div class="auth-brand-text">
          <strong>KRK Polytechnic</strong>
          <span>Hostel Portal</span>
        </div>
      </div>

      <div class="auth-left-content">
        <div class="auth-eyebrow">
          <i class="fa fa-graduation-cap"></i> <?= htmlspecialchars($currentSession) ?> Session
        </div>
        <h1 class="auth-left-title">
          Join the<br><em>Hostel</em><br>Community
        </h1>
        <p class="auth-left-sub">
          Register your account to apply for hostel accommodation this academic session.
        </p>

        <ul class="auth-features">
          <li><span class="af-icon"><i class="fa fa-check"></i></span> Free account registration</li>
          <li><span class="af-icon"><i class="fa fa-check"></i></span> Email activation for security</li>
          <li><span class="af-icon"><i class="fa fa-check"></i></span> Gender-matched room options</li>
          <li><span class="af-icon"><i class="fa fa-check"></i></span> Real-time application tracking</li>
        </ul>
      </div>

      <div class="auth-stats">
        <div class="auth-stat-chip"><i class="fa fa-bed"></i> 200 Rooms</div>
        <div class="auth-stat-chip"><i class="fa fa-bolt"></i> Instant Email</div>
        <div class="auth-stat-chip"><i class="fa fa-lock"></i> Secure</div>
      </div>
    </div>
  </div>

  <!-- ══ RIGHT PANEL — Registration Form ══ -->
  <div class="auth-right auth-right-wide">
    <div class="auth-right-inner auth-right-inner-wide">

      <div class="auth-top-nav">
        <span>Already registered?</span>
        <a href="login.php">Sign In <i class="fa fa-arrow-right"></i></a>
      </div>

      <div class="auth-reg-header">
        <div class="auth-card-icon">
          <i class="fa fa-user-plus"></i>
        </div>
        <div>
          <h2 class="auth-title">Create Your Account</h2>
          <p class="auth-subtitle">Hostel Registration &mdash; <?= htmlspecialchars($currentSession) ?></p>
        </div>
      </div>

      <?php if ($error): ?>
      <div class="auth-alert auth-alert-error">
        <i class="fa fa-exclamation-circle"></i>
        <span><?= $error ?></span>
      </div>
      <?php endif; ?>

      <?php if ($success): ?>
      <div class="auth-success-state">
        <div class="auth-success-icon"><i class="fa fa-check-circle"></i></div>
        <h3>Registration Complete!</h3>
        <p><?= $success ?></p>
        <a href="login.php" class="auth-btn-submit" style="display:inline-flex;text-decoration:none;margin-top:8px;">
          <span>Go to Login</span>
          <i class="fa fa-arrow-right"></i>
        </a>
      </div>

      <?php elseif ($regOpen !== '1'): ?>
      <div class="auth-alert auth-alert-warning">
        <i class="fa fa-lock"></i>
        <span>Registration is currently closed. Contact the Student Affairs office.</span>
      </div>

      <?php else: ?>
      <form method="POST" id="registerForm" class="auth-reg-form">

        <!-- Section: Identity -->
        <div class="auth-form-section">
          <div class="auth-form-section-label">Personal Details</div>
          <div class="auth-reg-grid">
            <div class="auth-field">
              <label>Matric / App. Number <span class="req">*</span></label>
              <div class="auth-input-wrap">
                <i class="fa fa-id-card auth-field-icon"></i>
                <input type="text" name="matric_number"
                       value="<?= htmlspecialchars($_POST['matric_number'] ?? '') ?>"
                       placeholder="KRK/2021/CS/001" required>
              </div>
            </div>
            <div class="auth-field">
              <label>Gender <span class="req">*</span></label>
              <div class="auth-input-wrap auth-select-wrap">
                <i class="fa fa-venus-mars auth-field-icon"></i>
                <select name="gender" required>
                  <option value="">Select gender</option>
                  <option value="male"   <?= (($_POST['gender']??'')==='male')  ?'selected':'' ?>>Male</option>
                  <option value="female" <?= (($_POST['gender']??'')==='female')?'selected':'' ?>>Female</option>
                </select>
              </div>
            </div>
            <div class="auth-field">
              <label>Surname <span class="req">*</span></label>
              <div class="auth-input-wrap">
                <i class="fa fa-user auth-field-icon"></i>
                <input type="text" name="surname" value="<?= htmlspecialchars($_POST['surname']??'') ?>" placeholder="e.g. Adeyemi" required>
              </div>
            </div>
            <div class="auth-field">
              <label>First Name <span class="req">*</span></label>
              <div class="auth-input-wrap">
                <i class="fa fa-user auth-field-icon"></i>
                <input type="text" name="firstname" value="<?= htmlspecialchars($_POST['firstname']??'') ?>" placeholder="e.g. Taiwo" required>
              </div>
            </div>
            <div class="auth-field">
              <label>Other Name</label>
              <div class="auth-input-wrap">
                <i class="fa fa-user auth-field-icon"></i>
                <input type="text" name="othername" value="<?= htmlspecialchars($_POST['othername']??'') ?>" placeholder="Optional">
              </div>
            </div>
            <div class="auth-field">
              <label>Level <span class="req">*</span></label>
              <div class="auth-input-wrap auth-select-wrap">
                <i class="fa fa-layer-group auth-field-icon"></i>
                <select name="level" required>
                  <option value="">Select level</option>
                  <?php foreach(['ND1','ND2','HND1','HND2'] as $l): ?>
                  <option value="<?= $l ?>" <?= (($_POST['level']??'')===$l)?'selected':'' ?>><?= $l ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Section: Academic -->
        <div class="auth-form-section">
          <div class="auth-form-section-label">Academic Information</div>
          <div class="auth-reg-grid">
            <div class="auth-field">
              <label>Faculty <span class="req">*</span></label>
              <div class="auth-input-wrap auth-select-wrap">
                <i class="fa fa-university auth-field-icon"></i>
                <select name="faculty_id" id="faculty_id" required>
                  <option value="">Select Faculty</option>
                  <?php while($fac=$faculties->fetch_assoc()): ?>
                  <option value="<?= $fac['id'] ?>" <?= (($_POST['faculty_id']??'')==$fac['id'])?'selected':'' ?>>
                    <?= htmlspecialchars($fac['name']) ?>
                  </option>
                  <?php endwhile; ?>
                </select>
              </div>
            </div>
            <div class="auth-field">
              <label>Department <span class="req">*</span></label>
              <div class="auth-input-wrap auth-select-wrap">
                <i class="fa fa-book auth-field-icon"></i>
                <select name="department_id" id="department_id" required>
                  <option value="">Select Faculty first</option>
                </select>
              </div>
            </div>
            <div class="auth-field">
              <label>Academic Session</label>
              <div class="auth-input-wrap">
                <i class="fa fa-calendar auth-field-icon"></i>
                <input type="text" value="<?= htmlspecialchars($currentSession) ?>" readonly class="auth-readonly">
              </div>
            </div>
            <div class="auth-field">
              <label>Semester <span class="req">*</span></label>
              <div class="auth-input-wrap auth-select-wrap">
                <i class="fa fa-clock auth-field-icon"></i>
                <select name="semester" required>
                  <option value="">Select Semester</option>
                  <option value="first"  <?= (($_POST['semester']??'')==='first') ?'selected':'' ?>>First Semester</option>
                  <option value="second" <?= (($_POST['semester']??'')==='second')?'selected':'' ?>>Second Semester</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Section: Contact & Security -->
        <div class="auth-form-section">
          <div class="auth-form-section-label">Contact &amp; Security</div>
          <div class="auth-reg-grid">
            <div class="auth-field">
              <label>Phone Number <span class="req">*</span></label>
              <div class="auth-input-wrap">
                <i class="fa fa-phone auth-field-icon"></i>
                <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone']??'') ?>" placeholder="08012345678" required>
              </div>
            </div>
            <div class="auth-field">
              <label>Email Address <span class="req">*</span></label>
              <div class="auth-input-wrap">
                <i class="fa fa-envelope auth-field-icon"></i>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email']??'') ?>" placeholder="student@email.com" required>
              </div>
            </div>
            <div class="auth-field">
              <label>Password <span class="req">*</span></label>
              <div class="auth-input-wrap">
                <i class="fa fa-lock auth-field-icon"></i>
                <input type="password" name="password" id="password" placeholder="Min. 6 characters" required autocomplete="new-password">
                <button type="button" class="auth-pwd-toggle" onclick="togglePwd('password',this)" aria-label="Toggle"><i class="fa fa-eye"></i></button>
              </div>
            </div>
            <div class="auth-field">
              <label>Confirm Password <span class="req">*</span></label>
              <div class="auth-input-wrap">
                <i class="fa fa-lock auth-field-icon"></i>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Repeat password" required autocomplete="new-password">
                <button type="button" class="auth-pwd-toggle" onclick="togglePwd('confirm_password',this)" aria-label="Toggle"><i class="fa fa-eye"></i></button>
              </div>
            </div>
          </div>
        </div>

        <button type="submit" class="auth-btn-submit">
          <span>Create My Account</span>
          <i class="fa fa-arrow-right"></i>
        </button>
      </form>
      <?php endif; ?>

      <p class="auth-trust">
        <i class="fa fa-shield-alt"></i>
        Your data is protected &middot; &copy; <?= date('Y') ?> KRK Polytechnic
      </p>
    </div>
  </div>

</div>

<?php include '../includes/footer.php'; ?>
