<?php
// vision-boards.php
require_once '../../db/config.php';
require_once 'auth.php';
checkAdminAuth();

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
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="header">
            <h1>Vision Boards Management</h1>
            <input type="text" id="searchInput" placeholder="Search vision boards..." class="search-input">
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
                                <button class="action-btn btn-view" onclick="viewBoard(<?php echo $board['board_id']; ?>)">
                                    View
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

        // View vision board function
        function viewBoard(boardId) {
            window.location.href = `view-board.php?id=${boardId}`;
        }
    </script>
</body>
</html>