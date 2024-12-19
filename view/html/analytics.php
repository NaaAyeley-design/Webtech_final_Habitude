<?php

require_once '../../db/config.php';
session_start();

header('Content-Type: application/json');

// Check if action is defined to know the type of request
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'fetch_all_stats':
            // Combined query to fetch all statistics and users at once
            $stmt = $conn->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM users) AS total_users,
                    (SELECT COUNT(*) FROM users WHERE created_at > NOW() - INTERVAL 1 WEEK) AS new_users,
                    (SELECT COUNT(*) FROM boards) AS total_boards,
                    (SELECT COUNT(*) FROM journal_entries) AS total_entries,
                    (SELECT COUNT(*) FROM sessions) AS total_sessions,
                    (SELECT JSON_ARRAYAGG(
                        JSON_OBJECT('id', id, 'email', email, 'created_at', created_at, 'status', status, 'role', role)
                    ) FROM users) AS users_list
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();

            echo json_encode([
                'total_users' => $data['total_users'],
                'new_users' => $data['new_users'],
                'total_boards' => $data['total_boards'],
                'total_entries' => $data['total_entries'],
                'total_sessions' => $data['total_sessions'],
                'users' => json_decode($data['users_list']) // Decode the JSON array of users
            ]);
            break;

        default:
            echo json_encode(['error' => 'Unknown action']);
            break;
    }
} else {
    echo json_encode(['error' => 'No action defined']);
}

// Ensure no extra output is produced
exit;

?>
