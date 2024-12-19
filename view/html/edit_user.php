<?php
require_once 'timerphp.php';
SessionManager::start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 2) {
    header('Location: 1loginpage.php');
    exit;
}

$db = Database::getInstance();

// Get user data
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = null;
$message = '';

if ($userId > 0) {
    $query = "SELECT u.*, up.first_name, up.last_name 
              FROM users u 
              LEFT JOIN user_profiles up ON u.user_id = up.user_id 
              WHERE u.user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Prepare the user update statement
        $updateUserQuery = "UPDATE users SET 
                          email = ?,
                          is_active = ?,
                          role_id = ?
                          WHERE user_id = ?";
        $stmt = $db->prepare($updateUserQuery);
        
        $email = $_POST['email'];
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $roleId = (int)$_POST['role_id'];
        
        $stmt->bind_param("siii", $email, $isActive, $roleId, $userId);
        $userUpdateSuccess = $stmt->execute();

        // Prepare the profile update statement
        $updateProfileQuery = "UPDATE user_profiles SET 
                             first_name = ?,
                             last_name = ?
                             WHERE user_id = ?";
        $stmt = $db->prepare($updateProfileQuery);
        
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        
        $stmt->bind_param("ssi", $firstName, $lastName, $userId);
        $profileUpdateSuccess = $stmt->execute();

        if ($userUpdateSuccess && $profileUpdateSuccess) {
            $message = 'User updated successfully';
            
            // Refresh user data
            $query = "SELECT u.*, up.first_name, up.last_name 
                     FROM users u 
                     LEFT JOIN user_profiles up ON u.user_id = up.user_id 
                     WHERE u.user_id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            throw new Exception('Failed to update user');
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Habitude</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        input[type="email"],
        input[type="text"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .checkbox-group {
            margin-top: 10px;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            background: #e8f5e9;
            color: #2e7d32;
        }
        .error {
            background: #ffebee;
            color: #c62828;
        }
        .button {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .button:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit User</h1>
        
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($user): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="is_active" <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                        Active Account
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role_id">
                        <option value="1" <?php echo $user['role_id'] == 1 ? 'selected' : ''; ?>>User</option>
                        <option value="2" <?php echo $user['role_id'] == 2 ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                
                <button type="submit" class="button">Update User</button>
                <a href="1admindashboard.php" style="margin-left: 10px; text-decoration: none; color: #666;">Cancel</a>
            </form>
        <?php else: ?>
            <p>User not found.</p>
            <a href="1admindashboard.php" style="text-decoration: none; color: #666;">Back to Dashboard</a>
        <?php endif; ?>
    </div>
</body>
</html>