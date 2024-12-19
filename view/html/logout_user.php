<?php
// logout.php
//require_once '../../.php';  // Changed from utilities.php to utils.php

session_start();
$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

session_destroy();
header("Location: 1loginpage.php");
exit();
?>