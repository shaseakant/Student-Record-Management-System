<?php
$host = "localhost";
$user = "u950626721_royalorbit";
$pass = "Royalorbit123";
$dbname = "u950626721_royalorbit";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
