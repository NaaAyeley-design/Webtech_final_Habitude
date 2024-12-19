<?php
header('Content-Type: application/json');
require_once '../../db/config.php';

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'user' => null,
    'redirect' => null
];

try {
    // Validate input
    if (!isset($data['email']) || !isset($data['password'])) {
        throw new Exception('Email and password are required');
    }

    // Sanitize inputs
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $password = $data['password'];

    // Prepare SQL statement with JOIN to fetch data from `users` and `userprofiles`
    $stmt = $conn->prepare("
        SELECT 
            u.user_id, 
            u.email, 
            u.password_hash, 
            u.role_id, 
            u.is_active,
            p.first_name, 
            p.last_name
        FROM users u
        JOIN user_profiles p ON u.user_id = p.user_id
        WHERE u.email = ? AND u.is_active = 1
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }

    // Bind parameters and execute
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    // Get result
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Invalid email or password');
    }

    $user = $result->fetch_assoc();

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        throw new Exception('Invalid email or password');
    }

    switch ($user['role_id']) {
        case 1:
            $redirect = 'http://169.239.251.102:3341/~ayeley.aryee/Habitude%20Self-Improvement%20Tracker/view/html/1dashboard.php';
            break;
        case 2:
            $redirect = 'http://169.239.251.102:3341/~ayeley.aryee/Habitude%20Self-Improvement%20Tracker/view/html/1admindashboard.php';
            break;
        default:
            $redirect = 'http://169.239.251.102:3341/~ayeley.aryee/Habitude%20Self-Improvement%20Tracker/view/html/1dashboard.php';
            break;
    }

    // Set success response
    $response['success'] = true;
    $response['message'] = 'Login successful';
    $response['redirect'] = $redirect;
    
    // Remove password_hash before sending user data
    unset($user['password_hash']);
    $response['user'] = $user;

    // Update last login timestamp
    $updateStmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?");
    $updateStmt->bind_param("i", $user['user_id']);
    $updateStmt->execute();
    $updateStmt->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
?>
