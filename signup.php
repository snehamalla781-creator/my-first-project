<?php
include 'db_connect.php'; // database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $security_question = $_POST['security_question'];
    $security_answer = password_hash(strtolower(trim($_POST['security_answer'])), PASSWORD_DEFAULT);

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = "user"; // default role

    // Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('Email already registered! Please login.'); window.location.href='index.php';</script>";
        $check->close();
        exit();
    }
    $check->close();

    // Insert new user
        $sql = "INSERT INTO users (username, email, password, role, security_question, security_answer) 
        VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $username, $email, $password, $role, $security_question, $security_answer);

    if ($stmt->execute()) {
        echo "<script>alert('Signup successful! You can now login.'); window.location.href='index.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>
