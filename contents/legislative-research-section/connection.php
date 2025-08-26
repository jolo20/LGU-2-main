<?php

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lgu2";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}