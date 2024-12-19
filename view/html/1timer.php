<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'timerphp.php';

SessionManager::start();
SessionManager::requireLogin();

$db = Database::getInstance();

// Handle AJAX requests (GET and POST)
if (isset($_GET['action']) || isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Handle GET requests
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
        if ($_GET['action'] === 'get_boards') {
            try {
                $userId = $_SESSION['user_id'];
                $sql = "SELECT * FROM vision_boards WHERE user_id = ? ORDER BY created_at DESC";
                $stmt = $db->conn->prepare($sql);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $boards = [];

                while ($board = $result->fetch_assoc()) {
                    // Get images for this board
                    $imagesSql = "SELECT * FROM board_images WHERE board_id = ?";
                    $imagesStmt = $db->conn->prepare($imagesSql);
                    $imagesStmt->bind_param("i", $board['board_id']);
                    $imagesStmt->execute();
                    $imagesResult = $imagesStmt->get_result();
                    
                    $board['images'] = [];
                    while ($image = $imagesResult->fetch_assoc()) {
                        $board['images'][] = $image;
                    }
                    
                    $boards[] = $board;
                }
                
                echo json_encode(['success' => true, 'boards' => $boards]);
                exit;
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
        }
    }

    // Handle POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // $response = ['success' => false, 'error' => ''];
        
        if (!SessionManager::isLoggedIn()) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create_board':
                try {
                    $title = $_POST['title'] ?? '';
                    $description = $_POST['description'] ?? '';
                    
                    $sql = "INSERT INTO vision_boards (user_id, title, description) VALUES (?, ?, ?)";
                    $stmt = $db->conn->prepare($sql);
                    $stmt->bind_param("iss", $_SESSION['user_id'], $title, $description);
                    
                    if ($stmt->execute()) {
                        $boardId = $db->conn->insert_id;
                        $response['success'] = true;
                        $response['board_id'] = $boardId;
                        
                        // Handle image uploads
                        if (isset($_FILES['images'])) {
                            $uploadDir = 'uploads/vision_boards/';
                            if (!file_exists($uploadDir)) {
                                mkdir($uploadDir, 0777, true);
                            }
                            
                            $uploadedImages = [];
                            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                                $fileName = uniqid() . '_' . $_FILES['images']['name'][$key];
                                $targetPath = $uploadDir . $fileName;
                                
                                if (move_uploaded_file($tmp_name, $targetPath)) {
                                    // Save image info to database
                                    $sql = "INSERT INTO board_images (board_id, image_path, image_name) VALUES (?, ?, ?)";
                                    $stmt = $db->conn->prepare($sql);
                                    $stmt->bind_param("iss", $boardId, $targetPath, $fileName);
                                    $stmt->execute();
                                    $uploadedImages[] = $fileName;
                                }
                            }
                            $response['uploaded_images'] = $uploadedImages;
                        }
                    } else {
                        $response['error'] = 'Failed to create board';
                    }
                } catch (Exception $e) {
                    $response['error'] = 'Error: ' . $e->getMessage();
                }
                echo json_encode($response);
                exit;
                break;
            
                case 'log_session':
                    try {
                        $userId = $_SESSION['user_id'];
                        $mode = $_POST['mode'] ?? 'Pomodoro';
                        $duration = (int)$_POST['duration'];
                        
                        // Use Database class instead of $conn
                        $db = Database::getInstance();
                        $sql = "INSERT INTO timer_sessions (user_id, mode_type, duration) VALUES (?, ?, ?)";
                        $stmt = $db->prepare($sql);
                        $stmt->bind_param("isi", $userId, $mode, $duration);
                        
                        if ($stmt->execute()) {
                            // Get recent sessions for the user
                            $recentSql = "SELECT * FROM timer_sessions 
                                         WHERE user_id = ? 
                                         ORDER BY completed_at DESC 
                                         LIMIT 5";
                            $recentStmt = $db->prepare($recentSql);
                            $recentStmt->bind_param("i", $userId);
                            $recentStmt->execute();
                            $result = $recentStmt->get_result();
                            $sessions = $result->fetch_all(MYSQLI_ASSOC);
                            
                            $response = [
                                'success' => true,
                                'sessions' => $sessions
                            ];
                        } else {
                            $response = [
                                'success' => false,
                                'error' => 'Failed to log session'
                            ];
                        }
                    } catch (Exception $e) {
                        $response = [
                            'success' => false,
                            'error' => 'Error: ' . $e->getMessage()
                        ];
                    }
                    echo json_encode($response);
                    break;
                
                    case 'update_preferences':
                        try {
                            $userId = $_SESSION['user_id'];
                            $defaultMode = $_POST['default_mode'] ?? 'Pomodoro';
                            $defaultDuration = (int)($_POST['default_duration'] ?? 1500);
                            $soundEnabled = (int)($_POST['sound_enabled'] ?? 1);
                            $lastMeditationMode = $_POST['last_meditation_mode'] ?? 'breathing';
                    
                            $db = Database::getInstance();
                            
                            // Check if preferences exist
                            $checkSql = "SELECT preference_id FROM timer_preferences WHERE user_id = ?";
                            $checkStmt = $db->prepare($checkSql);
                            $checkStmt->bind_param("i", $userId);
                            $checkStmt->execute();
                            $result = $checkStmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                // Update existing
                                $sql = "UPDATE timer_preferences 
                                       SET default_mode = ?, 
                                           default_duration = ?, 
                                           sound_enabled = ?, 
                                           last_meditation_mode = ? 
                                       WHERE user_id = ?";
                                $stmt = $db->prepare($sql);
                                $stmt->bind_param("siisi", $defaultMode, $defaultDuration, $soundEnabled, 
                                                $lastMeditationMode, $userId);
                            } else {
                                // Insert new
                                $sql = "INSERT INTO timer_preferences 
                                       (user_id, default_mode, default_duration, sound_enabled, last_meditation_mode) 
                                       VALUES (?, ?, ?, ?, ?)";
                                $stmt = $db->prepare($sql);
                                $stmt->bind_param("isiis", $userId, $defaultMode, $defaultDuration, 
                                                $soundEnabled, $lastMeditationMode);
                            }
                    
                            if ($stmt->execute()) {
                                $response = [
                                    'success' => true,
                                    'preferences' => [
                                        'default_mode' => $defaultMode,
                                        'default_duration' => $defaultDuration,
                                        'sound_enabled' => $soundEnabled,
                                        'last_meditation_mode' => $lastMeditationMode
                                    ]
                                ];
                            } else {
                                $response = [
                                    'success' => false,
                                    'error' => 'Failed to update preferences'
                                ];
                            }
                        } catch (Exception $e) {
                            $response = [
                                'success' => false,
                                'error' => 'Error: ' . $e->getMessage()
                            ];
                        }
                        echo json_encode($response);
                        break;

            case 'get_preferences':
                $response['success'] = true;
                $response['preferences'] = $db->getUserPreferences($_SESSION['user_id']);
                break;
        }
        
        echo json_encode($response);
        exit;
    }
}

