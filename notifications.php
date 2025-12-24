<?php
session_start();
include_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

// Fetch notifications for this user (latest first)
$query = "SELECT n.id, n.type, n.message, n.is_read, n.created_at, 
                 r.title AS recipe_title, u.username AS actor
          FROM notifications n
          LEFT JOIN recipes r ON n.recipe_id = r.id
          LEFT JOIN users u ON u.id = n.actor_id
          WHERE n.user_id = $loggedInUserId
          ORDER BY n.created_at DESC
          LIMIT 8";

$result = mysqli_query($conn, $query);

$notifications = [];
$unreadCount = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = [
        'id' => $row['id'],
        'type' => $row['type'],
        'message' => $row['message'],
        'is_read' => $row['is_read'],
        'created_at' => $row['created_at'],
        'recipe_title' => $row['recipe_title'],
        'actor' => $row['actor']
    ];
    if ($row['is_read'] == 0) {
        $unreadCount++;
    }
}

// Return JSON response
echo json_encode([
    'status' => 'success',
    'unread_count' => $unreadCount,
    'notifications' => $notifications
]);
