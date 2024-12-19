<?php
// File: ../../utils/utilities.php

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            self::$instance = new mysqli('localhost', 'root', '', 'webtech_habitude');
            if (self::$instance->connect_error) {
                error_log("Database connection failed: " . self::$instance->connect_error);
                throw new Exception("Database connection failed");
            }
            $this->conn = self::$instance;
            error_log("Database connection successful");
        } catch (Exception $e) {
            error_log("Exception in Database::__construct: " . $e->getMessage());
            throw $e;
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            new Database();
        }
        return self::$instance;
    }
    
    // Database utility methods
    public function query($sql) {
        return $this->conn->query($sql);
    }

    public function prepare($sql) {
        return $this->conn->prepare($sql);
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

    // Authentication methods
    public function authenticateUser($email, $password) {
        $sql = "SELECT user_id, password_hash FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        
        return $user['user_id'];
    }
    
    public function createUser($email, $password, $firstName, $lastName) {
        $this->conn->begin_transaction();
        try {
            // Insert into users table
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (email, password_hash) VALUES (?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ss", $email, $password_hash);
            $stmt->execute();
            $userId = $this->conn->insert_id;
            
            // Insert into user_profiles table
            $sql = "INSERT INTO user_profiles (user_id, first_name, last_name) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iss", $userId, $firstName, $lastName);
            $stmt->execute();
            
            $this->conn->commit();
            return $userId;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}

// Session handling class
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
    
    public static function checkTimeout($timeout = 1800) { // 30 minutes default
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            self::clear();
            header('Location: 1loginpage.php?timeout=1');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }
}
?>