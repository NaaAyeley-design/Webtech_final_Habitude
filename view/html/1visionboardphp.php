<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../../db/config.php';

// Temporary for testing
$_SESSION['user_id'] = 1;

// Function to get all vision boards for a user
function getUserBoards($userId) {
    global $conn;
    $sql = "SELECT * FROM vision_boards WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get images for a specific board
function getBoardImages($boardId) {
    global $conn;
    $sql = "SELECT * FROM board_images WHERE board_id = ? ORDER BY upload_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $boardId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to create a new vision board
function createVisionBoard($userId, $title, $description) {
    global $conn;
    $sql = "INSERT INTO vision_boards (user_id, title, description) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $userId, $title, $description);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    return false;
}

// Function to upload images for a board
function uploadBoardImage($boardId, $imageFile) {
    global $conn;
    
    $uploadDir = 'uploads/vision_boards/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileName = uniqid() . '_' . basename($imageFile['name']);
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($imageFile['tmp_name'], $targetPath)) {
        $sql = "INSERT INTO board_images (board_id, image_path, image_name) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $boardId, $targetPath, $fileName);
        return $stmt->execute();
    }
    return false;
}

// Function to delete an image
function deleteImage($imageId, $userId) {
    global $conn;
    
    // First verify that the image belongs to the user's board
    $sql = "SELECT bi.image_path FROM board_images bi 
            JOIN vision_boards vb ON bi.board_id = vb.board_id 
            WHERE bi.image_id = ? AND vb.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $imageId, $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        // Delete the physical file
        if (file_exists($result['image_path'])) {
            unlink($result['image_path']);
        }
        
        // Delete from database
        $sql = "DELETE FROM board_images WHERE image_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $imageId);
        return $stmt->execute();
    }
    return false;
}

// Handle GET requests for loading boards
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'get_boards') {
        try {
            $userId = $_SESSION['user_id'];
            $boards = getUserBoards($userId);
            
            // Get images for each board
            foreach ($boards as &$board) {
                $board['images'] = getBoardImages($board['board_id']);
            }
            
            echo json_encode(['success' => true, 'boards' => $boards]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_board':
            try {
                $title = $_POST['title'] ?? '';
                $description = $_POST['description'] ?? '';
                $boardId = createVisionBoard($_SESSION['user_id'], $title, $description);
                
                if ($boardId && isset($_FILES['images'])) {
                    $images = $_FILES['images'];
                    $uploadedImages = [];
                    
                    for ($i = 0; $i < count($images['name']); $i++) {
                        $image = [
                            'name' => $images['name'][$i],
                            'type' => $images['type'][$i],
                            'tmp_name' => $images['tmp_name'][$i],
                            'error' => $images['error'][$i],
                            'size' => $images['size'][$i]
                        ];
                        
                        if (uploadBoardImage($boardId, $image)) {
                            $uploadedImages[] = $image['name'];
                        }
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'board_id' => $boardId,
                        'uploaded_images' => $uploadedImages
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Failed to create board or upload images'
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Error creating board: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'delete_image':
            try {
                $imageId = $_POST['image_id'] ?? 0;
                $success = deleteImage($imageId, $_SESSION['user_id']);
                echo json_encode(['success' => $success]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Error deleting image: ' . $e->getMessage()
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
    }
    exit;
}

// Only include HTML content if it's not an AJAX request
if (!isset($_GET['action']) && !isset($_POST['action'])) {
    // Your existing HTML content goes here
?>
<!DOCTYPE html>
<html?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habitude Timer</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide-static/0.321.0/lucide.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/1timercss.css">
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <div class="sidebar-logo">
                <h1>Habitude</h1>
            </div>
            <a href="1dashboard.php" class="nav-link">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            <a href="1Journal.php" class="nav-link">
                <i data-lucide="book-open"></i> Journal
            </a>
            <a href="1visionboard.php" class="nav-link">
                <i data-lucide="target"></i> Vision Board
            </a>
            <a href="1timer.php" class="nav-link active">
                <i data-lucide="timer"></i> Timer
            </a>
        </nav>

        <main class="main-content">
            <section class="timer-section">
                <div class="timer-container">
                    <div class="timer-display" id="timerDisplay">25:00</div>
                    <div class="custom-time-input">
                        <input type="number" id="customMinutes" min="1" max="180" placeholder="Minutes">
                        <button class="btn btn-secondary" id="setCustomTime">Set Time</button>
                    </div>
                    <div class="timer-controls">
                        <button class="btn" id="startBtn">
                            <i data-lucide="play"></i> Start
                        </button>
                        <button class="btn btn-secondary" id="pauseBtn" disabled>
                            <i data-lucide="pause"></i> Pause
                        </button>
                        <button class="btn" id="resetBtn">
                            <i data-lucide="refresh-cw"></i> Reset
                        </button>
                    </div>
                    <div class="timer-presets">
                        <button class="preset-btn active" data-time="25">Pomodoro</button>
                        <button class="preset-btn" data-time="15">Short Break</button>
                        <button class="preset-btn" data-time="45">Long Break</button>
                    </div>
                </div>
            </section>

            <section class="meditation-section">
                <h2>Meditation Modes</h2>
                <div class="meditation-modes">
                    <div class="meditation-mode" data-mode="breathing">
                        <div class="mode-icon">
                            <i data-lucide="wind"></i>
                        </div>
                        <h3>Breathing</h3>
                        <p>Focus on deep, rhythmic breathing</p>
                    </div>
                    <div class="meditation-mode" data-mode="mindfulness">
                        <div class="mode-icon">
                            <i data-lucide="brain"></i>
                        </div>
                        <h3>Mindfulness</h3>
                        <p>Present moment awareness</p>
                    </div>
                    <div class="meditation-mode" data-mode="body-scan">
                        <div class="mode-icon">
                            <i data-lucide="activity"></i>
                        </div>
                        <h3>Body Scan</h3>
                        <p>Systematic body relaxation</p>
                    </div>
                    <div class="meditation-mode" data-mode="loving-kindness">
                        <div class="mode-icon">
                            <i data-lucide="heart"></i>
                        </div>
                        <h3>Loving-Kindness</h3>
                        <p>Cultivate compassion</p>
                    </div>
                </div>
            </section>

            <section class="log-section">
                <h2>Session Log</h2>
                <div class="session-log" id="sessionLog">
                    <!-- Session history will be logged here -->
                </div>
            </section>
        </main>
    </div>

    <!-- Audio for alarm -->
    <audio id="alarmSound" preload="auto">
        <source src="https://actions.google.com/sounds/v1/alarms/alarm_clock.ogg" type="audio/ogg">
        <source src="https://actions.google.com/sounds/v1/alarms/beeping_alarm_clock.ogg" type="audio/ogg">
    </audio>
</html>
<?php
}
?>