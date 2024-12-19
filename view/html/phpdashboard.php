# Project Structure
/habitude
├── config/
│   └── database.php           # Database connection configuration
├── includes/
│   ├── header.php             # Common header for all pages
│   └── footer.php             # Common footer for all pages
├── classes/
│   ├── Database.php           # Database connection and helper methods
│   ├── User.php               # User authentication and management
│   ├── Habit.php              # Habit tracking methods
│   ├── Task.php               # Task management methods
│   └── Journal.php            # Mindfulness journal methods
├── public/
│   ├── css/
│   │   └── dashboard.css      # Styling for dashboard
│   └── js/
│       └── dashboard.js       # Dashboard interactivity
├── dashboard.php              # Main dashboard page
├── habits.php                 # Habits management page
├── tasks.php                  # Tasks management page
├── journal.php                # Mindfulness journal page
└── index.php                  # Login/Registration page

# 1. config/database.php
<?php
class DatabaseConfig {
    private static $host = 'localhost';
    private static $username = 'your_db_username';
    private static $password = 'your_db_password';
    private static $database = 'habitude_db';

    public static function getConnection() {
        try {
            $conn = new PDO(
                "mysql:host=" . self::$host . ";dbname=" . self::$database, 
                self::$username, 
                self::$password
            );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
}

# 2. classes/Habit.php
<?php
class Habit {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createHabit($userId, $habitName, $description, $category, $goalFrequency) {
        $sql = "INSERT INTO habits (user_id, habit_name, description, category, goal_frequency, start_date) 
                VALUES (:user_id, :habit_name, :description, :category, :goal_frequency, CURDATE())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':habit_name', $habitName);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':goal_frequency', $goalFrequency);
        
        return $stmt->execute();
    }

    public function getUserHabits($userId) {
        $sql = "SELECT * FROM habits WHERE user_id = :user_id AND is_active = TRUE";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function trackHabitEntry($habitId, $completed, $notes = null) {
        $sql = "INSERT INTO habit_entries (habit_id, entry_date, completed, notes) 
                VALUES (:habit_id, CURDATE(), :completed, :notes)
                ON DUPLICATE KEY UPDATE completed = :completed, notes = :notes";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':habit_id', $habitId);
        $stmt->bindParam(':completed', $completed, PDO::PARAM_BOOL);
        $stmt->bindParam(':notes', $notes);
        
        return $stmt->execute();
    }
}

# 3. dashboard.php
<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Habit.php';
require_once 'classes/Task.php';

$db = DatabaseConfig::getConnection();
$habitManager = new Habit($db);
$taskManager = new Task($db);

// Ensure user is logged in (you'll need to implement authentication)
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$userHabits = $habitManager->getUserHabits($_SESSION['user_id']);
$upcomingTasks = $taskManager->getUpcomingTasks($_SESSION['user_id']);

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Habitude - Dashboard</title>
    <link rel="stylesheet" href="public/css/dashboard.css">
</head>
<body>
    <div class="dashboard-content">
        <div class="dashboard-panel">
            <div class="panel-header">
                <div class="panel-title">Habit Tracker</div>
            </div>
            <div class="habit-list">
                <?php foreach($userHabits as $habit): ?>
                    <div class="habit-item">
                        <span><?= htmlspecialchars($habit['habit_name']) ?></span>
                        <form method="post" action="track_habit.php">
                            <input type="hidden" name="habit_id" value="<?= $habit['habit_id'] ?>">
                            <button type="submit" name="complete" value="1">Complete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <a href="add_habit.php" class="btn">Add New Habit</a>
            </div>
        </div>

        <div class="dashboard-panel">
            <div class="panel-header">
                <div class="panel-title">Upcoming Tasks</div>
            </div>
            <div class="habit-list">
                <?php foreach($upcomingTasks as $task): ?>
                    <div class="habit-item">
                        <span><?= htmlspecialchars($task['title']) ?></span>
                        <span><?= date('M d, Y', strtotime($task['due_date'])) ?></span>
                    </div>
                <?php endforeach; ?>
                <a href="add_task.php" class="btn">Add New Task</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php include 'includes/footer.php'; ?>