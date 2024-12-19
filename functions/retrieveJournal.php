<?php
header('Content-Type: application/json');
require_once "../db/config.php";
session_start();



try {
    // Receive JSON payload
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Validate user ID
    if (!isset($data['user_id']) || empty($data['user_id'])) {
        throw new Exception('Invalid or missing user ID');
    }

    // Connect to database
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare and execute query
    $stmt = $pdo->prepare("SELECT entry_id, content, mood, tags, created_at 
                            FROM journal_entries 
                            WHERE user_id = ? 
                            ORDER BY created_at DESC");
    $stmt->execute([$data['user_id']]);

    // Fetch entries
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process tags (they are stored as text, so split if needed)
    $entries = array_map(function($entry) {
        $entry['tags'] = $entry['tags'] ? explode(',', $entry['tags']) : [];
        return $entry;
    }, $entries);

    // Return successful response
    echo json_encode([
        'success' => true, 
        'entries' => $entries
    ]);

} catch (Exception $e) {
    // Log the full error for debugging
    error_log('Journal Retrieval Error: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to retrieve journal entries: ' . $e->getMessage()
    ]);
}