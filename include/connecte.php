<?php
$host = "localhost";
$username = "phpmyadmin";
$password = "tp";
$database = "enigmatech";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
