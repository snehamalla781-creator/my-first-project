<?php
include 'db_connect.php';

if (isset($_GET['email'])) {
    $email = trim($_GET['email']);
    $stmt = $conn->prepare("SELECT security_question FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($question);
    if ($stmt->fetch()) {
        echo $question;
    } else {
        echo 'not_found';
    }
    $stmt->close();
}
$conn->close();
?>
