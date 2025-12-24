<?php
include 'db_connect.php';
header('Content-Type: application/json');
session_start();

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode([]);
    exit;
}

// Fetch last 10 searches
$stmt = $conn->prepare("SELECT query FROM user_searches WHERE user_id=? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$searches = [];
while ($row = $result->fetch_assoc()) {
    $searches[] = $row['query'];
}

echo json_encode($searches);
?>
