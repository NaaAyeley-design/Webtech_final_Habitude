<?php
// At the very top of delete_user.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set up error logging
ini_set('log_errors', 1);
error_log("Starting delete_user.php");

// Ensure we're sending JSON response
header('Content-Type: application/json');

// Include required files
require_once 'timerphp.php';

try {
    error_log("Starting session");
    SessionManager::start();

    // Check authentication
    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 2) {
        throw new Exception('Unauthorized access');
    }

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate user ID
    if (!isset($_GET['id'])) {
        throw new Exception('No user ID provided');
    }

    $userId = (int)$_GET['id'];
    
    // Prevent deleting the admin user
    if ($userId == 2) {
        throw new Exception('Cannot delete admin user');
    }

    error_log("Getting database connection");
    $db = Database::getInstance();
    
    if (!$db) {
        error_log("Database connection failed");
        throw new Exception('Database connection failed');
    }

    error_log("Database connected successfully");
    error_log("Attempting to delete user ID: " . $userId);
    
    // Start transaction
    $db->begin_transaction();
    error_log("Transaction started");

    try {
        // First, handle board_images through vision_boards
        error_log("Deleting board_images");
        $sql = "DELETE bi FROM board_images bi 
                INNER JOIN vision_boards vb ON bi.board_id = vb.board_id 
                WHERE vb.user_id = ?";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed for board_images: " . $db->error);
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        // Handle journal_entry_tags through journal_entries
        error_log("Deleting journal_entry_tags");
        $sql = "DELETE jet FROM journal_entry_tags jet 
                INNER JOIN journal_entries je ON jet.entry_id = je.entry_id 
                WHERE je.user_id = ?";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed for journal_entry_tags: " . $db->error);
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        // Define tables and their user ID column names in deletion order
        $tables = [
            'timer_preferences' => 'user_id',
            'timer_sessions' => 'user_id',
            'journal_tags' => 'user_id',
            'journal_entries' => 'user_id',
            'vision_boards' => 'user_id',
            'user_profiles' => 'user_id',
            'users' => 'user_id'
        ];

        // Delete from each table
        foreach ($tables as $table => $idColumn) {
            error_log("Deleting from table: $table");
            $sql = "DELETE FROM $table WHERE $idColumn = ?";
            $stmt = $db->prepare($sql);
            
            if (!$stmt) {
                error_log("Prepare failed for $table: " . $db->error);
                throw new Exception("Prepare failed for table $table: " . $db->error);
            }
            
            $stmt->bind_param('i', $userId);
            
            if (!$stmt->execute()) {
                error_log("Execute failed for $table: " . $stmt->error);
                throw new Exception("Delete failed for table $table: " . $stmt->error);
            }
            
            $stmt->close();
            error_log("Successfully deleted from $table");
        }

        // Commit the transaction
        $db->commit();
        error_log("Transaction committed successfully");
        
        echo json_encode([
            'success' => true,
            'message' => 'User and all related data successfully deleted'
        ]);

    } catch (Exception $e) {
        error_log("Error during deletion: " . $e->getMessage());
        $db->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Final error catch: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>