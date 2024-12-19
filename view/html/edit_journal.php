<?php
// edit-journal.php
require_once '../../db/config.php';
session_start();

if (!isset($_GET['id'])) {
    header('Location: AdminJournals.php');
    exit;
}

$entry_id = $_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'];
    $mood = $_POST['mood'];
    $tags = $_POST['tags'];
    $is_favorite = isset($_POST['is_favorite']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE journal_entries SET content = ?, mood = ?, tags = ?, is_favorite = ? WHERE entry_id = ?");
    $stmt->bind_param("sssii", $content, $mood, $tags, $is_favorite, $entry_id);
    
    if ($stmt->execute()) {
        header("Location: view-journal.php?id=" . $entry_id);
        exit;
    }
}

// Get journal entry
$query = "SELECT * FROM journal_entries WHERE entry_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $entry_id);
$stmt->execute();
$result = $stmt->get_result();
$entry = $result->fetch_assoc();

if (!$entry) {
    header('Location: AdminJournals.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Journal Entry - Habitude Admin</title>
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 200px;
            resize: vertical;
        }
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 4px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-save {
            background-color: #3b82f6;
            color: white;
        }
        .btn-cancel {
            background-color: #6b7280;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container">
        <h1>Edit Journal Entry</h1>

        <form method="POST">
            <div class="form-group">
                <label for="content">Journal Content</label>
                <textarea name="content" id="content" required><?php echo htmlspecialchars($entry['content']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="mood">Mood</label>
                <input type="text" name="mood" id="mood" value="<?php echo htmlspecialchars($entry['mood']); ?>" required>
            </div>

            <div class="form-group">
                <label for="tags">Tags (comma-separated)</label>
                <input type="text" name="tags" id="tags" value="<?php echo htmlspecialchars($entry['tags']); ?>" placeholder="happy, productive, inspired">
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_favorite" <?php echo $entry['is_favorite'] ? 'checked' : ''; ?>> Mark as Favorite
                </label>
            </div>

            <button type="submit" class="action-btn btn-save">Save Changes</button>
            <a href="view-journal.php?id=<?php echo $entry_id; ?>" class="action-btn btn-cancel">Cancel</a>
        </form>
    </div>
</body>
</html>