<?php
include 'db_connect.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $res = $conn->query("SELECT COUNT(*) AS total FROM comments WHERE recipe_id = $id AND is_deleted = 0");
    $row = $res->fetch_assoc();
    echo $row['total'];
}
?>
