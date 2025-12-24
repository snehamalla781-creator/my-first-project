<?php
session_start();
session_unset();
session_destroy();
header("Location: index.php"); // now homepage is accessible again
exit();
?>
