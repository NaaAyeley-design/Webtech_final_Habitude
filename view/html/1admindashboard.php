<?php
require_once '../../db/config.php';


/*$isAdmin = false;
if (isset($_SESSION['user_id'])) {
    // Check if the logged-in user is user_id 2 (which is admin)
    if ($_SESSION['role_id'] == 2) {
        $isAdmin = true;
    }
}

if (!$isAdmin) {
    header('Location: 1loginpage.php');
    exit;
}*/
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Habitude</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lucide-static/0.321.0/lucide.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
    <style>
        /* Base styles */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        /* Container and Navigation */
        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: #111;
            color: white;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            transition: width 0.3s ease;
        }

        .sidebar-logo {
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .sidebar-logo h1 {
            color: white;
            font-size: 1.5rem;
            margin: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            color: #fff;
            text-decoration: none;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-link i {
            margin-right: 1rem;
            font-size: 1.2rem;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link.active {
            background-color: #007bff;
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            padding: 2rem;
            background-color: #f8f9fa;
        }

        .main-content h1 {
            color: #333;
            margin-bottom: 2rem;
            font-size: 1.8rem;
        }

        /* Stats Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 600;
            color: #007bff;
            margin: 0.5rem 0;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        /* Table Section */
        .table-wrapper {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .table-wrapper h2 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Action Buttons */
        .action-btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background-color 0.2s ease;
        }

        .edit-btn {
            background-color: #007bff;
            color: white;
            margin-right: 0.5rem;
        }

        .edit-btn:hover {
            background-color: #0056b3;
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding: 1rem;
            }

            .main-content {
                padding: 1rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .table-wrapper {
                overflow-x: auto;
            }
        }

        /* Retractable Sidebar Animation */
        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar.collapsed .nav-link span {
            display: none;
        }

        .sidebar.collapsed .sidebar-logo h1 {
            display: none;
        }

        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 0.8rem;
        }

        .sidebar.collapsed .nav-link i {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'nav.php'; ?>

        <main class="main-content">
            <h1>Dashboard Overview</h1>
            
            <div class="dashboard-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <p class="stat-number" id="totalUsers">Loading...</p>
                    <p class="stat-label">New this week: <span id="newUsers">Loading...</span></p>
                </div>

                <div class="stat-card">
                    <h3>Vision Boards</h3>
                    <p class="stat-number" id="totalBoards">Loading...</p>
                </div>

                <div class="stat-card">
                    <h3>Journal Entries</h3>
                    <p class="stat-number" id="totalEntries">Loading...</p>
                </div>

                <div class="stat-card">
                    <h3>Timer Sessions</h3>
                    <p class="stat-number" id="totalSessions">Loading...</p>
                </div>
            </div>

            <div class="table-wrapper">
                <h2>User Management</h2>
                <table id="userTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Created At</th>
                            <th>Status</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Table rows will be populated dynamically -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>

           

    <script src="https://unpkg.com/lucide@latest"></script><script>
// Initialize Lucide icons
lucide.createIcons();

// Function to format date strings
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Function to get role name from role_id
function getRoleName(roleId) {
    switch (roleId) {
        case 1:
            return 'User';
        case 2:
            return 'Admin';
        default:
            return 'Unknown';
    }
}

// Function to format status
function formatStatus(status) {
    return status ? 'Active' : 'Inactive';
}

// Function to fetch analytics and user data
function fetchAnalytics() {
    const formData = new FormData();
    formData.append('action', 'fetch_all_stats');

    fetch('analytics.php', {
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
            // Update statistics
            document.getElementById('totalUsers').textContent = data.total_users;
            document.getElementById('newUsers').textContent = data.new_users;
            document.getElementById('totalBoards').textContent = data.total_boards;
            document.getElementById('totalEntries').textContent = data.total_entries;
            document.getElementById('totalSessions').textContent = data.total_sessions;

            // Update user table
            const userTableBody = document.querySelector('#userTable tbody');
            userTableBody.innerHTML = ''; // Clear existing rows

            data.users.forEach(user => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${user.id}</td>
                    <td>
                        ${user.email}<br>
                        <small class="text-muted">${user.first_name} ${user.last_name}</small>
                    </td>
                    <td>${formatDate(user.created_at)}</td>
                    <td>
                        <span class="status-badge ${user.status === 'active' ? 'active' : 'inactive'}">
                            ${formatStatus(user.status === 'active')}
                        </span>
                    </td>
                    <td>${getRoleName(user.role)}</td>
                    <td>
                        <button class="action-btn edit-btn" onclick="editUser(${user.id})">
                            Edit
                        </button>
                        <button class="action-btn delete-btn" onclick="deleteUser(${user.id})">
                            Delete
                        </button>
                    </td>
                `;
                userTableBody.appendChild(row);
            });
        } else {
            console.error('Error fetching data:', data.error);
            // Show error message to user
            alert('Error loading dashboard data. Please try refreshing the page.');
        }
    })
    .catch(error => {
        console.error('There was a problem with the fetch operation:', error);
        alert('Error connecting to the server. Please check your connection and try again.');
    });
}

// Function to handle edit user
function editUser(userId) {
    if (confirm('Are you sure you want to edit this user?')) {
        window.location.href = `edit_user.php?id=${userId}`;
    }
}

// Function to handle delete user
function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('action', 'delete_user');
        formData.append('user_id', userId);

        fetch('../../actions/admin_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh the dashboard data
                fetchAnalytics();
                alert('User deleted successfully');
            } else {
                alert('Error deleting user: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting user. Please try again.');
        });
    }
}

// Add some CSS for status badges
const style = document.createElement('style');
style.textContent = `
    .status-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.875rem;
    }
    .status-badge.active {
        background-color: #28a745;
        color: white;
    }
    .status-badge.inactive {
        background-color: #dc3545;
        color: white;
    }
    .text-muted {
        color: #6c757d;
    }
`;
document.head.appendChild(style);

// Fetch the analytics and user data when the page loads
document.addEventListener('DOMContentLoaded', fetchAnalytics);
</script>



</body>
</html>
