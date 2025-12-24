<?php
session_start();
include 'db_connect.php'; // your DB connection

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in.']);
    exit;
}

// Read JSON input
$data = json_decode(file_get_contents('php://input'), true);
$recipe_id = $data['recipe_id'] ?? 0;
$comment = trim($data['comment'] ?? '');

if (!$recipe_id || !$comment) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

// Insert comment into database
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("INSERT INTO comments (recipe_id, user_id, comment) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $recipe_id, $user_id, $comment);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}

$stmt->close();
$conn->close();
