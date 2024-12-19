<?php
require_once '../../db/config.php';
session_start();

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// Get user's name
$userName = 'Guest'; // Default value
try {
    $userNameStmt = $conn->prepare("SELECT first_name FROM user_profiles WHERE user_id = ?");
    $userNameStmt->bind_param("i", $userId);
    $userNameStmt->execute();
    $userNameResult = $userNameStmt->get_result();
    if ($row = $userNameResult->fetch_assoc()) {
        $userName = htmlspecialchars($row['first_name']);
    }
} catch (Exception $e) {
    error_log("Error fetching user name: " . $e->getMessage());
}

function getUserAnalytics($conn, $userId) {
    try {
        $analytics = [
            'total_journal_entries' => 0,
            'journal_entries_over_time' => [],
            'emotion_distribution' => [],
            'weekly_focus_time' => [],
            'status' => 'success'
        ];

        // Verify database connection
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

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
        if (!$journalCountStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $journalCountStmt->bind_param("i", $userId);
        if (!$journalCountStmt->execute()) {
            throw new Exception("Execute failed: " . $journalCountStmt->error);
        }
        $journalCountResult = $journalCountStmt->get_result();

        if ($journalCountResult->num_rows > 0) {
            while ($row = $journalCountResult->fetch_assoc()) {
                $analytics['journal_entries_over_time'][] = [
                    'date' => $row['date'],
                    'count' => $row['entry_count']
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
        $analytics['total_journal_entries'] = $totalResult->fetch_assoc()['total'] ?? 0;

        // Get emotion distribution
        $emotionStmt = $conn->prepare("
            SELECT mood as emotion, COUNT(*) as count 
            FROM journal_entries 
            WHERE user_id = ? 
            GROUP BY mood
            ORDER BY count DESC
        ");
        if (!$emotionStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $emotionStmt->bind_param("i", $userId);
        $emotionStmt->execute();
        $emotionResult = $emotionStmt->get_result();

        if ($emotionResult->num_rows > 0) {
            while ($row = $emotionResult->fetch_assoc()) {
                $analytics['emotion_distribution'][$row['emotion']] = $row['count'];
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
        if (!$focusTimeStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $focusTimeStmt->bind_param("i", $userId);
        $focusTimeStmt->execute();
        $focusTimeResult = $focusTimeStmt->get_result();
        
        if ($focusTimeResult->num_rows > 0) {
            while ($row = $focusTimeResult->fetch_assoc()) {
                $analytics['weekly_focus_time'][] = $row;
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

        return $analytics;

    } catch (Exception $e) {
        error_log("Analytics Error: " . $e->getMessage());
        return [
            'error' => 'Failed to fetch analytics data',
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'get_analytics':
            $analytics = getUserAnalytics($conn, $userId);
            header('Content-Type: application/json');
            echo json_encode($analytics);
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habitude User Analytics</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="../../assets/css/1dashboardcss.css">
    <link rel="stylesheet" href="../../assets/css/1dashboardcss.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
                /* Form positioning at bottom of nav */
        .sidebar form {
            margin-top: auto !important; /* Override inline style */
            margin-bottom: 2rem;
            width: 100%;
        }

        /* Purple Gradient Logout Button */
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            padding: 0.875rem 1.25rem;
            background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .logout-btn i {
            font-size: 1.25rem;
            transition: transform 0.3s ease;
        }

        .logout-btn span {
            font-weight: 500;
        }

        .logout-btn:hover {
            background: linear-gradient(135deg, #7C3AED 0%, #5B21B6 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(139, 92, 246, 0.2);
        }

        .logout-btn:active {
            transform: translateY(0);
        }

        /* Hover effect for icon */
        .logout-btn:hover i {
            transform: translateX(3px);
        }

        /* Focus state for accessibility */
        .logout-btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.4);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar form {
                margin-bottom: 1rem;
            }
            
            .logout-btn {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
            
            .logout-btn i {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <nav class="sidebar">
            <div class="sidebar-logo">
                <h1>Habitude</h1>
            </div>
            <a href="1dashboard.php" class="nav-link">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            <a href="1Journal.php" class="nav-link">
                <i data-lucide="book-open"></i> Journal
            </a>
            <a href="1timer.php" class="nav-link">
                <i data-lucide="timer"></i> Timer
            </a>
            <a href="1visionboard.php" class="nav-link">
                <i data-lucide="target"></i> Vision Board
            </a>
            <form action="logout_user.php" method="POST" style="margin-top: 10px;">
    <button type="submit" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </form>

        </nav>

        <main class="main-content">
            <header>
                <div class="greeting">
                    <h1 id="welcome-message">
                        <?php
                        $hour = date('G');
                        $greeting = 'Welcome back';
                        if ($hour < 12) $greeting = 'Good morning';
                        else if ($hour < 18) $greeting = 'Good afternoon';
                        else $greeting = 'Good evening';
                        echo "$greeting, $userName!";
                        ?>
                    </h1>
                    <p class="date"></p>
                </div>
            </header>

            <div class="analytics-section">
                <div class="widget">
                    <h2>Journal Entries Over Time</h2>
                    <div class="stats-header">
                        <span>Total Entries: <span id="total-journal-entries" class="large-stat"></span></span>
                    </div>
                    <canvas id="journalEntriesChart"></canvas>
                </div>
                
                <div class="widget">
                    <h2>Emotion Distribution</h2>
                    <canvas id="emotionChart"></canvas>
                </div>

                <div class="widget">
                    <h2>Weekly Focus Time</h2>
                    <canvas id="focusTimeChart"></canvas>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Date initialization
        const dateElement = document.querySelector('.date');
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        dateElement.textContent = new Date().toLocaleDateString('en-US', options);

        // Fetch and initialize analytics
        async function initializeAnalytics() {
            try {
                const response = await fetch('dashboard_functions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_analytics'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }

                // Journal Entries Chart
                const totalJournalEntriesEl = document.getElementById('total-journal-entries');
                totalJournalEntriesEl.textContent = data.total_journal_entries || '0';

                const journalData = data.journal_entries_over_time;
                const journalLabels = journalData.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                });
                const journalCounts = journalData.map(item => item.count);

                // Destroy existing journal entries chart if it exists
                const existingJournalChart = Chart.getChart('journalEntriesChart');
                if (existingJournalChart) existingJournalChart.destroy();

                new Chart(document.getElementById('journalEntriesChart'), {
                    type: 'line',
                    data: {
                        labels: journalLabels,
                        datasets: [{
                            label: 'Journal Entries',
                            data: journalCounts,
                            fill: true,
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Journal Entries Over Time'
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                },
                                title: {
                                    display: true,
                                    text: 'Number of Entries'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Date'
                                }
                            }
                        }
                    }
                });

                // Emotion Distribution Chart
                const emotionLabels = Object.keys(data.emotion_distribution);
                const emotionCounts = Object.values(data.emotion_distribution);

                if (emotionLabels.length === 0) {
                    document.getElementById('emotionChart').parentElement.innerHTML = 
                        '<div class="no-data-message">No emotion data available yet</div>';
                } else {
                    // Destroy existing emotion chart if it exists
                    const existingEmotionChart = Chart.getChart('emotionChart');
                    if (existingEmotionChart) existingEmotionChart.destroy();

                    new Chart(document.getElementById('emotionChart'), {
                        type: 'pie',
                        data: {
                            labels: emotionLabels,
                            datasets: [{
                                data: emotionCounts,
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.8)',
                                    'rgba(54, 162, 235, 0.8)',
                                    'rgba(255, 206, 86, 0.8)',
                                    'rgba(75, 192, 192, 0.8)',
                                    'rgba(153, 102, 255, 0.8)',
                                    'rgba(255, 159, 64, 0.8)'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Emotion Distribution'
                                }
                            }
                        }
                    });
                }

                // Weekly Focus Time Chart
                if (data.weekly_focus_time.length === 0) {
                    document.getElementById('focusTimeChart').parentElement.innerHTML = 
                        '<div class="no-data-message">No focus time data available yet</div>';
                } else {
                    const focusTimeLabels = data.weekly_focus_time.map(item => {
                        const date = new Date(item.date);
                        return date.toLocaleDateString('en-US', { weekday: 'short' });
                    });
                    const focusTimeDurations = data.weekly_focus_time.map(item => item.total_focus_time);

                    // Destroy existing focus time chart if it exists
                    const existingFocusChart = Chart.getChart('focusTimeChart');
                    if (existingFocusChart) existingFocusChart.destroy();

                    new Chart(document.getElementById('focusTimeChart'), {
                        type: 'bar',
                        data: {
                            labels: focusTimeLabels,
                            datasets: [{
                                label: 'Focus Time (Minutes)',
                                data: focusTimeDurations,
                                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Minutes'
                                    }
                                }
                            }
                        }
                    });
                }

            } catch (error) {
                console.error('Error loading analytics:', error);
                // Display error messages in the widgets
                document.getElementById('total-journal-entries').textContent = 'Not available';
                document.getElementById('journalEntriesChart').parentElement.innerHTML = 
                    '<div class="no-data-message">Unable to load journal data</div>';
                document.getElementById('emotionChart').parentElement.innerHTML = 
                    '<div class="no-data-message">Unable to load emotion data</div>';
                document.getElementById('focusTimeChart').parentElement.innerHTML = 
                    '<div class="no-data-message">Unable to load focus time data</div>';
            }
        }

        // Initialize analytics on page load
        initializeAnalytics();
    });
    </script>

    <style>
    .no-data-message {
        text-align: center;
        padding: 20px;
        color: #666;
        font-style: italic;
    }

    .widget canvas {
        min-height: 300px;
    }

    .stats-header {
        text-align: center;
        margin-bottom: 20px;
        font-size: 1.2em;
    }

    .large-stat {
        font-size: 1.5em;
        font-weight: bold;
        color: #4a90e2;
    }
    </style>
</body>
</html>