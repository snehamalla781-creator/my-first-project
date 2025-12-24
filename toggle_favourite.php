<?php
include 'db_connect.php';
session_start();
header('Content-Type: application/json');
error_reporting(0); // hide notices/warnings

// --- Check login ---
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$recipe_id = $_POST['recipe_id'] ?? null;

// --- Validate recipe_id ---
if (!$recipe_id || !is_numeric($recipe_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid recipe id']);
    exit;
}

// --- Check if favourite exists ---
$stmt = $conn->prepare("SELECT 1 FROM favourites WHERE user_id=? AND recipe_id=?");
$stmt->bind_param("ii", $user_id, $recipe_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // --- Remove favourite ---
    $stmtDel = $conn->prepare("DELETE FROM favourites WHERE user_id=? AND recipe_id=?");
    $stmtDel->bind_param("ii", $user_id, $recipe_id);
    $stmtDel->execute();

    echo json_encode(['success' => true, 'favourited' => false]);
} else {
    // --- Add favourite ---
    $stmtIns = $conn->prepare("INSERT INTO favourites (user_id, recipe_id, created_at) VALUES (?, ?, NOW())");
    $stmtIns->bind_param("ii", $user_id, $recipe_id);
    $stmtIns->execute();

    echo json_encode(['success' => true, 'favourited' => true]);
}
?>