// The rest of your HTML and JavaScript code remains exactly the same...
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habitude Timer</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide-static/0.321.0/lucide.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Keep your existing CSS file -->
    <link rel="stylesheet" href="../../assets/css/1timercss.css">
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
    <div class="container">
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
        <a href="1timer.php" class="nav-link active">
            <i data-lucide="timer"></i> Timer
        </a>
        <a href="1visionboard.php" class="nav-link">
            <i data-lucide="target"></i> Vision Board
        </a>
        <form action="logout_user.php" method="POST" style="margin-top: 10px;">
    <button type="submit" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
    </button>
</form>
    </nav>

        <main class="main-content">
            <section class="timer-section">
                <div class="timer-container">
                    <div class="timer-display" id="timerDisplay">25:00</div>
                    <div class="custom-time-input">
                        <input type="number" id="customMinutes" min="1" max="180" placeholder="Minutes">
                        <button class="btn btn-secondary" id="setCustomTime">Set Time</button>
                    </div>
                    <div class="timer-controls">
                        <button class="btn" id="startBtn">
                            <i data-lucide="play"></i> Start
                        </button>
                        <button class="btn btn-secondary" id="pauseBtn" disabled>
                            <i data-lucide="pause"></i> Pause
                        </button>
                        <button class="btn" id="resetBtn">
                            <i data-lucide="refresh-cw"></i> Reset
                        </button>
                    </div>
                    <div class="timer-presets">
                        <button class="preset-btn active" data-time="25">Pomodoro</button>
                        <button class="preset-btn" data-time="15">Short Break</button>
                        <button class="preset-btn" data-time="45">Long Break</button>
                    </div>
                </div>
            </section>

            <section class="meditation-section">
                <h2>Meditation Modes- Coming Soon</h2>
                <div class="meditation-modes">
                    <div class="meditation-mode" data-mode="breathing">
                        <div class="mode-icon">
                            <i data-lucide="wind"></i>
                        </div>
                        <h3>Breathing</h3>
                        <p>Focus on deep, rhythmic breathing</p>
                    </div>
                    <div class="meditation-mode" data-mode="mindfulness">
                        <div class="mode-icon">
                            <i data-lucide="brain"></i>
                        </div>
                        <h3>Mindfulness</h3>
                        <p>Present moment awareness</p>
                    </div>
                    <div class="meditation-mode" data-mode="body-scan">
                        <div class="mode-icon">
                            <i data-lucide="activity"></i>
                        </div>
                        <h3>Body Scan</h3>
                        <p>Systematic body relaxation</p>
                    </div>
                    <div class="meditation-mode" data-mode="loving-kindness">
                        <div class="mode-icon">
                            <i data-lucide="heart"></i>
                        </div>
                        <h3>Loving-Kindness</h3>
                        <p>Cultivate compassion</p>
                    </div>
                </div>
            </section>

            <section class="log-section">
                <h2>Session Log</h2>
                <div class="session-log" id="sessionLog">
                    <!-- Session history will be logged here -->
                </div>
            </section>
        </main>
    </div>

    <!-- Audio for alarm -->
    <audio id="alarmSound" preload="auto">
        <source src="https://actions.google.com/sounds/v1/alarms/alarm_clock.ogg" type="audio/ogg">
        <source src="https://actions.google.com/sounds/v1/alarms/beeping_alarm_clock.ogg" type="audio/ogg">
    </audio>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        let presetBtns;
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();

            // Timer Elements
            const timerDisplay = document.getElementById('timerDisplay');
            const startBtn = document.getElementById('startBtn');
            const pauseBtn = document.getElementById('pauseBtn');
            const resetBtn = document.getElementById('resetBtn');
            const customMinutes = document.getElementById('customMinutes');
            const setCustomTimeBtn = document.getElementById('setCustomTime');
            presetBtns = document.querySelectorAll('.preset-btn');
            const meditationModes = document.querySelectorAll('.meditation-mode');
            const sessionLog = document.getElementById('sessionLog');
            const alarmSound = document.getElementById('alarmSound');

            let interval;
            let timeLeft = 25 * 60; // Default 25 minutes
            let isRunning = false;
            let selectedMode = 'Pomodoro';
            let originalTime = timeLeft;
            let actualTimeSpent = 0;
            let startTime = null;

            // Format time for display
            function formatTime(seconds) {
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = seconds % 60;
                return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
            }

            // Update Timer Display
            function updateDisplay() {
                timerDisplay.textContent = formatTime(timeLeft);
                document.title = `${formatTime(timeLeft)} - Habitude Timer`;
            }

            // Start Timer
            function startTimer() {
    if (!isRunning && timeLeft > 0) {
        isRunning = true;
        startBtn.disabled = true;
        pauseBtn.disabled = false;
        
        // Record start time if not already set
        if (!startTime) {
            startTime = Date.now();
        }

        interval = setInterval(() => {
            if (timeLeft > 0) {
                timeLeft--;
                // Calculate actual time spent
                actualTimeSpent = Math.floor((originalTime - timeLeft));
                updateDisplay();
                if (timeLeft <= 10) {
                    timerDisplay.style.color = 'var(--error)';
                }
            } else {
                completeTimer();
            }
        }, 1000);
    }
}


            // Pause Timer
            function pauseTimer() {
            if (isRunning) {
                clearInterval(interval);
                isRunning = false;
                startBtn.disabled = false;
                pauseBtn.disabled = true;
            }
        }

            // Reset Timer
            function resetTimer() {
    clearInterval(interval);
    isRunning = false;
    timeLeft = originalTime;
    actualTimeSpent = 0;
    startTime = null;
    startBtn.disabled = false;
    pauseBtn.disabled = true;
    timerDisplay.style.color = 'transparent';
    updateDisplay();
}
            // Complete Timer
            // Update completeTimer function
            function completeTimer() {
    clearInterval(interval);
    isRunning = false;
    startBtn.disabled = false;
    pauseBtn.disabled = true;
    playAlarm();
    
    // Log the session with actual time spent
    fetch('1timer.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=log_session&mode=${selectedMode}&duration=${actualTimeSpent}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.sessions) {
            updateSessionLog(data.sessions);
        }
    })
    .catch(error => console.error('Error logging session:', error));

    // Reset timers
    timeLeft = originalTime;
    actualTimeSpent = 0;
    startTime = null;
    updateDisplay();
}
// Add this helper function
function updateSessionLog(sessions) {
    sessionLog.innerHTML = '';
    sessions.forEach(session => {
        const logEntry = document.createElement('div');
        const date = new Date(session.completed_at);
        logEntry.innerHTML = `
            <strong>${session.mode_type}</strong> - ${formatTime(session.duration)} session completed
            <br>
            <small>${date.toLocaleString()}</small>
        `;
        sessionLog.appendChild(logEntry);
    });
}
            // Play Alarm
            function playAlarm() {
                alarmSound.play();
                setTimeout(() => {
                    alarmSound.pause();
                    alarmSound.currentTime = 0;
                }, 3000);
            }

            // Log Session
            function logSession() {
                const logEntry = document.createElement('div');
                const date = new Date();
                logEntry.innerHTML = `
                    <strong>${selectedMode}</strong> - ${formatTime(originalTime)} session completed
                    <br>
                    <small>${date.toLocaleString()}</small>
                `;
                sessionLog.insertBefore(logEntry, sessionLog.firstChild);

                // Keep only last 5 entries
                while (sessionLog.children.length > 5) {
                    sessionLog.removeChild(sessionLog.lastChild);
                }
            }

            // Set Custom Time
            function setCustomTime() {
                const minutes = parseInt(customMinutes.value);
                if (minutes && minutes > 0 && minutes <= 180) {
                    timeLeft = minutes * 60;
                    originalTime = timeLeft;
                    updateDisplay();
                    customMinutes.value = '';
                    resetActivePresets();
                }
            }

            // Reset Active Preset Buttons
            function resetActivePresets() {
                presetBtns.forEach(btn => btn.classList.remove('active'));
            }

            // Event Listeners
            startBtn.addEventListener('click', startTimer);
            pauseBtn.addEventListener('click', pauseTimer);
            resetBtn.addEventListener('click', resetTimer);
            setCustomTimeBtn.addEventListener('click', setCustomTime);

            // Custom time input
            customMinutes.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    setCustomTime();
                }
            });

            // Preset Buttons
            presetBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    resetActivePresets();
                    btn.classList.add('active');
                    timeLeft = parseInt(btn.dataset.time) * 60;
                    originalTime = timeLeft;
                    selectedMode = btn.textContent;
                    updateDisplay();
                    savePreferences();
                });
            });

            // Meditation Modes
            meditationModes.forEach(mode => {
                mode.addEventListener('click', () => {
                    meditationModes.forEach(m => m.classList.remove('active'));
                    mode.classList.add('active');
                    selectedMode = mode.querySelector('h3').textContent;
                    savePreferences();
                });
            });

            // Initialize display
            loadPreferences();
            updateDisplay();
        });

        // Add these functions to your existing JavaScript

