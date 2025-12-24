<?php
$servername = "localhost";
$username = "root";     // XAMPP default
$password = "";         // XAMPP default
$dbname = "recipesdb";  // your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
// echo "Connected successfully";


//  Set MySQL session timezone to match PHP timezone
$conn->query("SET time_zone = '+05:45'");


?>
