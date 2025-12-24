<?php
session_start();
include 'db_connect.php'; // your database connection

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'Someone';

$recipe_id = $_POST['recipe_id'] ?? ($_GET['recipe_id'] ?? 0);
$commentText = $_POST['comment'] ?? null;

// If comment is submitted, insert it
if ($user_id && $commentText && $recipe_id) {
    $commentText = mysqli_real_escape_string($conn, $commentText);

    // Insert comment
    $conn->query("INSERT INTO notifications (user_id, actor_id, recipe_id, type, message, is_read, created_at)
    VALUES ('$recipeOwnerId', '$user_id', '$recipe_id', 'rating', '$message', 0, NOW())");

    // Insert notification for recipe owner
    $recipeOwnerRes = $conn->query("SELECT user_id FROM recipes WHERE id='$recipe_id'");
    if ($recipeOwnerRes && $recipeOwnerRes->num_rows > 0) {
        $recipeOwnerId = $recipeOwnerRes->fetch_assoc()['user_id'];

        if ($recipeOwnerId != $user_id) { // don't notify self
            $message = "💬 $username commented: '$commentText'";
            $conn->query("INSERT INTO notifications (user_id, recipe_id, type, message) 
                          VALUES ('$recipeOwnerId', '$recipe_id', 'comment', '$message')");
        }
    }
}

$sql = "SELECT c.id, c.comment, c.user_id, u.username
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.recipe_id = ? AND c.is_deleted = 0
        ORDER BY c.created_at DESC
        ";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = [
        'id' => $row['id'],
        'comment' => $row['comment'],
        'username' => $row['username'],
        'can_delete' => ($user_id && $user_id == $row['user_id'])
    ];
}

echo json_encode($comments);

$stmt->close();
$conn->close();
?>
