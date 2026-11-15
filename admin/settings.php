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

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <h2 class="page-title"><i class="fa fa-cog"></i> Portal Settings</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="card" style="max-width:580px;">
            <div class="card-header"><i class="fa fa-sliders-h"></i> General Settings</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                    <div class="form-group">
                        <label>Portal Name</label>
                        <input type="text" name="portal_name" class="form-control"
                               value="<?= htmlspecialchars($portalName) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Current Academic Session</label>
                        <input type="text" name="academic_session" class="form-control"
                               placeholder="e.g. 2025/2026"
                               value="<?= htmlspecialchars($currentSession) ?>" required>
                        <small style="color:var(--gray);">This appears on allocation letters and registration forms.</small>
                    </div>

                    <div class="form-group">
                        <label>Hostel Fee Amount (₦)</label>
                        <input type="number" name="hostel_fee_amount" class="form-control"
                               min="1000" step="500"
                               value="<?= htmlspecialchars($currentFee) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Maximum Bed Spaces</label>
                        <input type="number" name="max_bed_spaces" class="form-control"
                               min="1" max="1000"
                               value="<?= htmlspecialchars($maxBeds) ?>" required>
                    </div>

                    <div class="form-group">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:600;">
                            <input type="checkbox" name="registration_open" value="1"
                                   <?= $regOpen === '1' ? 'checked' : '' ?>
                                   style="width:18px; height:18px; cursor:pointer;">
                            Student Registration Open
                        </label>
                        <small style="color:var(--gray);">
                            When unchecked, new students cannot register — existing students can still log in.
                        </small>
                    </div>

                    <hr style="margin:20px 0;">

                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>

        <!-- Danger zone -->
        <div class="card" style="max-width:580px; margin-top:24px; border:2px solid var(--danger);">
            <div class="card-header" style="background:var(--danger);">
                <i class="fa fa-exclamation-triangle"></i> Danger Zone
            </div>
            <div class="card-body">
                <p style="font-size:14px; color:var(--gray); margin-bottom:16px;">
                    These actions are irreversible. Use with extreme caution.
                </p>
                <p style="font-size:13px;">
                    To reset the portal or manage admin passwords, use the
                    <code>setup.php</code> script directly on the server (do not expose it publicly).
                </p>
            </div>
        </div>

    </main>
</div>

<?php include '../includes/footer.php'; ?>
