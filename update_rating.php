<?php
include 'db_connect.php';

if (isset($_POST['id'], $_POST['rating'])) {
    $id = intval($_POST['id']);
    $rating = floatval($_POST['rating']);

    // Simple example: just replace old rating (you can improve to average system)
    $conn->query("UPDATE recipes SET rating = $rating WHERE id = $id");

    $res = $conn->query("SELECT rating FROM recipes WHERE id = $id");
    $row = $res->fetch_assoc();
    echo number_format($row['rating'], 1);
}
?>
