<?php
include 'db_connect.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("UPDATE recipes SET views = views + 1 WHERE id = $id");
    $res = $conn->query("SELECT views FROM recipes WHERE id = $id");
    $row = $res->fetch_assoc();
    echo $row['views'];
}
?>
