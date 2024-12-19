<?php
header('Content-Type: application/json');
require_once "../db/config.php";

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once '../db/config.php';
session_start();

// Detailed error logging function
function logError($message) {
    $logFile = '../error_log.txt';
    $timestamp = date('[Y-m-d H:i:s]');
    file_put_contents($logFile, $timestamp . ' ' . $message . PHP_EOL, FILE_APPEND);
}

// Check database connection
if (!$conn) {
    logError("MySQLi Connection Failed: " . mysqli_connect_error());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection error'
    ]);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Handle OPTIONS request for CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit();
}

// Get raw POST data and log it for debugging
$rawInput = file_get_contents('php://input');
logError("Raw Input: " . $rawInput);

// Parse JSON input
$input = json_decode($rawInput, true);

// Check JSON parsing errors
if (json_last_error() !== JSON_ERROR_NONE) {
    logError("JSON Parsing Error: " . json_last_error_msg());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit();
}

// Validate input
$entry_id = $input['entry_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (empty($entry_id)) {
    logError("Invalid Entry ID: " . print_r($input, true));
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid entry ID']);
    exit();
}

// Start a transaction
$conn->begin_transaction();

try {
    // First, delete any related entries in journal_entry_tags
    $tagDeleteStmt = $conn->prepare("DELETE FROM journal_entry_tags WHERE entry_id = ?");
    $tagDeleteStmt->bind_param("i", $entry_id);
    $tagDeleteStmt->execute();

    // Check if the entry exists and belongs to the user
    $checkStmt = $conn->prepare("SELECT * FROM journal_entries WHERE entry_id = ? AND user_id = ?");
    $checkStmt->bind_param("ii", $entry_id, $user_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        $conn->rollback();
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'Entry not found or unauthorized'
        ]);
        exit();
    }

    // Delete the journal entry
    $deleteStmt = $conn->prepare("DELETE FROM journal_entries WHERE entry_id = ? AND user_id = ?");
    $deleteStmt->bind_param("ii", $entry_id, $user_id);
    $deleteResult = $deleteStmt->execute();

    if ($deleteResult) {
        $conn->commit();
        logError("Entry $entry_id deleted successfully");
        echo json_encode([
            'success' => true, 
            'message' => 'Journal entry deleted successfully'
        ]);
    } else {
        $conn->rollback();
        logError("Deletion failed for entry $entry_id");
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Deletion failed'
        ]);
    }
} catch (Exception $e) {
    $conn->rollback();
    logError("Unexpected Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Unexpected error occurred: ' . $e->getMessage()
    ]);
} finally {
    // Close statements and connection
    if (isset($tagDeleteStmt)) $tagDeleteStmt->close();
    if (isset($checkStmt)) $checkStmt->close();
    if (isset($deleteStmt)) $deleteStmt->close();
    $conn->close();
}

exit();