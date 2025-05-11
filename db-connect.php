<?php
// db-connect.php
// Database connection configuration

$db_host = "localhost";
$db_user = "root";
$db_pass = ""; // Consider using a more secure password in production
$db_name = "enrollment_list";

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for proper character encoding
$conn->set_charset("utf8mb4");
?>