// Load user preferences
async function loadPreferences() {
    try {
        const response = await fetch('1timer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_preferences'
        });
        const data = await response.json();
        
        if (data.success && data.preferences) {
            // Apply preferences
            timeLeft = data.preferences.default_duration;
            originalTime = timeLeft;
            selectedMode = data.preferences.default_mode;
            
            // Update UI
            updateDisplay();
            presetBtns.forEach(btn => {
                if (btn.textContent === selectedMode) {
                    resetActivePresets();
                    btn.classList.add('active');
                }
            });
            
            // Set meditation mode
            meditationModes.forEach(mode => {
                if (mode.dataset.mode === data.preferences.last_meditation_mode) {
                    mode.classList.add('active');
                }
            });
        }
    } catch (error) {
        console.error('Error loading preferences:', error);
    }
}

// Log completed session
async function logSession() {
    try {
        const response = await fetch('1timer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=log_session&mode=${selectedMode}&duration=${originalTime}`
        });
        const data = await response.json();
        
        if (data.success && data.sessions) {
            // Update session log UI
            sessionLog.innerHTML = '';
            data.sessions.forEach(session => {
                const logEntry = document.createElement('div');
                const date = new Date(session.completed_at);
                logEntry.innerHTML = `
                    <strong>${session.mode_type}</strong> - ${formatTime(session.duration)} session completed
                    <br>
                    <small>${date.toLocaleString()}</small>
                `;
                sessionLog.appendChild(logEntry);
            });
        }
    } catch (error) {
        console.error('Error logging session:', error);
    }
}

