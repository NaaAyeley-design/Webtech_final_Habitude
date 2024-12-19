<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../../db/config.php';

// Temporary for testing
$_SESSION['user_id'] = 1;

// Handle AJAX requests (GET and POST)
if (isset($_GET['action']) || isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Handle GET requests
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
        if ($_GET['action'] === 'get_boards') {
            try {
                $userId = $_SESSION['user_id'];
                $sql = "SELECT * FROM vision_boards WHERE user_id = ? ORDER BY created_at DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $boards = [];

                while ($board = $result->fetch_assoc()) {
                    // Get images for this board
                    $imagesSql = "SELECT * FROM board_images WHERE board_id = ?";
                    $imagesStmt = $conn->prepare($imagesSql);
                    $imagesStmt->bind_param("i", $board['board_id']);
                    $imagesStmt->execute();
                    $imagesResult = $imagesStmt->get_result();
                    
                    $board['images'] = [];
                    while ($image = $imagesResult->fetch_assoc()) {
                        $board['images'][] = $image;
                    }
                    
                    $boards[] = $board;
                }
                
                echo json_encode(['success' => true, 'boards' => $boards]);
                exit;
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
        }
    }

    // Handle POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $response = ['success' => false, 'error' => ''];
        
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
                    
                    $sql = "INSERT INTO vision_boards (user_id, title, description) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iss", $_SESSION['user_id'], $title, $description);
                    
                    if ($stmt->execute()) {
                        $boardId = $conn->insert_id;
                        $response['success'] = true;
                        $response['board_id'] = $boardId;
                        
                        // Handle image uploads
                        if (isset($_FILES['images'])) {
                            $uploadDir = 'uploads/vision_boards/';
                            if (!file_exists($uploadDir)) {
                                mkdir($uploadDir, 0777, true);
                            }
                            
                            $uploadedImages = [];
                            $imageData = [];
                            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                                $fileName = uniqid() . '_' . $_FILES['images']['name'][$key];
                                $targetPath = $uploadDir . $fileName;
                                
                                if (move_uploaded_file($tmp_name, $targetPath)) {
                                    // Save image info to database
                                    $sql = "INSERT INTO board_images (board_id, image_path, image_name) VALUES (?, ?, ?)";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("iss", $boardId, $targetPath, $fileName);
                                    if ($stmt->execute()) {
                                        $imageId = $conn->insert_id;
                                        $imageData[] = [
                                            'image_id' => $imageId,
                                            'image_path' => $targetPath,
                                            'image_name' => $fileName
                                        ];
                                    }
                                    $uploadedImages[] = $fileName;
                                }
                            }
                            $response['uploaded_images'] = $imageData;
                        }
                    } else {
                        $response['error'] = 'Failed to create board';
                    }
                } catch (Exception $e) {
                    $response['error'] = 'Error: ' . $e->getMessage();
                }
                echo json_encode($response);
                break;    
            case 'delete_image':
                // Your existing delete_image code
                break;
        
            case 'delete_board':
                try {
                    $boardId = $_POST['board_id'] ?? 0;
                    $userId = $_SESSION['user_id'];
        
                    // First, delete all images associated with this board
                    $imagesSql = "SELECT image_path FROM board_images WHERE board_id = ?";
                    $imagesStmt = $conn->prepare($imagesSql);
                    $imagesStmt->bind_param("i", $boardId);
                    $imagesStmt->execute();
                    $imagesResult = $imagesStmt->get_result();
        
                    // Delete physical image files
                    while ($image = $imagesResult->fetch_assoc()) {
                        if (file_exists($image['image_path'])) {
                            unlink($image['image_path']);
                        }
                    }
        
                    // Delete image records from database
                    $deleteImagesSql = "DELETE FROM board_images WHERE board_id = ?";
                    $deleteImagesStmt = $conn->prepare($deleteImagesSql);
                    $deleteImagesStmt->bind_param("i", $boardId);
                    $deleteImagesStmt->execute();
        
                    // Delete the board itself
                    $deleteBoardSql = "DELETE FROM vision_boards WHERE board_id = ? AND user_id = ?";
                    $deleteBoardStmt = $conn->prepare($deleteBoardSql);
                    $deleteBoardStmt->bind_param("ii", $boardId, $userId);
                    
                    if ($deleteBoardStmt->execute()) {
                        $response['success'] = true;
                    } else {
                        $response['error'] = 'Failed to delete board';
                    }
                } catch (Exception $e) {
                    $response['error'] = 'Error: ' . $e->getMessage();
                }
                echo json_encode($response);
                break;
        }
        exit;
    }
}

