<?php
// get_journal.php
require_once '../../db/config.php';
session_start();

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $entry_id = $_GET['id'];
    
    $query = "SELECT * FROM journal_entries WHERE entry_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $entry_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $entry = $result->fetch_assoc();
    
    if ($entry) {
        echo json_encode(['success' => true, 'entry' => $entry]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Entry not found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No ID provided']);
}