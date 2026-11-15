<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$faculty_id = intval($_GET['faculty_id'] ?? 0);

if (!$faculty_id) {
    echo json_encode([]);
    exit();
}

$stmt = $conn->prepare("SELECT id, name FROM departments WHERE faculty_id = ? ORDER BY name");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();

$departments = [];
while ($row = $result->fetch_assoc()) {
    $departments[] = ['id' => $row['id'], 'name' => $row['name']];
}

echo json_encode($departments);
?>
