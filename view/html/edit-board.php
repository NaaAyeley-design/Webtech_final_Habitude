<?php
require_once '../../db/config.php';
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);


// Get board ID from URL
$board_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch board details
$stmt = $conn->prepare("SELECT vb.*, up.first_name, up.last_name 
                       FROM vision_boards vb 
                       LEFT JOIN users u ON vb.user_id = u.user_id 
                       LEFT JOIN user_profiles up ON u.user_id = up.user_id 
                       WHERE vb.board_id = ?");
$stmt->bind_param("i", $board_id);
$stmt->execute();
$board = $stmt->get_result()->fetch_assoc();

if (!$board) {
    die("Board not found");
}

// Fetch existing images
$stmt = $conn->prepare("SELECT * FROM board_images WHERE board_id = ?");
$stmt->bind_param("i", $board_id);
$stmt->execute();
$existing_images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update board details
    $title = $_POST['title'];
    $description = $_POST['description'];
    $user_id = $_POST['user_id'];

    $stmt = $conn->prepare("UPDATE vision_boards SET title = ?, description = ?, user_id = ? WHERE board_id = ?");
    $stmt->bind_param("ssii", $title, $description, $user_id, $board_id);
    $stmt->execute();

    // Handle image deletions
    if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $image_id) {
            // Get image path before deletion
            $stmt = $conn->prepare("SELECT image_path FROM board_images WHERE image_id = ? AND board_id = ?");
            $stmt->bind_param("ii", $image_id, $board_id);
            $stmt->execute();
            $image = $stmt->get_result()->fetch_assoc();

            if ($image) {
                // Delete physical file
                if (file_exists($image['image_path'])) {
                    unlink($image['image_path']);
                }

                // Delete database record
                $stmt = $conn->prepare("DELETE FROM board_images WHERE image_id = ? AND board_id = ?");
                $stmt->bind_param("ii", $image_id, $board_id);
                $stmt->execute();
            }
        }
    }

    // Handle new image uploads
    if (isset($_FILES['new_images'])) {
        $upload_dir = "../../uploads/vision_boards/";
        
        foreach ($_FILES['new_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['new_images']['error'][$key] === 0) {
                $filename = uniqid() . '_' . $_FILES['new_images']['name'][$key];
                $filepath = $upload_dir . $filename;

                if (move_uploaded_file($tmp_name, $filepath)) {
                    $stmt = $conn->prepare("INSERT INTO board_images (board_id, image_path) VALUES (?, ?)");
                    $stmt->bind_param("is", $board_id, $filepath);
                    $stmt->execute();
                }
            }
        }
    }

    // Redirect back to vision boards list
    header("Location: AdminVisionBoard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Vision Board - Habitude Admin</title>
    <style>
        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .form-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group textarea {
            height: 120px;
        }

        .existing-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .image-container {
            position: relative;
        }

        .image-container img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
        }

        .delete-checkbox {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .submit-btn {
            background-color: #3b82f6;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .submit-btn:hover {
            background-color: #2563eb;
        }

        .new-images-upload {
            margin-top: 20px;
            padding: 20px;
            border: 2px dashed #ddd;
            border-radius: 4px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container">
        <h1>Edit Vision Board</h1>

        <div class="form-container">
            <form method="POST" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $board_id; ?>">
                <div class="form-group">
                    <label for="user_id">User</label>
                    <select name="user_id" id="user_id" required>
                        <?php 
                        $users_query = "SELECT u.user_id, up.first_name, up.last_name 
                                      FROM users u 
                                      LEFT JOIN user_profiles up ON u.user_id = up.user_id 
                                      ORDER BY up.first_name, up.last_name";
                        $users_result = $conn->query($users_query);
                        while ($user = $users_result->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $user['user_id']; ?>" 
                                    <?php echo ($user['user_id'] == $board['user_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($board['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description"><?php echo htmlspecialchars($board['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Existing Images</label>
                    <div class="existing-images">
                        <?php foreach ($existing_images as $image): ?>
                            <div class="image-container">
                                <img src="<?php echo htmlspecialchars($image['image_path']); ?>" alt="Vision Board Image">
                                <input type="checkbox" name="delete_images[]" value="<?php echo $image['image_id']; ?>" class="delete-checkbox">
                                <label>Delete</label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Add New Images</label>
                    <div class="new-images-upload">
                        <input type="file" name="new_images[]" multiple accept="image/*">
                        <p>Drag & drop images here or click to select files</p>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        // Preview new images before upload
        document.querySelector('input[type="file"]').addEventListener('change', function(e) {
            const preview = document.createElement('div');
            preview.style.marginTop = '20px';
            preview.style.display = 'grid';
            preview.style.gridTemplateColumns = 'repeat(auto-fill, minmax(200px, 1fr))';
            preview.style.gap = '20px';

            for (const file of this.files) {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.width = '100%';
                        img.style.height = '200px';
                        img.style.objectFit = 'cover';
                        img.style.borderRadius = '4px';
                        preview.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                }
            }

            const container = this.parentElement;
            const existingPreview = container.querySelector('div:not(.new-images-upload)');
            if (existingPreview) {
                container.removeChild(existingPreview);
            }
            container.appendChild(preview);
        });
    </script>
</body>
</html>