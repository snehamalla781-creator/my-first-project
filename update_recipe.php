<?php
// connect to DB
include 'db_connect.php';

// check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = $_POST['id'];
    $title       = $_POST['title'];
    $category    = $_POST['category'];
    $servings    = $_POST['servings'];
    $prep_time   = $_POST['prep_time'];
    $cook_time   = $_POST['cook_time'];
    $difficulty  = $_POST['difficulty'];
    $emoji       = $_POST['emoji'];
    $description = $_POST['description'];
    $ingredients = $_POST['ingredients'];
    $instructions= $_POST['instructions'];

    // handle image upload (optional)
    if (!empty($_FILES['image']['name'])) {
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $target = "images/" . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            // update with image
            $sql = "UPDATE recipes 
                    SET title=?, category=?, servings=?, prep_time=?, cook_time=?, difficulty=?, emoji=?, description=?, ingredients=?, instructions=?, image=?
                    WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssissssssssi", $title, $category, $servings, $prep_time, $cook_time, $difficulty, $emoji, $description, $ingredients, $instructions, $imageName, $id);
        } else {
            die("Image upload failed.");
        }
    } else {
        // update without image
        $sql = "UPDATE recipes 
                SET title=?, category=?, servings=?, prep_time=?, cook_time=?, difficulty=?, emoji=?, description=?, ingredients=?, instructions=?
                WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssisssssssi", $title, $category, $servings, $prep_time, $cook_time, $difficulty, $emoji, $description, $ingredients, $instructions, $id);
    }

    if ($stmt->execute()) {
        header("Location: user_dashboard.php?msg=Recipe updated successfully");
        exit();
    } else {
        echo "Error updating recipe: " . $conn->error;
    }
}
?>
