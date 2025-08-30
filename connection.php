<?php 
$host = "localhost";
$user = "root1";
$pass = "";
$dbname = "lgu2";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