// Save preferences
async function savePreferences() {
    try {
        const activeMeditation = document.querySelector('.meditation-mode.active');
        const preferences = {
            default_mode: selectedMode,
            default_duration: originalTime,
            sound_enabled: true,
            last_meditation_mode: activeMeditation ? activeMeditation.dataset.mode : 'breathing'
        };
        
        await fetch('1timer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_preferences&` + new URLSearchParams(preferences).toString()
        });
    } catch (error) {
        console.error('Error saving preferences:', error);
    }
}

// Update the completeTimer function


// Add a function to update preferences
async function updatePreferences() {
    try {
        const activeMeditation = document.querySelector('.meditation-mode.active');
        const formData = new FormData();
        formData.append('action', 'update_preferences');
        formData.append('default_mode', selectedMode);
        formData.append('default_duration', originalTime);
        formData.append('sound_enabled', '1');
        formData.append('last_meditation_mode', activeMeditation ? activeMeditation.dataset.mode : 'breathing');

        const response = await fetch('1timer.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (!data.success) {
            console.error('Failed to update preferences:', data.error);
        }
    } catch (error) {
        console.error('Error updating preferences:', error);
    }
}

// Add event listeners to update preferences when changed
presetBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        // ... existing preset button code ...
        updatePreferences();
    });
});

meditationModes.forEach(mode => {
    mode.addEventListener('click', () => {
        // ... existing meditation mode code ...
        updatePreferences();
    });
});

// Load preferences when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadPreferences();
    // ... rest of your existing DOMContentLoaded code
});

// Save preferences when changed
presetBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        // ... existing preset button code ...
        savePreferences();
    });
});

meditationModes.forEach(mode => {
    mode.addEventListener('click', () => {
        // ... existing meditation mode code ...
        savePreferences();
    });
});
    </script>
</body>
</html>