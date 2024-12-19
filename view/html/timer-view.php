<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timer Management - Habitude Admin</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #007bff;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: #333;
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

        .search-input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 250px;
        }

        .timer-mode {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .mode-pomodoro {
            background-color: #fce7f3;
            color: #9d174d;
        }

        .mode-meditation {
            background-color: #e0e7ff;
            color: #3730a3;
        }

        .sound-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .sound-icon {
            color: #4b5563;
        }

        .duration-cell {
            font-family: monospace;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .table-container {
                overflow-x: auto;
            }

            .search-input {
                width: 100%;
                margin-bottom: 15px;
            }
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn-save {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .action-btn {
            padding: 4px 8px;
            margin: 0 2px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-edit {
            background-color: #007bff;
            color: white;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn-add {
            background-color: #28a745;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-add:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="header">
            <h1>Timer Management</h1>
            <input type="text" id="searchInput" placeholder="Search sessions..." class="search-input">
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Sessions</h3>
                <div class="stat-value"><?php echo number_format($totalSessions); ?></div>
            </div>
            <div class="stat-card">
                <h3>Active Users</h3>
                <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
            </div>
            <div class="stat-card">
                <h3>Average Duration</h3>
                <div class="stat-value"><?php echo round($avgDuration / 60, 1); ?> min</div>
            </div>
        </div>

        <div class="table-container">
            <div class="section-header">
                <h2 class="section-title">User Preferences</h2>
                <button onclick="showAddModal()" class="btn-add">Add New Preference</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Default Mode</th>
                        <th>Duration</th>
                        <th>Sound</th>
                        <th>Last Meditation Mode</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($pref = $prefResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pref['first_name'] . ' ' . $pref['last_name']); ?></td>
                            <td>
                                <span class="timer-mode mode-<?php echo strtolower($pref['default_mode']); ?>">
                                    <?php echo htmlspecialchars($pref['default_mode']); ?>
                                </span>
                            </td>
                            <td class="duration-cell"><?php echo floor($pref['default_duration'] / 60); ?> min</td>
                            <td>
                                <span class="sound-status">
                                    <i class="fas <?php echo $pref['sound_enabled'] ? 'fa-volume-up' : 'fa-volume-mute'; ?> sound-icon"></i>
                                    <?php echo $pref['sound_enabled'] ? 'Enabled' : 'Disabled'; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($pref['last_meditation_mode']); ?></td>
                            <td>
                                <button onclick='editPreference(<?php echo json_encode($pref); ?>)' 
                                        class="action-btn btn-edit">
                                    Edit
                                </button>
                                <button onclick='deletePreference(<?php echo $pref['preference_id']; ?>)' 
        class="action-btn btn-delete">
    Delete
</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h2 class="section-title">Recent Sessions</h2>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Mode</th>
                        <th>Duration</th>
                        <th>Completed At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($session = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></td>
                            <td>
                                <span class="timer-mode mode-<?php echo strtolower($session['mode_type']); ?>">
                                    <?php echo htmlspecialchars($session['mode_type']); ?>
                                </span>
                            </td>
                            <td class="duration-cell"><?php echo floor($session['duration'] / 60); ?> min</td>
                            <td><?php echo date('M d, Y H:i', strtotime($session['completed_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h2>Add New User Preference</h2>
            <form id="addForm" method="POST">
                <div class="form-group">
                    <label for="user_id">User:</label>
                    <select id="user_id" name="user_id" required>
                        <?php
                        $userQuery = "SELECT u.user_id, up.first_name, up.last_name 
                                    FROM users u 
                                    LEFT JOIN user_profiles up ON u.user_id = up.user_id 
                                    LEFT JOIN timer_preferences tp ON u.user_id = tp.user_id 
                                    WHERE tp.user_id IS NULL";
                        $userResult = $conn->query($userQuery);
                        while ($user = $userResult->fetch_assoc()) {
                            echo '<option value="' . $user['user_id'] . '">' . 
                                 htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . 
                                 '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="add_default_mode">Default Mode:</label>
                    <select id="add_default_mode" name="default_mode" required>
                        <option value="Pomodoro">Pomodoro</option>
                        <option value="Meditation">Meditation</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="add_default_duration">Duration (minutes):</label>
                    <input type="number" id="add_default_duration" name="default_duration" required>
                </div>
                <div class="form-group">
                    <label for="add_sound_enabled">Sound:</label>
                    <select id="add_sound_enabled" name="sound_enabled" required>
                        <option value="1">Enabled</option>
                        <option value="0">Disabled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="add_last_meditation_mode">Last Meditation Mode:</label>
                    <input type="text" id="add_last_meditation_mode" name="last_meditation_mode">
                </div>
                <button type="submit" name="add_preference" class="btn-save">Add Preference</button>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit User Preferences</h2>
            <form id="editForm" method="POST">
                <input type="hidden" id="edit_preference_id" name="edit_preference_id">
                <div class="form-group">
                    <label for="default_mode">Default Mode:</label>
                    <select id="default_mode" name="default_mode" required>
                        <option value="Pomodoro">Pomodoro</option>
                        <option value="Meditation">Meditation</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="default_duration">Duration (minutes):</label>
                    <input type="number" id="default_duration" name="default_duration" required>
                </div>
                <div class="form-group">
                    <label for="sound_enabled">Sound:</label>
                    <select id="sound_enabled" name="sound_enabled" required>
                        <option value="1">Enabled</option>
                        <option value="0">Disabled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="last_meditation_mode">Last Meditation Mode:</label>
                    <input type="text" id="last_meditation_mode" name="last_meditation_mode">
                </div>
                <button type="submit" name="update_preference" class="btn-save">Save Changes</button>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <script>
   // Modal and UI element references
const editModal = document.getElementById('editModal');
const addModal = document.getElementById('addModal');
const editCloseBtn = editModal.querySelector('.close');
const addCloseBtn = addModal.querySelector('.close');



function showAddModal() {
    addModal.style.display = 'block';
}

function closeAddModal() {
    addModal.style.display = 'none';
    document.getElementById('addForm').reset();
}

function closeEditModal() {
    editModal.style.display = 'none';
    document.getElementById('editForm').reset();
}

editCloseBtn.onclick = closeEditModal;
addCloseBtn.onclick = closeAddModal;

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target == editModal) {
        closeEditModal();
    }
    if (event.target == addModal) {
        closeAddModal();
    }
}

// Edit form handler
document.getElementById('editForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const duration = document.getElementById('default_duration').value;
    if (duration <= 0 || duration > 180) {
        alert('Duration must be between 1 and 180 minutes');
        return;
    }

    try {
        const formData = new FormData(this);
        formData.append('update_preference', '1');

        const response = await fetch('timer.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new TypeError("Expected JSON response");
        }

        const data = await response.json();
        
        if (data.success) {
            closeEditModal();
            window.location.reload();
        } else {
            alert(data.error || 'Error updating preference');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error updating preference. Please try again.');
    }
});



function editPreference(pref) {
    try {
        // Parse the preference data if it's a string
        const prefData = typeof pref === 'string' ? JSON.parse(pref) : pref;
        
        // Set form values
        document.getElementById('edit_preference_id').value = prefData.preference_id;
        document.getElementById('default_mode').value = prefData.default_mode;
        document.getElementById('default_duration').value = Math.floor(Number(prefData.default_duration) / 60);
        document.getElementById('sound_enabled').value = prefData.sound_enabled === "1" || prefData.sound_enabled === 1 ? "1" : "0";
        document.getElementById('last_meditation_mode').value = prefData.last_meditation_mode || '';
        
        // Show modal
        editModal.style.display = "block";
    } catch (error) {
        console.error('Error setting edit form values:', error);
        alert('Error editing preference. Please try again.');
    }
}
function escapeHtml(unsafe) {
    return unsafe
        ? unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;")
        : '';
}


// Function to refresh data
async function refreshData() {
    try {
        const response = await fetch('timer.php?action=get_all_data');
        if (!response.ok) throw new Error('Network response was not ok');
        
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new TypeError("Expected JSON response");
        }

        const data = await response.json();
        if (data.success) {
            if (data.data.preferences) updatePreferencesTable(data.data.preferences);
            if (data.data.stats) updateStatistics(data.data.stats);
            if (data.data.sessions) updateSessionsTable(data.data.sessions);
        } else {
            console.error('Server error:', data.error);
            alert('Error refreshing data. Please try again.');
        }
    } catch (error) {
        console.error('Error refreshing data:', error);
        alert('Error refreshing data. Please try again.');
    }
}

// Function to update preferences table
function updatePreferencesTable(preferences) {
    const tbody = document.querySelector('.table-container table:first-of-type tbody');
    if (!tbody) {
        console.error('Preferences table body not found');
        return;
    }

    tbody.innerHTML = '';
    
    preferences.forEach(pref => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${escapeHtml(pref.first_name + ' ' + pref.last_name)}</td>
            <td>
                <span class="timer-mode mode-${pref.default_mode.toLowerCase()}">
                    ${escapeHtml(pref.default_mode)}
                </span>
            </td>
            <td class="duration-cell">${Math.floor(pref.default_duration / 60)} min</td>
            <td>
                <span class="sound-status">
                    <i class="fas ${pref.sound_enabled ? 'fa-volume-up' : 'fa-volume-mute'} sound-icon"></i>
                    ${pref.sound_enabled ? 'Enabled' : 'Disabled'}
                </span>
            </td>
            <td>${escapeHtml(pref.last_meditation_mode || '')}</td>
            <td>
                <button onclick='editPreference(<?php echo json_encode($pref, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' 
                    class="action-btn btn-edit">
                    Edit
                </button>
                <button onclick='deletePreference(${pref.preference_id})' 
                        class="action-btn btn-delete">
                    Delete
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Function to update statistics
function updateStatistics(stats) {
    const statValues = document.querySelectorAll('.stat-value');
    if (statValues.length >= 3) {
        statValues[0].textContent = Number(stats.totalSessions).toLocaleString();
        statValues[1].textContent = Number(stats.totalUsers).toLocaleString();
        statValues[2].textContent = `${(stats.avgDuration / 60).toFixed(1)} min`;
    }
}

// Function to update sessions table
function updateSessionsTable(sessions) {
    const tbody = document.querySelector('.table-container:last-of-type table tbody');
    if (!tbody) {
        console.error('Sessions table body not found');
        return;
    }

    tbody.innerHTML = '';
    
    sessions.forEach(session => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${escapeHtml(session.first_name + ' ' + session.last_name)}</td>
            <td>
                <span class="timer-mode mode-${session.mode_type.toLowerCase()}">
                    ${escapeHtml(session.mode_type)}
                </span>
            </td>
            <td class="duration-cell">${Math.floor(session.duration / 60)} min</td>
            <td>${new Date(session.completed_at).toLocaleString()}</td>
        `;
        tbody.appendChild(row);
    });
}

// Update delete preference function to prevent page refresh
// Delete function
async function deletePreference(preferenceId) {
    if (!confirm('Are you sure you want to delete this preference?')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('delete_preference_id', preferenceId);
        formData.append('delete_preference', '1');

        const response = await fetch('timer.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Error deleting preference');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error deleting preference. Please try again.');
    }
}
// Edit form handler
document.getElementById('editForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const duration = document.getElementById('default_duration').value;
    if (duration <= 0 || duration > 180) {
        alert('Duration must be between 1 and 180 minutes');
        return;
    }
    try {
        const formData = new FormData(this);
        formData.append('update_preference', '1');
        const response = await fetch('timer.php', { method: 'POST', body: formData });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new TypeError("Expected JSON response");
        }
        const data = await response.json();
        if (data.success) {
            closeEditModal();
            window.location.reload();
        } else {
            throw new Error(data.error || 'Error updating preference');
        }
    } catch (error) {
        console.error('Error:', error);
        alert(error.message);
    }
});
// Add form handler
document.getElementById('addForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const duration = document.getElementById('add_default_duration').value;
    if (duration <= 0 || duration > 180) {
        alert('Duration must be between 1 and 180 minutes');
        return;
    }

    try {
        const formData = new FormData(this);
        formData.append('add_preference', '1');

        const response = await fetch('timer.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Error adding preference');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error adding preference. Please try again.');
    }
});
    </script>
</body>
</html>