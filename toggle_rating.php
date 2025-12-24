<?php
include 'db_connect.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Someone'; // For notification message
$recipe_id = $_POST['recipe_id'] ?? null;
$rating = $_POST['rating'] ?? null;

if (!$recipe_id || !$rating) {
    echo json_encode(['success' => false, 'message' => 'Missing recipe id or rating']);
    exit;
}

$rating = (int)$rating;
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating value']);
    exit;
}

// Check if user already rated
$check = $conn->query("SELECT * FROM ratings WHERE user_id='$user_id' AND recipe_id='$recipe_id'");
if ($check && $check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already rated this recipe']);
    exit;
} else {
    // Insert rating
    $conn->query("INSERT INTO notifications (user_id, actor_id, recipe_id, type, message, is_read, created_at)
    VALUES ('$recipeOwnerId', '$user_id', '$recipe_id', 'rating', '$message', 0, NOW())");

    // Insert notification for recipe owner
    $recipeOwnerRes = $conn->query("SELECT user_id FROM recipes WHERE id='$recipe_id'");
    if ($recipeOwnerRes && $recipeOwnerRes->num_rows > 0) {
        $recipeOwnerId = $recipeOwnerRes->fetch_assoc()['user_id'];

        if ($recipeOwnerId != $user_id) { // Don't notify self
            $message = "⭐ $username rated your recipe";
            $conn->query("INSERT INTO notifications (user_id, recipe_id, type, message) 
                          VALUES ('$recipeOwnerId', '$recipe_id', 'rating', '$message')");
        }
    }

    // Calculate new average rating
    $avgRes = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings FROM ratings WHERE recipe_id='$recipe_id'");
    $avgRow = $avgRes->fetch_assoc();

    echo json_encode([
        'success' => true,
        'avg_rating' => round($avgRow['avg_rating'], 1),
        'total_ratings' => (int)$avgRow['total_ratings']
    ]);
}
?>
