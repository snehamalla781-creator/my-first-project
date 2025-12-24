<?php
session_start();
include_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$logged_in_id = $_SESSION['user_id'];
$profile_id = (int)$_POST['profile_id'] ?? 0;

if (isset($_POST['follow'])) {
    $stmt = $conn->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $logged_in_id, $profile_id);
    $stmt->execute();
} elseif (isset($_POST['unfollow'])) {
    $stmt = $conn->prepare("DELETE FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmt->bind_param("ii", $logged_in_id, $profile_id);
    $stmt->execute();
}

header("Location: my_profile.php?user_id=$profile_id");
exit();
?>
