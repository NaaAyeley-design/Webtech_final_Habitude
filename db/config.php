<?php
$servername = "localhost";  
$username = "root";         // Database username
$password = "";             // Database password
$dbname = "webtech_habitude";  // Database name


// $servername = "localhost";  
// $username = "ayeley.aryee";         // Database username
// $password = "esuon2004";             // Database password
// $dbname = "webtech_fall2024_ayeley_aryee";  // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>