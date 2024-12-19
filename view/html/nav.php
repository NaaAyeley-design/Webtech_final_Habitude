<?php
// nav.php
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        /* Reset and general styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Sidebar styles */
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: #1a1a1a;
            position: fixed;
            left: 0;
            top: 0;
            color: white;
            padding: 20px 0;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .toggle-btn {
            position: absolute;
            right: -40px;
            top: 10px;
            background: #1a1a1a;
            color: white;
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-container {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #333;
            margin-bottom: 20px;
            white-space: nowrap;
            overflow: hidden;
        }

        .logo-container h1 {
            font-size: 24px;
            color: #fff;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
        }

        .nav-item {
            padding: 0;
            margin: 5px 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: #b3b3b3;
            text-decoration: none;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .nav-link:hover {
            background-color: #333;
            color: #fff;
        }

        .nav-link.active {
            background-color: #333;
            color: #fff;
            border-left: 4px solid #007bff;
        }

        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main content wrapper */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            background-color: #f4f6f9;
            transition: all 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 70px;
        }

        /* User info */
        .user-info {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 20px;
            border-top: 1px solid #333;
            background-color: #1a1a1a;
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar.collapsed .user-info span,
        .sidebar.collapsed .logo-container h1,
        .sidebar.collapsed .nav-link span {
            display: none;
        }

        /* Logout button */
        .logout-btn {
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <button class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="logo-container">
            <h1>Habitude Admin</h1>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="1admindashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == '1admindashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="1admindashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == '1admindashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="JournalAdmin.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'JournalAdmin.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Journals</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="AdminVisionBoard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'AdminVisionBoard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-images"></i>
                    <span>Vision Boards</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="AdminTimer.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'timer.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i>
                    <span>Timer</span>
                </a>
            </li>
        </ul>

        <div class="user-info">
            <p><i class="fas fa-user"></i> <span>Admin User</span></p>
            <p><i class="fas fa-envelope"></i> <span>admin@habitude.com</span></p>
            <form action="../../actions/logout_user.php" method="POST" style="margin: auto 0 1rem 0;">
                <button type="submit" class="logout-btn">
                    <i data-lucide="log-out"></i> Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Add Font Awesome for icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>

    <!-- Sidebar toggle script -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');

            // Store the state in localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }

        // Restore sidebar state on page load
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
        });
    </script>
</body>
</html>