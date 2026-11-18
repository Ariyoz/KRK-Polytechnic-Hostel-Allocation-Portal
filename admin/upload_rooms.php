<?php
$pageTitle = "Upload Rooms";
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }
require_once 'auth_guard.php';
require_role(['ict','dsa'], 'Only ICT staff or the DSA can add hostel rooms.');

$error   = '';
$success = '';

// Count existing rooms
$totalRooms = $conn->query("SELECT COUNT(*) as c FROM hostel_rooms")->fetch_assoc()['c'];
$remaining  = 200 - $totalRooms;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $block      = trim($_POST['block'] ?? '');
    $room_start = intval($_POST['room_start'] ?? 0);
    $room_count = intval($_POST['room_count'] ?? 0);
    $gender     = $_POST['gender'] ?? '';

    if (!$block || !$room_start || !$room_count || !$gender) {
        $error = 'All fields are required.';
    } elseif ($room_count > $remaining) {
        $error = "You can only add up to $remaining more rooms (200 max total).";
    } elseif ($room_count < 1 || $room_count > 100) {
        $error = 'Room count must be between 1 and 100.';
    } else {
        $added = 0;
        $skipped = 0;
        for ($i = 0; $i < $room_count; $i++) {
            $roomNum = $block . sprintf('%03d', $room_start + $i);

            // Check duplicate
            $dup = $conn->prepare("SELECT id FROM hostel_rooms WHERE room_number=?");
            $dup->bind_param("s", $roomNum);
            $dup->execute();
            $dup->store_result();

            if ($dup->num_rows > 0) {
                $skipped++;
            } else {
                $ins = $conn->prepare("INSERT INTO hostel_rooms (room_number, block, gender) VALUES (?, ?, ?)");
                $ins->bind_param("sss", $roomNum, $block, $gender);
                $ins->execute();
                $added++;
            }
        }

        // Re-count
        $totalRooms = $conn->query("SELECT COUNT(*) as c FROM hostel_rooms")->fetch_assoc()['c'];
        $remaining  = 200 - $totalRooms;

        $success = "$added room(s) added successfully." . ($skipped ? " $skipped duplicate(s) skipped." : '');
    }
}

$roleLabel = ['dsa'=>'Dean of Student Affairs','registrar'=>'Registrar Office','ict'=>'ICT Department'][$_SESSION['admin_role'] ?? ''] ?? strtoupper($_SESSION['admin_role'] ?? '');
$capacityPct = round(($totalRooms / 200) * 100);

$pageCss = ['admin.css','admin-extra.css'];
include '../includes/header.php';
?>

<?php include 'sidebar.php'; ?>

<div class="page-head">
                <span class="eyebrow">Hostel management</span>
                <h1>Add hostel rooms</h1>
                <p class="sub">Generate a numbered block of rooms in one go, e.g. Block A, rooms 1–20.</p>
            </div>

            <div class="card mb-5">
                <p><strong><?= $totalRooms ?></strong> of 200 rooms on file &mdash; <strong><?= $remaining ?></strong> bed space<?= $remaining !== 1 ? 's' : '' ?> remaining.</p>
                <div class="progress">
                    <div class="progress-bar <?= $capacityPct > 90 ? 'is-danger' : ($capacityPct > 70 ? 'is-warning' : '') ?>" style="width:<?= $capacityPct ?>%;"></div>
                </div>
            </div>

            <?php if ($error): ?><div class="alert alert-danger mb-4"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success mb-4"><?= $success ?></div><?php endif; ?>

            <div class="card is-narrow">
                <form method="POST">
                    <div class="field">
                        <label class="label req">Block name / letter</label>
                        <input type="text" name="block" class="input"
                               placeholder="e.g. A, B, C" maxlength="5"
                               value="<?= htmlspecialchars($_POST['block'] ?? '') ?>" required>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label class="label req">Starting room number</label>
                            <input type="number" name="room_start" class="input"
                                   placeholder="e.g. 1" min="1"
                                   value="<?= htmlspecialchars($_POST['room_start'] ?? '') ?>" required>
                        </div>
                        <div class="field">
                            <label class="label req">Number of rooms to add</label>
                            <input type="number" name="room_count" class="input"
                                   placeholder="e.g. 10" min="1" max="<?= min(100, $remaining) ?>"
                                   value="<?= htmlspecialchars($_POST['room_count'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label req">Gender</label>
                        <select name="gender" class="select" required>
                            <option value="">-- Select --</option>
                            <option value="male"   <?= (($_POST['gender'] ?? '') === 'male')   ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= (($_POST['gender'] ?? '') === 'female') ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" <?= $remaining <= 0 ? 'disabled' : '' ?>>Add rooms</button>
                </form>
            </div>

            <p class="mt-4">
                <a href="rooms.php" class="btn btn-outline btn-sm">View all rooms</a>
            </p>

    </div><!-- portal-content -->
  </div><!-- portal-main -->
</div><!-- portal -->

<?php include '../includes/footer.php'; ?>
