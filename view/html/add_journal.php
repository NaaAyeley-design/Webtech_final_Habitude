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

        $stmt = $conn->prepare("INSERT INTO journal_entries (user_id, content, mood, tags, is_favorite) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("isssi", $user_id, $content, $mood, $tags, $is_favorite);
        
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