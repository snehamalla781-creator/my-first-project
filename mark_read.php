<?php
session_start();
include_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = $loggedInUserId");

echo json_encode(['status' => 'success']);
