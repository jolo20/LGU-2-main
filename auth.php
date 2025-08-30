<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root = str_repeat('../', substr_count(trim($_SERVER['PHP_SELF'], '/'), '/') - 1);

if (empty($_SESSION['user_id'])) {
    $_SESSION['flash'] = 'Please login first';
    header("Location: {$root}login.php");
    exit;
}
