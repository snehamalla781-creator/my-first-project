<?php
include 'db_connect.php';
session_start();

$user_id = $_SESSION['user_id'] ?? 0;
$q = $_GET['q'] ?? '';

if (!$user_id || !$q) {
    exit; // ignore if not logged in or empty query
}

// Trim whitespace
$q = trim($q);

// Check if this search already exists for this user
$stmt = $conn->prepare("SELECT id FROM user_searches WHERE user_id=? AND query=?");
$stmt->bind_param("is", $user_id, $q);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Already exists → update timestamp to make it most recent
    $stmt = $conn->prepare("UPDATE user_searches SET created_at=NOW() WHERE user_id=? AND query=?");
    $stmt->bind_param("is", $user_id, $q);
    $stmt->execute();
} else {
    // Insert new search
    $stmt = $conn->prepare("INSERT INTO user_searches (user_id, query) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $q);
    $stmt->execute();
}

// Optional: return success (not necessary for fetch)
echo json_encode(['status' => 'ok']);
?>
