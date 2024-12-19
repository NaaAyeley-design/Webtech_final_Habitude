<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
    $requiredFields = ['email', 'password', 'first_name', 'last_name'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            $response['errors'][$field] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
        }
    }

    if (!empty($response['errors'])) {
        throw new Exception("Validation failed");
    }

    // Additional input validations
    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $response['errors']['email'] = "Invalid email format.";
        throw new Exception("Email validation failed");
    }

    // Password strength validation
    if (strlen($input['password']) < 8) {
        $response['errors']['password'] = "Password must be at least 8 characters long.";
        throw new Exception("Password validation failed");
    }

    $email = $conn->real_escape_string($input['email']);
    $firstName = $conn->real_escape_string($input['first_name']);
    $lastName = $conn->real_escape_string($input['last_name']);
    $password = $input['password'];

    // Check if email already exists
    $checkEmailStmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $checkEmailStmt->bind_param("s", $email);
    $checkEmailStmt->execute();
    $checkEmailStmt->store_result();
    
    if ($checkEmailStmt->num_rows > 0) {
        $response['errors']['email'] = "An account with this email already exists.";
        throw new Exception("Duplicate email");
    }
    $checkEmailStmt->close();

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Begin transaction
    $conn->begin_transaction();

    // Insert into users table
    $userStmt = $conn->prepare("INSERT INTO users (email, password_hash, created_at) VALUES (?, ?, NOW())");
    $userStmt->bind_param("ss", $email, $hashedPassword);
    
    if (!$userStmt->execute()) {
        throw new Exception("User insert failed: " . $userStmt->error);
    }
    
    $userId = $conn->insert_id;

    // Insert into user_profiles table
    $profileStmt = $conn->prepare("INSERT INTO user_profiles (user_id, first_name, last_name) VALUES (?, ?, ?)");
    $profileStmt->bind_param("iss", $userId, $firstName, $lastName);
    
    if (!$profileStmt->execute()) {
        throw new Exception("Profile insert failed: " . $profileStmt->error);
    }

    // Commit transaction
    $conn->commit();
    $response['success'] = true;

} catch (Exception $e) {
    // Rollback transaction and log error
    if (isset($conn) && $conn->connect_error === null) {
        $conn->rollback();
    }
    
    error_log("Registration error: " . $e->getMessage());
    
    // If no specific errors were set, add a generic error
    if (empty($response['errors'])) {
        $response['errors']['form'] = "Registration failed: " . $e->getMessage();
    }

} finally {
    if (isset($userStmt)) $userStmt->close();
    if (isset($profileStmt)) $profileStmt->close();
    if (isset($conn)) $conn->close();
}

// Output response
echo json_encode($response);
?>