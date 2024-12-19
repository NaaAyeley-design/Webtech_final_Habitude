<?php
header('Content-Type: application/json');
require_once "../db/config.php";
session_start();


// Database connection details
// $servername = 'localhost';
// $dbname = 'webtech_habitude';
// $username = 'root';
// $password = '';

// $servername = "localhost";  
// $username = "ayeley.aryee";         // Database username
// $password = "esuon2004";             // Database password
// $dbname = "webtech_fall2024_ayeley_aryee";  // Database name

try {
    // Receive JSON payload
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Validate input
    if (!isset($data['user_id']) || !isset($data['content'])) {
        throw new Exception('Invalid input');
    }

    // Connect to database
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare SQL statement
    $stmt = $pdo->prepare("INSERT INTO journal_entries (user_id, content, mood, tags, created_at) VALUES (?, ?, ?, ?, NOW())");
    
    // Convert tags to JSON for storage
    $tagsJson = json_encode($data['tags'] ?? []);

    // Execute statement
    $result = $stmt->execute([
        $data['user_id'], 
        $data['content'], 
        $data['mood'] ?? 'neutral',
        $tagsJson
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Entry saved successfully']);
    } else {
        throw new Exception('Failed to save entry');
    }

} catch (Exception $e) {
    // Log the full error for debugging
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}