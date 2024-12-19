<?php
// add_board_ajax.php
require_once '../../db/config.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Log incoming data
        error_log("Received POST request: " . print_r($_POST, true));
        error_log("Received FILES: " . print_r($_FILES, true));

        $user_id = $_POST['user_id'];
        $title = $_POST['title'];
        $description = $_POST['description'] ?? '';

        // Start transaction
        $conn->begin_transaction();

        // Insert vision board
        $stmt = $conn->prepare("INSERT INTO vision_boards (user_id, title, description) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("iss", $user_id, $title, $description);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating vision board: " . $conn->error);
        }

        $board_id = $conn->insert_id;
        error_log("Created board with ID: " . $board_id);

        // Handle image uploads
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $upload_dir = '../uploads/vision_boards/';
            
            // Create upload directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception("Failed to create upload directory");
                }
            }

            // Process each uploaded file
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    // Generate unique filename
                    $file_extension = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                    $file_name = uniqid() . '_' . time() . '.' . $file_extension;
                    $file_path = $upload_dir . $file_name;

                    error_log("Attempting to move file to: " . $file_path);

                    // Move uploaded file
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        // Insert image record into database
                        $img_stmt = $conn->prepare("INSERT INTO board_images (board_id, image_path) VALUES (?, ?)");
                        if (!$img_stmt) {
                            throw new Exception("Prepare failed for image insertion: " . $conn->error);
                        }

                        $img_stmt->bind_param("is", $board_id, $file_path);
                        if (!$img_stmt->execute()) {
                            throw new Exception("Error saving image record: " . $conn->error);
                        }
                        error_log("Saved image record for file: " . $file_path);
                    } else {
                        throw new Exception("Failed to move uploaded file");
                    }
                }
            }
        }

        // Commit transaction
        $conn->commit();
        echo json_encode(['success' => true]);
        error_log("Successfully completed vision board creation");

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error in add_board_ajax.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}