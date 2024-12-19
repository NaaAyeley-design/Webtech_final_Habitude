<?php
// add_journal_ajax.php
require_once '../../db/config.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = $_POST['user_id'];
        $content = $_POST['content'];
        $mood = $_POST['mood'];
        $tags = $_POST['tags'] ?? '';
        $is_favorite = isset($_POST['is_favorite']) ? 1 : 0;
        
        if (isset($_POST['entry_id'])) {
            // Update existing entry
            $entry_id = $_POST['entry_id'];
            $stmt = $conn->prepare("UPDATE journal_entries SET user_id = ?, content = ?, mood = ?, tags = ?, is_favorite = ? WHERE entry_id = ?");
            $stmt->bind_param("isssii", $user_id, $content, $mood, $tags, $is_favorite, $entry_id);
        } else {
            // Create new entry
            $stmt = $conn->prepare("INSERT INTO journal_entries (user_id, content, mood, tags, is_favorite) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $user_id, $content, $mood, $tags, $is_favorite);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

// Make sure there's no extra output
exit;