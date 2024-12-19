<?php
// File: includes/utilities.php

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        $configPath = __DIR__ . '/../../db/config.php';
        if (!file_exists($configPath)) {
            throw new Exception("Config file not found at: " . $configPath);
        }
        
        require_once $configPath;
        
        if (!isset($GLOBALS['conn'])) {
            // If $conn isn't set, create the connection using your existing config variables
            $GLOBALS['conn'] = new mysqli($servername, $username, $password, $dbname);
            
            if ($GLOBALS['conn']->connect_error) {
                throw new Exception("Connection failed: " . $GLOBALS['conn']->connect_error);
            }
        }
        
        $this->conn = $GLOBALS['conn'];
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            try {
                self::$instance = new mysqli('localhost', 'root','', 'webtech_habitude');
                if (self::$instance->connect_error) {
                    error_log("Database connection failed: " . self::$instance->connect_error);
                    return null;
                }
                error_log("Database connection successful");
            } catch (Exception $e) {
                error_log("Exception in Database::getInstance: " . $e->getMessage());
                return null;
            }
        }
        return self::$instance;
    }
    
    // Authentication methods
    public function authenticateUser($email, $password) {
        $stmt = $this->conn->prepare("
            SELECT id, password_hash, failed_attempts, last_attempt_time 
            FROM users WHERE email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user) {
            throw new Exception("Invalid email or password");
        }
        
        // Check for too many failed attempts
        if ($user['failed_attempts'] >= 5 && time() - strtotime($user['last_attempt_time']) < 900) {
            throw new Exception("Account temporarily locked. Please try again in 15 minutes.");
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            $this->updateFailedAttempts($user['id']);
            throw new Exception("Invalid email or password");
        }
        
        $this->resetFailedAttempts($user['id']);
        return $user['id'];
    }
    

        // Add these public methods to your Database class
        public function query($sql) {
            return $this->conn->query($sql);
        }

        public function prepare($sql) {
            return $this->conn->prepare($sql);
        }


    private function updateFailedAttempts($userId) {
        $stmt = $this->conn->prepare("
            UPDATE users 
            SET failed_attempts = failed_attempts + 1,
                last_attempt_time = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
    
    private function resetFailedAttempts($userId) {
        $stmt = $this->conn->prepare("
            UPDATE users 
            SET failed_attempts = 0,
                last_attempt_time = NULL
            WHERE id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
    
    // Timer related methods
    public function getUserPreferences($userId) {
        $sql = "SELECT * FROM timer_preferences WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->createDefaultPreferences($userId);
            return [
                'default_mode' => 'Pomodoro',
                'default_duration' => 1500,
                'sound_enabled' => true,
                'last_meditation_mode' => 'breathing'
            ];
        }
        
        return $result->fetch_assoc();
    }
    
    private function createDefaultPreferences($userId) {
        $sql = "INSERT INTO timer_preferences (user_id) VALUES (?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
    
    public function updatePreferences($userId, $preferences) {
        $sql = "UPDATE timer_preferences SET 
                default_mode = ?,
                default_duration = ?,
                sound_enabled = ?,
                last_meditation_mode = ?
                WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("siisi", 
            $preferences['default_mode'],
            $preferences['default_duration'],
            $preferences['sound_enabled'],
            $preferences['last_meditation_mode'],
            $userId
        );
        return $stmt->execute();
    }
    
    public function logTimerSession($userId, $mode, $duration) {
        $sql = "INSERT INTO timer_sessions (user_id, mode_type, duration, completed_at) 
                VALUES (?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isi", $userId, $mode, $duration);
        
        try {
            if (!$stmt->execute()) {
                error_log("Failed to log timer session: " . $stmt->error);
                return false;
            }
            return true;
        } catch (Exception $e) {
            error_log("Error logging timer session: " . $e->getMessage());
            return false;
        }
    }
    
    public function getRecentSessions($userId, $limit = 5) {
        $sql = "SELECT * FROM timer_sessions WHERE user_id = ? ORDER BY completed_at DESC LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Session handling functions
class SessionManager {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function setUser($userId) {
        if (!is_numeric($userId) || $userId <= 0) {
            throw new InvalidArgumentException('Invalid user ID');
        }
        $_SESSION['user_id'] = $userId;
        $_SESSION['last_activity'] = time();
        session_regenerate_id(true);
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    }
    
    public static function clear() {
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: 1loginpage.php');
            exit;
        }
    }
}
?>