<?php
session_start();
include 'db_connect.php'; // your DB connection

header('Content-Type: application/json');

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in.']);
    exit;
}

// Get comment ID from request
$data = json_decode(file_get_contents('php://input'), true);
$comment_id = $data['comment_id'] ?? 0;

if (!$comment_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid comment ID.']);
    exit;
}

// Soft delete: mark as deleted (or use DELETE if you prefer)
$stmt = $conn->prepare("UPDATE comments SET is_deleted = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $comment_id, $_SESSION['user_id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete comment.']);
}

$stmt->close();
$conn->close();
?>
