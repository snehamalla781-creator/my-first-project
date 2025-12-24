<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); // redirect if not logged in
    exit();
}
// back garna namilna lai
echo '<script>
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };
</script>';
?>

<h1>Welcome, <?php echo $_SESSION['username']; ?>!</h1>
<a href="logout.php">Logout</a>