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

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <h2 class="page-title"><i class="fa fa-upload"></i> Upload / Add Hostel Rooms</h2>

        <div class="alert alert-info">
            <strong>Capacity:</strong> <?= $totalRooms ?>/200 rooms uploaded.
            Remaining: <strong><?= $remaining ?></strong> bed space(s).
        </div>

        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

        <div class="card" style="max-width:500px;">
            <div class="card-header">Add Room Block</div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label>Block Name / Letter</label>
                        <input type="text" name="block" class="form-control"
                               placeholder="e.g. A, B, C" maxlength="5"
                               value="<?= htmlspecialchars($_POST['block'] ?? '') ?>" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Starting Room Number</label>
                            <input type="number" name="room_start" class="form-control"
                                   placeholder="e.g. 1" min="1"
                                   value="<?= htmlspecialchars($_POST['room_start'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Number of Rooms to Add</label>
                            <input type="number" name="room_count" class="form-control"
                                   placeholder="e.g. 10" min="1" max="<?= min(100, $remaining) ?>"
                                   value="<?= htmlspecialchars($_POST['room_count'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" class="form-control" required>
                            <option value="">-- Select --</option>
                            <option value="male"   <?= (($_POST['gender'] ?? '') === 'male')   ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= (($_POST['gender'] ?? '') === 'female') ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" <?= $remaining <= 0 ? 'disabled' : '' ?>>
                        <i class="fa fa-plus"></i> Add Rooms
                    </button>
                </form>
            </div>
        </div>

        <div style="margin-top:20px;">
            <a href="rooms.php" class="btn btn-light"><i class="fa fa-list"></i> View All Rooms</a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