// Only output HTML if it's not an AJAX request
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habitude Vision Board</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide-static/0.321.0/lucide.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --secondary: #818CF8;
            --accent: #C7D2FE;
            --background: #F5F7FF;
            --surface: #FFFFFF;
            --text-primary: #1F2937;
            --text-secondary: #6B7280;
            --success: #34D399;
            --error: #EF4444;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            --gradient-accent: linear-gradient(135deg, #C7D2FE 0%, #93C5FD 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: var(--surface);
            padding: 2rem;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(0, 0, 0, 0.05);
            height: 100vh;
            position: fixed;
        }

        .sidebar-logo h1 {
            font-size: 2rem;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 2.5rem;
            letter-spacing: -0.5px;
        }

        .nav-link {
            padding: 0.875rem 1.25rem;
            margin: 0.5rem 0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            text-decoration: none;
            font-weight: 500;
        }

        .nav-link:hover {
            background: var(--gradient-primary);
            color: white;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .nav-link i {
            margin-right: 12px;
            font-size: 1.25rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            width: calc(100% - 280px);
        }

        /* Action Bar */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .action-bar h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Board Grid */
        .boards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 1rem 0;
        }

        .board-card {
            background: var(--surface);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease forwards;
        }

        .board-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .board-card.expanded {
            grid-column: 1 / -1;
        }

        .board-card-content {
            padding: 1.5rem;
        }

        .board-card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .board-card-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        /* Image Grid */
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .image-container {
            position: relative;
            aspect-ratio: 1;
            overflow: hidden;
            border-radius: 8px;
        }

        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-container .delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .image-container:hover .delete-btn {
            opacity: 1;
        }

        .add-images-btn {
            margin-top: 1rem;
            width: 100%;
            padding: 0.5rem;
            background: var(--gradient-accent);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            color: var(--primary);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .add-images-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        /* Form positioning at bottom of nav */
        .sidebar form {
            margin-top: auto !important; /* Override inline style */
            margin-bottom: 2rem;
            width: 100%;
        }

        /* Purple Gradient Logout Button */
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            padding: 0.875rem 1.25rem;
            background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .logout-btn i {
            font-size: 1.25rem;
            transition: transform 0.3s ease;
        }

        .logout-btn span {
            font-weight: 500;
        }

        .logout-btn:hover {
            background: linear-gradient(135deg, #7C3AED 0%, #5B21B6 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(139, 92, 246, 0.2);
        }

        .logout-btn:active {
            transform: translateY(0);
        }

        /* Hover effect for icon */
        .logout-btn:hover i {
            transform: translateX(3px);
        }

        /* Focus state for accessibility */
        .logout-btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.4);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar form {
                margin-bottom: 1rem;
            }
            
            .logout-btn {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
            
            .logout-btn i {
                font-size: 1.1rem;
            }
        }
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: var(--surface);
            padding: 2rem;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow-lg);
            position: relative;
        }

        .close-btn {
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            color: var(--error);
            transform: rotate(90deg);
        }

        /* Form Styles */
        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .input-group label {
            font-weight: 500;
            color: var(--text-primary);
        }

        input[type="text"],
        textarea {
            padding: 1rem;
            border: 2px solid var(--accent);
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 1rem;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }

            .boards-grid {
                grid-template-columns: 1fr;
            }

            .action-bar {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
        
        /* Additional Button Styles */
        .btn {
            padding: 0.875rem 1.5rem;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .board-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.delete-board-btn {
    background: var(--error);
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    transition: all 0.3s ease;
}

.delete-board-btn:hover {
    transform: scale(1.1);
    background: #dc2626;
}
    </style>
</head>
<body>
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
        <a href="1timer.php" class="nav-link">
            <i data-lucide="timer"></i> Timer
        </a>
        <a href="1visionboard.php" class="nav-link active">
            <i data-lucide="target"></i> Vision Board
        </a>
        <form action="logout_user.php" method="POST" style="margin-top: 10px;">
    <button type="submit" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
</form>

    </nav>

    <main class="main-content">
        <div class="action-bar">
            <h2>My Vision Boards</h2>
            <button class="btn" id="createBoardBtn">Create New Board</button>
        </div>

        <div class="boards-grid" id="boardsContainer">
            <!-- Boards will be dynamically added here -->
        </div>

        <!-- Create Board Modal -->
        <div class="modal" id="createBoardModal">
            <div class="modal-content">
                <button class="close-btn">&times;</button>
                <h2>Create New Vision Board</h2>
                <form class="upload-form" id="createBoardForm">
                    <div class="input-group">
                        <label for="boardTitle">Board Title</label>
                        <input type="text" id="boardTitle" required>
                    </div>
                    <div class="input-group">
                        <label for="boardDescription">Description</label>
                        <textarea id="boardDescription" rows="3"></textarea>
                    </div>
                    <div class="input-group">
                        <label for="boardImages">Upload Images</label>
                        <input type="file" id="boardImages" multiple accept="image/*">
                    </div>
                    <button type="submit" class="btn">Create Board</button>
                </form>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        // DOM Elements
const createBoardBtn = document.getElementById('createBoardBtn');
const createBoardModal = document.getElementById('createBoardModal');
const closeBtn = document.querySelector('.close-btn');
const createBoardForm = document.getElementById('createBoardForm');
const boardsContainer = document.getElementById('boardsContainer');

class VisionBoard {
    constructor(title, description, images = [], boardId = null) {
        this.title = title;
        this.description = description;
        this.images = images;
        this.boardId = boardId;
        this.element = this.createBoardElement();
    }

    createBoardElement() {
        const board = document.createElement('div');
        board.className = 'board-card';
        board.dataset.boardId = this.boardId;
        board.innerHTML = `
            <div class="board-card-content">
                <div class="board-header">
                    <h3 class="board-card-title">${this.title}</h3>
                    <button class="delete-board-btn" title="Delete Board">×</button>
                </div>
                <p class="board-card-description">${this.description}</p>
                <div class="image-grid"></div>
                <button class="add-images-btn">Add More Images</button>
            </div>
        `;

        this.setupEventListeners(board);
        this.renderImages(board);
        return board;
    }

    setupEventListeners(board) {
        const addButton = board.querySelector('.add-images-btn');
        const deleteButton = board.querySelector('.delete-board-btn');
        
        addButton.addEventListener('click', (e) => {
            e.stopPropagation();
            this.handleAddImages();
        });

        deleteButton.addEventListener('click', async (e) => {
            e.stopPropagation();
            if (confirm('Are you sure you want to delete this board? This action cannot be undone.')) {
                await this.deleteBoard();
            }
        });

        board.addEventListener('click', () => {
            board.classList.toggle('expanded');
        });
    }

    renderImages(board = this.element) {
        const grid = board.querySelector('.image-grid');
        grid.innerHTML = '';
        
        this.images.forEach((image, index) => {
            const container = document.createElement('div');
            container.className = 'image-container';
            
            const img = document.createElement('img');
            // Handle both file objects and server-returned image paths
            if (image.image_path) {
                img.src = image.image_path;
                container.dataset.imageId = image.image_id;
            } else if (typeof image === 'string') {
                img.src = image;
            } else {
                img.src = URL.createObjectURL(image);
            }
            
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'delete-btn';
            deleteBtn.innerHTML = '×';
            deleteBtn.addEventListener('click', async (e) => {
                e.stopPropagation();
                if (container.dataset.imageId) {
                    await this.deleteImageFromServer(container.dataset.imageId);
                }
                this.deleteImage(index);
            });

            container.appendChild(img);
            container.appendChild(deleteBtn);
            grid.appendChild(container);
        });
    }

    async handleAddImages() {
        const input = document.createElement('input');
        input.type = 'file';
        input.multiple = true;
        input.accept = 'image/*';

        input.onchange = async (e) => {
            const files = Array.from(e.target.files);
            
            if (this.boardId) {
                const formData = new FormData();
                formData.append('action', 'add_images');
                formData.append('board_id', this.boardId);
                
                files.forEach(file => {
                    formData.append('images[]', file);
                });

                try {
                    const response = await fetch('1visionboard.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        // Instead of reloading the page, fetch and update only this board
                        await this.refreshBoardImages();
                        return;
                    }
                } catch (error) {
                    console.error('Error uploading images:', error);
                }
            }

            // Fallback for new boards
            this.images.push(...files);
            this.renderImages();
        };

        input.click();
    }

    async deleteBoard() {
        if (!this.boardId) return;

        try {
            const formData = new FormData();
            formData.append('action', 'delete_board');
            formData.append('board_id', this.boardId);

            const response = await fetch('1visionboard.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                this.element.remove();
            } else {
                alert('Failed to delete board. Please try again.');
            }
        } catch (error) {
            console.error('Error deleting board:', error);
            alert('Error deleting board. Please try again.');
        }
    }

    async refreshBoardImages() {
        try {
            const response = await fetch(`1visionboard.php?action=get_boards`);
            const data = await response.json();
            const updatedBoard = data.boards.find(board => board.board_id === this.boardId);
            if (updatedBoard) {
                this.images = updatedBoard.images;
                this.renderImages();
            }
        } catch (error) {
            console.error('Error refreshing board images:', error);
        }
    }

    async deleteImageFromServer(imageId) {
        const formData = new FormData();
        formData.append('action', 'delete_image');
        formData.append('image_id', imageId);
        
        try {
            const response = await fetch('1visionboard.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            return data.success;
        } catch (error) {
            console.error('Error deleting image:', error);
            return false;
        }
    }

    deleteImage(index) {
        this.images.splice(index, 1);
        this.renderImages();
    }
}

// Event Listeners for Modal
createBoardBtn.addEventListener('click', () => {
    createBoardModal.style.display = 'flex';
});

closeBtn.addEventListener('click', () => {
    createBoardModal.style.display = 'none';
});

// Close modal if clicking outside
window.addEventListener('click', (e) => {
    if (e.target === createBoardModal) {
        createBoardModal.style.display = 'none';
    }
});

// Load boards from server
async function loadUserBoards() {
    try {
        const response = await fetch('1visionboard.php?action=get_boards');
        const data = await response.json();
        
        if (data.boards) {
            boardsContainer.innerHTML = '';
            data.boards.forEach(boardData => {
                const board = new VisionBoard(
                    boardData.title,
                    boardData.description,
                    boardData.images || [], // Ensure images is always an array
                    boardData.board_id
                );
                boardsContainer.appendChild(board.element);
            });
        }
    } catch (error) {
        console.error('Error loading boards:', error);
    }
}

// Form submission handler
// Form submission handler
createBoardForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData();
    formData.append('action', 'create_board');
    formData.append('title', document.getElementById('boardTitle').value);
    formData.append('description', document.getElementById('boardDescription').value);
    
    const imageFiles = document.getElementById('boardImages').files;
    for (let i = 0; i < imageFiles.length; i++) {
        formData.append('images[]', imageFiles[i]);
    }
    
    try {
        const response = await fetch('1visionboard.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            // Instead of reloading all boards, just add the new one
            const newBoard = new VisionBoard(
                document.getElementById('boardTitle').value,
                document.getElementById('boardDescription').value,
                data.uploaded_images ? data.uploaded_images.map(filename => ({
                    image_path: `../../uploads/vision_boards/${filename}`,
                    image_id: null // The server should return image IDs in the response
                })) : [],
                data.board_id
            );
            
            // Add the new board to the beginning of the container
            if (boardsContainer.firstChild) {
                boardsContainer.insertBefore(newBoard.element, boardsContainer.firstChild);
            } else {
                boardsContainer.appendChild(newBoard.element);
            }
        } else {
            alert('Error creating board. Please try again.');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error creating board. Please try again.');
    }
    
    createBoardForm.reset();
    createBoardModal.style.display = 'none';
});

// Initialize lucide icons and load boards when DOM is ready
window.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    loadUserBoards();
});
</script>
</body>
</html>