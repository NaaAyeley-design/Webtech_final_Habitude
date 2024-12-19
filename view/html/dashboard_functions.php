<?php
session_start();
require_once '../../db/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        echo json_encode(['error' => 'User not authenticated']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'get_analytics':
            try {
                $analytics = [
                    'total_journal_entries' => 0,
                    'journal_entries_over_time' => [],
                    'emotion_distribution' => [],
                    'weekly_focus_time' => [],
                    'status' => 'success'
                ];

                // Get journal entries over time
                $journalCountStmt = $conn->prepare("
                    SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as entry_count
                    FROM journal_entries 
                    WHERE user_id = ? 
                    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC
                ");
                $journalCountStmt->bind_param("i", $userId);
                $journalCountStmt->execute();
                $journalCountResult = $journalCountStmt->get_result();

                if ($journalCountResult->num_rows > 0) {
                    while ($row = $journalCountResult->fetch_assoc()) {
                        $analytics['journal_entries_over_time'][] = [
                            'date' => $row['date'],
                            'count' => (int)$row['entry_count']
                        ];
                    }
                } else {
                    // Create empty data for the last 30 days
                    for ($i = 30; $i >= 0; $i--) {
                        $date = date('Y-m-d', strtotime("-$i days"));
                        $analytics['journal_entries_over_time'][] = [
                            'date' => $date,
                            'count' => 0
                        ];
                    }
                }

                // Get total entries
                $totalStmt = $conn->prepare("SELECT COUNT(*) as total FROM journal_entries WHERE user_id = ?");
                $totalStmt->bind_param("i", $userId);
                $totalStmt->execute();
                $totalResult = $totalStmt->get_result();
                $analytics['total_journal_entries'] = (int)($totalResult->fetch_assoc()['total'] ?? 0);

                // Get emotion distribution
                $emotionStmt = $conn->prepare("
                    SELECT mood as emotion, COUNT(*) as count 
                    FROM journal_entries 
                    WHERE user_id = ? 
                    GROUP BY mood
                    ORDER BY count DESC
                ");
                $emotionStmt->bind_param("i", $userId);
                $emotionStmt->execute();
                $emotionResult = $emotionStmt->get_result();

                if ($emotionResult->num_rows > 0) {
                    while ($row = $emotionResult->fetch_assoc()) {
                        $analytics['emotion_distribution'][$row['emotion']] = (int)$row['count'];
                    }
                } else {
                    $analytics['emotion_distribution'] = [
                        'Happy' => 0,
                        'Sad' => 0,
                        'Excited' => 0,
                        'Calm' => 0,
                        'Stressed' => 0,
                        'Anxious' => 0
                    ];
                }

                // Get weekly focus time
                $focusTimeStmt = $conn->prepare("
                    SELECT 
                        DATE(completed_at) as date, 
                        ROUND(SUM(duration) / 60, 2) as total_focus_time 
                    FROM timer_sessions 
                    WHERE user_id = ? AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK) 
                    GROUP BY DATE(completed_at)
                    ORDER BY date
                ");
                $focusTimeStmt->bind_param("i", $userId);
                $focusTimeStmt->execute();
                $focusTimeResult = $focusTimeStmt->get_result();
                
                if ($focusTimeResult->num_rows > 0) {
                    while ($row = $focusTimeResult->fetch_assoc()) {
                        $analytics['weekly_focus_time'][] = [
                            'date' => $row['date'],
                            'total_focus_time' => (float)$row['total_focus_time']
                        ];
                    }
                } else {
                    for ($i = 6; $i >= 0; $i--) {
                        $date = date('Y-m-d', strtotime("-$i days"));
                        $analytics['weekly_focus_time'][] = [
                            'date' => $date,
                            'total_focus_time' => 0
                        ];
                    }
                }

                header('Content-Type: application/json');
                echo json_encode($analytics);

            } catch (Exception $e) {
                error_log("Analytics Error: " . $e->getMessage());
                echo json_encode([
                    'error' => 'Failed to fetch analytics data',
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            break;
    }
    exit;
}
?>