<?php
require_once '../../db/config.php';
session_start();

// Get timer statistics with error checking
$totalSessions = 0;
$totalUsers = 0;
$avgDuration = 0;

$sessionsQuery = $conn->query("SELECT COUNT(*) as count FROM timer_sessions");
if ($sessionsQuery && $result = $sessionsQuery->fetch_assoc()) {
    $totalSessions = $result['count'];
}

$usersQuery = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM timer_sessions");
if ($usersQuery && $result = $usersQuery->fetch_assoc()) {
    $totalUsers = $result['count'];
}

$durationQuery = $conn->query("SELECT AVG(duration) as avg FROM timer_sessions");
if ($durationQuery && $result = $durationQuery->fetch_assoc()) {
    $avgDuration = $result['avg'] ?: 0;
}

// Get recent timer sessions
$query = "SELECT ts.*, up.first_name, up.last_name 
          FROM timer_sessions ts 
          LEFT JOIN users u ON ts.user_id = u.user_id 
          LEFT JOIN user_profiles up ON u.user_id = up.user_id 
          ORDER BY ts.completed_at DESC 
          LIMIT 100";
$result = $conn->query($query);

// Get user preferences
$prefQuery = "SELECT tp.*, up.first_name, up.last_name 
              FROM timer_preferences tp 
              LEFT JOIN users u ON tp.user_id = u.user_id 
              LEFT JOIN user_profiles up ON u.user_id = up.user_id 
              ORDER BY tp.preference_id DESC";
$prefResult = $conn->query($prefQuery);

// Get users without preferences for the add modal
$userQuery = "SELECT u.user_id, up.first_name, up.last_name 
              FROM users u 
              LEFT JOIN user_profiles up ON u.user_id = up.user_id 
              LEFT JOIN timer_preferences tp ON u.user_id = tp.user_id 
              WHERE tp.user_id IS NULL
              ORDER BY up.first_name, up.last_name";
$userResult = $conn->query($userQuery);

// Check if queries were successful
if (!$result || !$prefResult || !$userResult) {
    die("Error executing queries: " . $conn->error);
}

// Include the view file
include 'timer-view.php';
?>