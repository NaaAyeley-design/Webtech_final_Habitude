<?php
require_once '../../db/config.php';
session_start();

// Ensure we're sending JSON responses
header('Content-Type: application/json');

// Error handling function
function sendJsonResponse($success, $data = [], $error = '') {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error
    ]);
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] === 'get_all_data') {
        try {
            // ... your existing get_all_data code ...
        } catch (Exception $e) {
            sendJsonResponse(false, [], 'Database error: ' . $e->getMessage());
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_preference'])) {
            try {
                $preference_id = $_POST['edit_preference_id'];
                $default_mode = $_POST['default_mode'];
                $default_duration = $_POST['default_duration'] * 60;
                $sound_enabled = $_POST['sound_enabled'];
                $last_meditation_mode = $_POST['last_meditation_mode'];
        
                $updateStmt = $conn->prepare("UPDATE timer_preferences 
                                            SET default_mode = ?, 
                                                default_duration = ?, 
                                                sound_enabled = ?, 
                                                last_meditation_mode = ? 
                                            WHERE preference_id = ?");
                
                if (!$updateStmt) {
                    throw new Exception($conn->error);
                }
        
                $updateStmt->bind_param("siisi", $default_mode, $default_duration, 
                                      $sound_enabled, $last_meditation_mode, $preference_id);
                
                if ($updateStmt->execute()) {
                    sendJsonResponse(true, ['message' => 'Preference updated successfully']);
                } else {
                    throw new Exception($updateStmt->error);
                }
            } catch (Exception $e) {
                sendJsonResponse(false, [], 'Error updating preference: ' . $e->getMessage());
            }
        }

        if (isset($_POST['delete_preference'])) {
            try {
                $preference_id = $_POST['delete_preference_id'];
                error_log("Received preference_id: " . $preference_id);
                
                // Direct delete without checking for sessions
                $deleteStmt = $conn->prepare("DELETE FROM timer_preferences WHERE preference_id = ?");
                if (!$deleteStmt) {
                    throw new Exception($conn->error);
                }
        
                error_log("Prepared statement created");
        
                $deleteStmt->bind_param("i", $preference_id);
                
                error_log("Parameters bound");
                
                if ($deleteStmt->execute()) {
                    error_log("Delete executed successfully");
                    sendJsonResponse(true, ['message' => 'Preference deleted successfully']);
                } else {
                    error_log("Delete execution failed");
                    throw new Exception($deleteStmt->error);
                }
            } catch (Exception $e) {
                error_log("Caught exception: " . $e->getMessage());
                sendJsonResponse(false, [], 'Error: ' . $e->getMessage());
            }
        }
        if (isset($_POST['add_preference'])) {
            $user_id = $_POST['user_id'];
            $default_mode = $_POST['default_mode'];
            $default_duration = $_POST['default_duration'] * 60;
            $sound_enabled = $_POST['sound_enabled'];
            $last_meditation_mode = $_POST['last_meditation_mode'];

            $insertStmt = $conn->prepare("INSERT INTO timer_preferences 
                                        (user_id, default_mode, default_duration, sound_enabled, last_meditation_mode) 
                                        VALUES (?, ?, ?, ?, ?)");
            
            if (!$insertStmt) {
                throw new Exception($conn->error);
            }

            $insertStmt->bind_param("isiis", $user_id, $default_mode, $default_duration, 
                                  $sound_enabled, $last_meditation_mode);
            
            if ($insertStmt->execute()) {
                sendJsonResponse(true, ['message' => 'Add successful']);
            } else {
                throw new Exception($insertStmt->error);
            }
        }
    } catch (Exception $e) {
        sendJsonResponse(false, [], $e->getMessage());
    }
}
?>