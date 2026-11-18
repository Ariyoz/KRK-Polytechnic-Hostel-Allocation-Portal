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
$pageCss  = ['auth.css'];
include '../includes/header.php';
?>

<div class="container section auth-register-page">

  <div class="page-head">
    <span class="eyebrow">KRK HALL &middot; <?= htmlspecialchars($currentSession) ?> SESSION</span>
    <h1>Hostel registration</h1>
    <p class="sub">Fill in your details, choose an available room, then click <strong>Register for the hostel</strong>. We will send an activation alert to your email once you submit the form.</p>
  </div>

  <div class="steps-row">
    <div class="steps-row-item is-current"><span class="dot">1</span> Register</div>
    <div class="steps-row-item"><span class="dot">2</span> Activate email</div>
    <div class="steps-row-item"><span class="dot">3</span> Pay school fees</div>
    <div class="steps-row-item"><span class="dot">4</span> Room granted</div>
    <div class="steps-row-item"><span class="dot">5</span> Pay hostel fee</div>
  </div>

  <div class="auth-register-grid">

      <div class="card stack">

        <?php if ($error): ?>
        <div class="alert alert-danger"><span><?= $error ?></span></div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success"><span><?= $success ?></span></div>

        <div class="kv">
          <div class="kv-row"><span class="kv-label">Matric / application number</span><span class="kv-value"><?= htmlspecialchars($matric) ?></span></div>
          <div class="kv-row"><span class="kv-label">Name</span><span class="kv-value"><?= htmlspecialchars($fname . ' ' . $surname) ?></span></div>
          <div class="kv-row"><span class="kv-label">Status</span><span class="badge badge-warning">Waiting for activation</span></div>
        </div>

        <a href="login.php" class="btn btn-dark btn-block">Go to login</a>

        <?php elseif ($regOpen !== '1'): ?>
        <div class="alert alert-warning"><span>Registration is currently closed. Contact the Student Affairs office.</span></div>

        <?php else: ?>
        <form method="POST" id="registerForm" class="stack">

          <fieldset class="field-group">
            <legend>Personal details</legend>
            <div class="field">
              <label class="label" for="matric_number">Matric / application number <span class="req">*</span></label>
              <input class="input" type="text" id="matric_number" name="matric_number"
                     value="<?= htmlspecialchars($_POST['matric_number'] ?? '') ?>"
                     placeholder="KRK/ND/24/COM/015" required>
            </div>
            <div class="field-row">
              <div class="field">
                <label class="label" for="surname">Surname <span class="req">*</span></label>
                <input class="input" type="text" id="surname" name="surname" value="<?= htmlspecialchars($_POST['surname']??'') ?>" placeholder="e.g. Adeyemi" required>
              </div>
              <div class="field">
                <label class="label" for="firstname">First name <span class="req">*</span></label>
                <input class="input" type="text" id="firstname" name="firstname" value="<?= htmlspecialchars($_POST['firstname']??'') ?>" placeholder="e.g. Taiwo" required>
              </div>
            </div>
            <div class="field-row">
              <div class="field">
                <label class="label" for="othername">Other name</label>
                <input class="input" type="text" id="othername" name="othername" value="<?= htmlspecialchars($_POST['othername']??'') ?>" placeholder="Optional">
              </div>
              <div class="field">
                <label class="label" for="gender">Gender <span class="req">*</span></label>
                <select class="select" id="gender" name="gender" required>
                  <option value="">Select gender</option>
                  <option value="male"   <?= (($_POST['gender']??'')==='male')  ?'selected':'' ?>>Male</option>
                  <option value="female" <?= (($_POST['gender']??'')==='female')?'selected':'' ?>>Female</option>
                </select>
                <p class="hint">Decides which rooms you can choose.</p>
              </div>
            </div>
          </fieldset>

          <fieldset class="field-group">
            <legend>Academic details</legend>
            <div class="field-row">
              <div class="field">
                <label class="label" for="faculty_id">Faculty <span class="req">*</span></label>
                <select class="select" name="faculty_id" id="faculty_id" required>
                  <option value="">Select faculty</option>
                  <?php while($fac=$faculties->fetch_assoc()): ?>
                  <option value="<?= $fac['id'] ?>" <?= (($_POST['faculty_id']??'')==$fac['id'])?'selected':'' ?>>
                    <?= htmlspecialchars($fac['name']) ?>
                  </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="field">
                <label class="label" for="department_id">Department <span class="req">*</span></label>
                <select class="select" name="department_id" id="department_id" required>
                  <option value="">Select faculty first</option>
                </select>
              </div>
            </div>
            <div class="field-row cols-3">
              <div class="field">
                <label class="label" for="level">Level <span class="req">*</span></label>
                <select class="select" id="level" name="level" required>
                  <option value="">Select level</option>
                  <?php foreach(['ND1','ND2','HND1','HND2'] as $l): ?>
                  <option value="<?= $l ?>" <?= (($_POST['level']??'')===$l)?'selected':'' ?>><?= $l ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="field">
                <label class="label" for="academic_session">Academic session</label>
                <input class="input" type="text" id="academic_session" value="<?= htmlspecialchars($currentSession) ?>" readonly>
              </div>
              <div class="field">
                <label class="label" for="semester">Semester <span class="req">*</span></label>
                <select class="select" id="semester" name="semester" required>
                  <option value="">Select semester</option>
                  <option value="first"  <?= (($_POST['semester']??'')==='first') ?'selected':'' ?>>First semester</option>
                  <option value="second" <?= (($_POST['semester']??'')==='second')?'selected':'' ?>>Second semester</option>
                </select>
              </div>
            </div>
          </fieldset>

          <fieldset class="field-group">
            <legend>Contact and password</legend>
            <div class="field-row">
              <div class="field">
                <label class="label" for="phone">Phone number <span class="req">*</span></label>
                <input class="input" type="tel" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone']??'') ?>" placeholder="08012345678" required>
              </div>
              <div class="field">
                <label class="label" for="email">Email address <span class="req">*</span></label>
                <input class="input" type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email']??'') ?>" placeholder="you@example.com" required>
                <p class="hint">Your activation link and room notices go here.</p>
              </div>
            </div>
            <div class="field-row">
              <div class="field">
                <label class="label" for="password">Password <span class="req">*</span></label>
                <div class="pwd-field">
                  <input class="input" type="password" name="password" id="password" placeholder="At least 6 characters" required minlength="6" autocomplete="new-password">
                  <button type="button" class="pwd-toggle" onclick="togglePwd('password',this)" aria-label="Show password">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
                  </button>
                </div>
              </div>
              <div class="field">
                <label class="label" for="confirm_password">Confirm password <span class="req">*</span></label>
                <div class="pwd-field">
                  <input class="input" type="password" name="confirm_password" id="confirm_password" placeholder="Repeat password" required autocomplete="new-password">
                  <button type="button" class="pwd-toggle" onclick="togglePwd('confirm_password',this)" aria-label="Show password">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
                  </button>
                </div>
              </div>
            </div>
          </fieldset>

          <button type="submit" class="btn btn-primary btn-block">Register for the hostel</button>
          <p class="text-sm muted text-center">Already registered? <a href="login.php" class="sub-link">Log in</a></p>
        </form>
        <?php endif; ?>

      </div>

      <div class="auth-aside">
        <div class="card">
          <div class="card-head"><h3>What happens next</h3></div>
          <div class="timeline">
            <div class="timeline-item is-current">
              <div class="timeline-dot">1</div>
              <h4>Register</h4>
              <p>Submit this form.</p>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot">2</div>
              <h4>Activate</h4>
              <p>Click the link in your email.</p>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot">3</div>
              <h4>Pay school fees</h4>
              <p>At least 50% in first semester.</p>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot">4</div>
              <h4>Room granted</h4>
              <p>By the DSA, by email.</p>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot">5</div>
              <h4>Pay hostel fee</h4>
              <p>&#8358;45,000, then print your slip.</p>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-head"><h3>Have these ready</h3></div>
          <ul class="ready-list">
            <li>Matric or application number</li>
            <li>An email address you can open now</li>
            <li>Your faculty, department and level</li>
          </ul>
        </div>
      </div>

  </div>

</div>

<?php include '../includes/footer.php'; ?>
