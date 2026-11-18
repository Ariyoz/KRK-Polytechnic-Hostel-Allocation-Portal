<?php
$pageTitle = "Portal Settings";
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }

require_once 'auth_guard.php';
require_role(['dsa'], 'Only the Dean of Student Affairs can change portal settings.');

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$message = '';
$msgType = 'success';

// Helper: update setting
function updateSetting($conn, $key, $value) {
    $st = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?)
                           ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    $st->bind_param("ss", $key, $value);
    return $st->execute();
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }

    $session    = trim($_POST['academic_session'] ?? '');
    $feeAmount  = intval($_POST['hostel_fee_amount'] ?? 0);
    $regOpen    = isset($_POST['registration_open']) ? '1' : '0';
    $maxBeds    = intval($_POST['max_bed_spaces'] ?? 200);
    $portalName = trim($_POST['portal_name'] ?? '');

    if (!$session || !$feeAmount || !$portalName || $maxBeds < 1) {
        $message = 'All fields are required and fee/bed spaces must be positive numbers.';
        $msgType = 'danger';
    } else {
        $saves = [
            'academic_session'  => $session,
            'hostel_fee_amount' => (string)$feeAmount,
            'registration_open' => $regOpen,
            'max_bed_spaces'    => (string)$maxBeds,
            'portal_name'       => $portalName,
        ];
        $stmtUpd = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?)
                                    ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        foreach ($saves as $k => $v) {
            $stmtUpd->bind_param("ss", $k, $v);
            $stmtUpd->execute();
        }
        $message = 'Settings saved successfully.';
    }
}

// Load current settings
$currentSession  = setting($conn, 'academic_session', '2025/2026');
$currentFee      = setting($conn, 'hostel_fee_amount', '25000');
$regOpen         = setting($conn, 'registration_open', '1');
$maxBeds         = setting($conn, 'max_bed_spaces',    '200');
$portalName      = setting($conn, 'portal_name',       'KRK Polytechnic Hostel Portal');

$roleLabel = ['dsa'=>'Dean of Student Affairs','registrar'=>'Registrar Office','ict'=>'ICT Department'][$_SESSION['admin_role'] ?? ''] ?? strtoupper($_SESSION['admin_role'] ?? '');

$pageCss = ['admin.css','admin-extra.css'];
include '../includes/header.php';
?>

<?php include 'sidebar.php'; ?>

<div class="page-head">
                <span class="eyebrow">System</span>
                <h1>Portal settings</h1>
                <p class="sub">These values control the current session, hostel fee and registration window for every student.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $msgType ?> mb-4"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="card settings-card">
                <div class="card-head"><h3>General settings</h3></div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                    <div class="field">
                        <label class="label req">Portal name</label>
                        <input type="text" name="portal_name" class="input"
                               value="<?= htmlspecialchars($portalName) ?>" required>
                    </div>

                    <div class="field">
                        <label class="label req">Current academic session</label>
                        <input type="text" name="academic_session" class="input"
                               placeholder="e.g. 2025/2026"
                               value="<?= htmlspecialchars($currentSession) ?>" required>
                        <p class="hint">This appears on allocation letters and registration forms.</p>
                    </div>

                    <div class="field">
                        <label class="label req">Hostel fee amount (&#8358;)</label>
                        <input type="number" name="hostel_fee_amount" class="input"
                               min="1000" step="500"
                               value="<?= htmlspecialchars($currentFee) ?>" required>
                    </div>

                    <div class="field">
                        <label class="label req">Maximum bed spaces</label>
                        <input type="number" name="max_bed_spaces" class="input"
                               min="1" max="1000"
                               value="<?= htmlspecialchars($maxBeds) ?>" required>
                    </div>

                    <div class="field">
                        <label class="switch">
                            <input type="checkbox" name="registration_open" value="1"
                                   <?= $regOpen === '1' ? 'checked' : '' ?>>
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Student registration open</span>
                        </label>
                        <p class="hint">When off, new students cannot register — existing students can still log in.</p>
                    </div>

                    <button type="submit" class="btn btn-primary">Save settings</button>
                </form>
            </div>

            <!-- Danger zone -->
            <div class="card danger-card settings-card mt-5">
                <div class="card-head"><h3>Danger zone</h3></div>
                <p class="text-sm muted mb-3">These actions are irreversible. Use with extreme caution.</p>
                <p class="text-sm">
                    To reset the portal or manage admin passwords, use the
                    <code>setup.php</code> script directly on the server (do not expose it publicly).
                </p>
            </div>

    </div><!-- portal-content -->
  </div><!-- portal-main -->
</div><!-- portal -->

<?php include '../includes/footer.php'; ?>
