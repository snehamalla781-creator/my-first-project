<?php
session_start();
include 'db_connect.php';

// ✅ Set Nepal timezone
date_default_timezone_set('Asia/Kathmandu');

if (!isset($_SESSION['user_id'])) {
    header("Location: user_dashboard.php?show=my-recipes#my-recipes-section");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null; // hidden input for edit
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $difficulty = $_POST['difficulty'] ?? '';
    $servings = $_POST['servings'] ?? 1;
    $cook_time = $_POST['cook_time'] ?? '';
    $prep_time = $_POST['prep_time'] ?? '';
    $emoji = $_POST['emoji'] ?? '';
    $ingredients = $_POST['ingredients'] ?? '';
    $instructions = $_POST['instructions'] ?? '';
    $old_image = $_POST['old_image'] ?? null;
    $is_exported = $_POST['is_exported'] ?? 0; // ✅ Export flag

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $imageName = time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "images/$imageName");
    } else {
        $imageName = $old_image; // keep old image on edit
    }

    if ($id) {
        // UPDATE existing recipe
        $stmt = $conn->prepare("
            UPDATE recipes SET 
                title=?, description=?, image=?, category=?, difficulty=?, servings=?, 
                cook_time=?, prep_time=?, emoji=?, ingredients=?, instructions=?, is_exported=? 
            WHERE id=? AND user_id=?
        ");
        $stmt->bind_param(
            "sssssiissssiii",
            $title, $description, $imageName, $category, $difficulty, $servings,
            $cook_time, $prep_time, $emoji, $ingredients, $instructions, $is_exported, $id, $user_id
        );
    } else {
        // ✅ INSERT new recipe with PHP-generated created_at
        $created_at = date('Y-m-d H:i:s'); // current Nepal time

        $stmt = $conn->prepare("
            INSERT INTO recipes 
                (user_id, title, description, image, category, difficulty, servings, cook_time, prep_time, emoji, ingredients, instructions, is_exported, views, created_at) 
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)
        ");
        $stmt->bind_param(
            "isssssiiisssis",
            $user_id, $title, $description, $imageName, $category, $difficulty, $servings,
            $cook_time, $prep_time, $emoji, $ingredients, $instructions, $is_exported, $created_at
        );
    }

    if ($stmt->execute()) {
        // Redirect based on export or save
        if ($is_exported == 1) {
            header("Location: user_dashboard.php?show=feed&status=exported#feed-section");
        } else {
            header("Location: user_dashboard.php?show=my-recipes&status=saved#my-recipes-section");
        }
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
