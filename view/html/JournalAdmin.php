<?php
// journals.php
require_once '../../db/config.php';
session_start();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle journal deletion
    if (isset($_POST['delete_journal'])) {
        $entry_id = $_POST['entry_id'];
        $stmt = $conn->prepare("DELETE FROM journal_entries WHERE entry_id = ?");
        $stmt->bind_param("i", $entry_id);
        if ($stmt->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?delete=success");
            exit;
        } else {
            $error = "Error deleting journal entry: " . $conn->error;
        }
    } 
    // Handle journal addition (though this should now be handled by add_journal_ajax.php)
    else if (isset($_POST['user_id'], $_POST['content'], $_POST['mood'])) {
        $user_id = $_POST['user_id'];
        $content = $_POST['content'];
        $mood = $_POST['mood'];
        $tags = $_POST['tags'] ?? '';
        $is_favorite = isset($_POST['is_favorite']) ? 1 : 0;

        $stmt = $conn->prepare("INSERT INTO journal_entries (user_id, content, mood, tags, is_favorite) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $user_id, $content, $mood, $tags, $is_favorite);
        
        if ($stmt->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit;
        } else {
            $error = "Error creating journal entry: " . $conn->error;
        }
    }
}

// Get all users for the dropdown
$users_query = "SELECT u.user_id, up.first_name, up.last_name 
                FROM users u 
                LEFT JOIN user_profiles up ON u.user_id = up.user_id 
                ORDER BY up.first_name, up.last_name";
$users_result = $conn->query($users_query);

// Get all journal entries with user information
$query = "SELECT je.*, up.first_name, up.last_name 
          FROM journal_entries je 
          LEFT JOIN users u ON je.user_id = u.user_id 
          LEFT JOIN user_profiles up ON u.user_id = up.user_id 
          ORDER BY je.created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal Management - Habitude Admin</title>
    <style>
        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            transition: all 0.3s ease;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
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

        .tag {
            background: #e2e8f0;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.875rem;
            margin: 2px;
            display: inline-block;
        }

        .mood-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.875rem;
            background: #ddd;
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

        .favorite-star {
            color: gold;
            font-size: 1.2em;
        }

        .container {
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
        transition: all 0.3s ease; /* Add smooth transition */
    }

    .table-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        overflow-x: auto;
        width: 100%; /* Ensure table container takes full width */
    }

    .form-container {
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
        select, input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        textarea {
            height: 200px;
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

        /* Modal styles */
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group select,
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-group textarea {
            height: 200px;
            resize: vertical;
        }

        /* Existing button styles */
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

        /* Success message */
        .success-message {
            background-color: #10B981;
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="success-message" id="successMessage">
            Journal entry added successfully!
        </div>

        <div class="header">
            <h1>🎯 Journal Entries Management</h1>
            <div class="header-actions">
                <button onclick="openModal()" class="action-btn btn-view">Add Journal</button>
                <input type="text" id="searchInput" placeholder="Search entries..." class="search-input">
            </div>
        </div>

        <!-- Modal Form -->
        <div id="journalModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>Add New Journal Entry</h2>
                <form id="addJournalForm" onsubmit="submitJournalForm(event)">
                    <div class="form-group">
                        <label for="user_id">User</label>
                        <select name="user_id" id="user_id" required>
                            <option value="">Select User</option>
                            <?php while ($user = $users_result->fetch_assoc()): ?>
                                <option value="<?php echo $user['user_id']; ?>">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="content">Journal Content</label>
                        <textarea name="content" id="content" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="mood">Mood</label>
                        <input type="text" name="mood" id="mood" required>
                    </div>

                    <div class="form-group">
                        <label for="tags">Tags (comma-separated)</label>
                        <input type="text" name="tags" id="tags" placeholder="happy, productive, inspired">
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_favorite" id="is_favorite"> Mark as Favorite
                        </label>
                    </div>

                    <button type="submit" class="action-btn btn-view">Create Journal Entry</button>
                </form>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Content Preview</th>
                        <th>Created At</th>
                        <th>Mood</th>
                        <th>Tags</th>
                        <th>Favorite</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($entry = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($entry['entry_id']); ?></td>
                            <td><?php echo htmlspecialchars($entry['first_name'] . ' ' . $entry['last_name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($entry['content'], 0, 100)) . '...'; ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($entry['created_at'])); ?></td>
                            <td>
                                <span class="mood-badge">
                                    <?php echo htmlspecialchars($entry['mood']); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                if ($entry['tags']) {
                                    $tags = explode(',', $entry['tags']);
                                    foreach ($tags as $tag) {
                                        echo '<span class="tag">' . htmlspecialchars(trim($tag)) . '</span>';
                                    }
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo $entry['is_favorite'] ? '<span class="favorite-star">⭐</span>' : ''; ?>
                            </td>
                            <td>
                                <button class="action-btn btn-view" onclick="editJournal(<?php echo $entry['entry_id']; ?>)">
                                    Edit
                                </button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="entry_id" value="<?php echo $entry['entry_id']; ?>">
                                    <button type="submit" name="delete_journal" class="action-btn btn-delete"
                                            onclick="return confirm('Are you sure you want to delete this journal entry?')">
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
        // Modal functions
        function openModal() {
            document.getElementById('journalModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('journalModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('journalModal')) {
                closeModal();
            }
        }


        function editJournal(entryId) {
    fetch(`get_journal.php?id=${entryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fill the modal form with the journal data
                document.getElementById('user_id').value = data.entry.user_id;
                document.getElementById('content').value = data.entry.content;
                document.getElementById('mood').value = data.entry.mood;
                document.getElementById('tags').value = data.entry.tags;
                document.getElementById('is_favorite').checked = data.entry.is_favorite === "1";
                
                // Add entry_id to the form
                const form = document.getElementById('addJournalForm');
                let entryIdInput = form.querySelector('input[name="entry_id"]');
                if (!entryIdInput) {
                    entryIdInput = document.createElement('input');
                    entryIdInput.type = 'hidden';
                    entryIdInput.name = 'entry_id';
                    form.appendChild(entryIdInput);
                }
                entryIdInput.value = entryId;

                // Update form title and button text
                document.querySelector('.modal-content h2').textContent = 'Edit Journal Entry';
                document.querySelector('.modal-content button[type="submit"]').textContent = 'Save Changes';
                
                // Show the modal
                openModal();
            } else {
                alert('Error loading journal entry');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading journal entry');
        });
}


        // Form submission
        function submitJournalForm(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const isEdit = formData.has('entry_id');

            fetch('add_journal_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert(isEdit ? 'Journal entry updated successfully!' : 'Journal entry added successfully!');
                    
                    // Close modal and reset form
                    closeModal();
                    form.reset();
                    
                    // Remove the entry_id if it exists
                    const entryIdInput = form.querySelector('input[name="entry_id"]');
                    if (entryIdInput) {
                        entryIdInput.remove();
                    }
                    
                    // Reset form title and button text
                    document.querySelector('.modal-content h2').textContent = 'Add New Journal Entry';
                    document.querySelector('.modal-content button[type="submit"]').textContent = 'Create Journal Entry';
                    
                    // Reload the page to show changes
                    location.reload();
                } else {
                    alert('Error ' + (isEdit ? 'updating' : 'adding') + ' journal entry: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error ' + (isEdit ? 'updating' : 'adding') + ' journal entry');
            });
        }

        function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user?')) {
        fetch(`delete_user.php?id=${userId}`)  // Removed the POST method
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User deleted successfully');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the user');
        });
    }
}
    </script>
</body>
</html>