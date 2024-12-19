<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add this near the top of login_user.php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Preflight request
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

$response = ['success' => false, 'errors' => []];

// Database connection
require_once '../db/config.php';

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Decode JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception("Invalid JSON input");
    }

    // Validate input data
    $requiredFields = ['email', 'password'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing or empty $field");
        }
    }

    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception("Invalid email format");
    }

    $password = $input['password'];

    // Get user from database
    $stmt = $conn->prepare("SELECT u.user_id, u.password_hash, u.role_id, up.first_name, up.last_name 
                           FROM users u 
                           LEFT JOIN user_profiles up ON u.user_id = up.user_id 
                           WHERE u.email = ?");
    $stmt->bind_param("s", $email);
    
    if (!$stmt->execute()) {
        throw new Exception("Login query failed");
    }

    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Invalid email or password");
    }

    $user = $result->fetch_assoc();

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        throw new Exception("Invalid email or password");
    }

    // Start session and store user data
    session_start();
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $email;
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['role_id'] = $user['role_id'];

    $response['success'] = true;
    $response['user'] = [
        'email' => $email,
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'role_id' => $user['role_id']
    ];

    // Redirect based on role_id
    if ($user['role_id'] == 1) {
        $response['redirect'] = '../../view/html/1dashboard.php';
    } elseif ($user['role_id'] == 2) {
        $response['redirect'] = '../../view/html/1admindashboard.php';
    } else {
        throw new Exception("Invalid role");
    }

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $response['errors']['form'] = "Login failed: " . $e->getMessage();

} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}

// Output response
echo json_encode($response);
?>
