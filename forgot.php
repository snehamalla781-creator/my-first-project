<!-- <?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update->bind_param("ss", $hashed, $email);
        $update->execute();
        echo "<script>alert('Password reset successful!'); window.location.href='index.php';</script>";
        $update->close();
    } else {
        echo "<script>alert('No account found with this email'); window.location.href='index.php';</script>";
    }

    $stmt->close();
}
$conn->close();
?> -->
<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $security_question = $_POST['security_question'];
    $security_answer = strtolower(trim($_POST['security_answer']));
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    // Check user with email
    $stmt = $conn->prepare("SELECT security_question, security_answer FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Check question & answer
        if ($user['security_question'] === $security_question &&
            password_verify($security_answer, $user['security_answer'])) {

            // Update password
            $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update->bind_param("ss", $new_password, $email);
            $update->execute();
            $update->close();

            echo "<script>alert('Password reset successful!'); window.location.href='index.php';</script>";
        } else {
            echo "<script>alert('Security question or answer is incorrect'); window.location.href='index.php';</script>";
        }

    } else {
        echo "<script>alert('No account found with this email'); window.location.href='index.php';</script>";
    }

    $stmt->close();
}
$conn->close();
?>
