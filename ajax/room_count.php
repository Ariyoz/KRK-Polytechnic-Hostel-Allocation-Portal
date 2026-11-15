<?php
// Returns available room count for a given gender
require_once '../config/db.php';
header('Content-Type: application/json');

$gender = $_GET['gender'] ?? '';
if (!in_array($gender, ['male', 'female'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM hostel_rooms WHERE status='available' AND gender=?");
$stmt->bind_param("s", $gender);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
echo json_encode(['count' => (int) $row['c']]);
