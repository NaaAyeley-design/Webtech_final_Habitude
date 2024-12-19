<?php
header('Content-Type: application/json');
require_once "../db/config.php";
session_start();

$input = json_decode(file_get_contents('php://input'), true);

$response = ['success' => false, 'message' => 'Unknown error'];

try {
    // Add more verbose logging for debugging
    error_log('Received input: ' . print_r($input, true));

    // Validate input with more specific checks
    if (empty($input['user_id']) || empty($input['entry_id']) || empty($input['content'])) {
        throw new Exception('Missing or empty required parameters: ' . 
            (empty($input['user_id']) ? 'user_id ' : '') . 
            (empty($input['entry_id']) ? 'entry_id ' : '') . 
            (empty($input['content']) ? 'content' : ''));
    }

    // Verify user authentication
    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $input['user_id']) {
        throw new Exception('Unauthorized access');
    }
    
    // Database connection (use your actual credentials)
    $db = new mysqli('localhost', 'root', '', 'webtech_habitude');
    
    if ($db->connect_error) {
        throw new Exception('Database connection failed: ' . $db->connect_error);
    }

    // Log received entry_id and user_id for debugging
    error_log('entry_id received: ' . $input['entry_id']);
    error_log('user_id received: ' . $input['user_id']);

    // First, verify the entry exists and belongs to the user
    $check_stmt = $db->prepare("SELECT COUNT(*) as count FROM journal_entries WHERE entry_id = ? AND user_id = ?");
    $check_stmt->bind_param('ii', $input['entry_id'], $input['user_id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();
    
    // Check if entry exists for the user
    if ($check_row['count'] == 0) {
        throw new Exception('Entry not found or does not belong to this user');
    }

    // Log if entry is found
    error_log('Entry found: ' . $check_row['count']);

    // Prepare and execute update
    $stmt = $db->prepare("UPDATE journal_entries SET content = ?, mood = ?, tags = ? WHERE entry_id = ? AND user_id = ?");
    $tags = json_encode($input['tags'] ?? []); // Ensure tags is always an array
    $stmt->bind_param('sssii', $input['content'], $input['mood'], $tags, $input['entry_id'], $input['user_id']);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response = ['success' => true, 'message' => 'Entry updated successfully'];
        } else {
            $response = ['success' => false, 'message' => 'No entry found or no changes made'];
        }
    } else {
        throw new Exception('Update failed: ' . $stmt->error);
    }

    $stmt->close();
    $db->close();
} catch (Exception $e) {
    error_log('Update error: ' . $e->getMessage());
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
