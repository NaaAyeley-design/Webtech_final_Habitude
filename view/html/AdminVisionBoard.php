<?php
// vision-boards.php
require_once '../../db/config.php';
session_start();

// Handle vision board deletion
if (isset($_POST['delete_board'])) {
    $board_id = $_POST['board_id'];
    
    // First delete associated images (both from database and file system)
    $stmt = $conn->prepare("SELECT image_path FROM board_images WHERE board_id = ?");
    $stmt->bind_param("i", $board_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($image = $result->fetch_assoc()) {
        if (file_exists($image['image_path'])) {
            unlink($image['image_path']);
        }
    }
    
    // Delete the vision board
    $stmt = $conn->prepare("DELETE FROM vision_boards WHERE board_id = ?");
    $stmt->bind_param("i", $board_id);
    $stmt->execute();
}

// Get all vision boards with user information and image count
$query = "SELECT vb.*, up.first_name, up.last_name, 
          (SELECT COUNT(*) FROM board_images bi WHERE bi.board_id = vb.board_id) as image_count 
          FROM vision_boards vb 
          LEFT JOIN users u ON vb.user_id = u.user_id 
          LEFT JOIN user_profiles up ON u.user_id = up.user_id 
          ORDER BY vb.created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vision Board Management - Habitude Admin</title>
    <style>
        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 250px;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 4px;
        }

        .btn-view {
            background-color: #3b82f6;
            color: white;
        }

        .btn-delete {
            background-color: #ef4444;
            color: white;
        }

        .image-count {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .image-icon {
            width: 20px;
            height: 20px;
        }

        .add-user-btn {
            background-color: #10b981;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .add-user-btn:hover {
            background-color: #059669;
        }

        /* Add these to your existing styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border-radius: 8px;
    width: 80%;
    max-width: 800px;
    position: relative;
    max-height: 90vh;
    overflow-y: auto;
}

.close {
    position: absolute;
    right: 20px;
    top: 10px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #666;
}

.close:hover {
    color: #333;
}

/* Form styles */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #374151;
}

.form-group input[type="text"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.form-group textarea {
    height: 120px;
    resize: vertical;
}

.form-group select {
    background-color: white;
}

.image-upload-container {
    border: 2px dashed #d1d5db;
    padding: 20px;
    text-align: center;
    border-radius: 6px;
    margin-bottom: 20px;
    background-color: #f9fafb;
}

.image-upload-container:hover {
    border-color: #3b82f6;
    background-color: #f3f4f6;
}

.image-preview {
    max-width: 200px;
    max-height: 200px;
    margin-top: 10px;
    display: none;
}

.upload-icon {
    width: 40px;
    height: 40px;
    margin-bottom: 10px;
    color: #6b7280;
}

.upload-text {
    color: #6b7280;
    margin-bottom: 10px;
}

.file-input {
    display: none;
}

.select-files-btn {
    background-color: #3b82f6;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    display: inline-block;
    font-size: 14px;
}

.select-files-btn:hover {
    background-color: #2563eb;
}

    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="header">
            <h1>Vision Boards Management</h1>
            <div class="header-controls">
                <button onclick="openModal()" class="add-user-btn">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Add Vision Board
            </button>
            <input type="text" id="searchInput" placeholder="Search vision boards..." class="search-input">
        </div>
    </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Created At</th>
                        <th>Images</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($board = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($board['board_id']); ?></td>
                            <td><?php echo htmlspecialchars($board['first_name'] . ' ' . $board['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($board['title']); ?></td>
                            <td><?php echo htmlspecialchars($board['description']) ?: '-'; ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($board['created_at'])); ?></td>
                            <td>
                                <div class="image-count">
                                    <img src="images/picture-icon.svg" alt="Images" class="image-icon">
                                    <?php echo $board['image_count']; ?>
                                </div>
                            </td>
                            <td>
                            <button class="action-btn btn-view" onclick="editBoard(<?php echo $board['board_id']; ?>)">
                                Edit       
                            </button>   
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="board_id" value="<?php echo $board['board_id']; ?>">
                                    <button type="submit" name="delete_board" class="action-btn btn-delete"
                                            onclick="return confirm('Are you sure you want to delete this vision board?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <!-- Modal Form -->
<div id="boardModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Add New Vision Board</h2>
        
        <form id="addBoardForm" onsubmit="submitBoardForm(event)" enctype="multipart/form-data">
            <div class="form-group">
                <label for="user_id">User</label>
                <select name="user_id" id="user_id" required>
                    <option value="">Select User</option>
                    <?php 
                    $users_query = "SELECT u.user_id, up.first_name, up.last_name 
                                  FROM users u 
                                  LEFT JOIN user_profiles up ON u.user_id = up.user_id 
                                  ORDER BY up.first_name, up.last_name";
                    $users_result = $conn->query($users_query);
                    while ($user = $users_result->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $user['user_id']; ?>">
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" name="title" id="title" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description"></textarea>
            </div>

            <div class="form-group">
                <label>Vision Board Images</label>
                <div class="image-upload-container" id="dropZone">
                    <svg class="upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="upload-text">Drag & drop your images here or</p>
                    <label class="select-files-btn">
                        Select Files
                        <input type="file" name="images[]" class="file-input" accept="image/*" multiple onchange="handleFileSelect(event)">
                    </label>
                    <div id="imagePreviewContainer"></div>
                </div>
            </div>

            <button type="submit" class="action-btn btn-view">Create Vision Board</button>
        </form>
    </div>
</div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            const tableRows = document.querySelectorAll('tbody tr');

            tableRows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(searchText) ? '' : 'none';
            });
        });

        // Edit vision board function
        function editBoard(boardId) {
            window.location.href = `edit-board.php?id=${boardId}`;
        }

    function openModal() {
        document.getElementById('boardModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('boardModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == document.getElementById('boardModal')) {
            closeModal();
        }
    }

    function submitBoardForm(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    // Show loading state
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.textContent;
    submitButton.textContent = 'Creating...';
    submitButton.disabled = true;

    fetch('add_board_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Vision board created successfully!');
            closeModal();
            form.reset();
            document.getElementById('imagePreviewContainer').innerHTML = '';
            location.reload();
        } else {
            alert('Error creating vision board: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error creating vision board: ' + error.message);
    })
    .finally(() => {
        // Reset button state
        submitButton.textContent = originalButtonText;
        submitButton.disabled = false;
    });
}
    // Add these functions to your existing script section
function handleFileSelect(event) {
    const files = event.target.files;
    const previewContainer = document.getElementById('imagePreviewContainer');
    previewContainer.innerHTML = '';

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        if (!file.type.startsWith('image/')) continue;

        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('img');
            preview.src = e.target.result;
            preview.style.maxWidth = '200px';
            preview.style.maxHeight = '200px';
            preview.style.margin = '10px';
            previewContainer.appendChild(preview);
        }
        reader.readAsDataURL(file);
    }
}

// Update the submitBoardForm function to handle file upload
function submitBoardForm(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    fetch('add_board_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Vision board created successfully!');
            closeModal();
            form.reset();
            document.getElementById('imagePreviewContainer').innerHTML = '';
            location.reload();
        } else {
            alert('Error creating vision board: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error creating vision board');
    });
}
    </script>
</body>
</html>