<?php
session_start();

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$gameMode = isset($_GET['mode']) ? $_GET['mode'] : '';
$selectMode = isset($_GET['select']) && $_GET['select'] === 'mode';
$source = isset($_GET['source']) ? $_GET['source'] : 'sidebar';
$isPractice = $gameMode === 'practice';

// If not logged in and not practice mode and trying to play (not select mode), redirect to login
if (!$isLoggedIn && !$isPractice && !$selectMode && !empty($gameMode)) {
    header("Location: index.php?login_required=1");
    exit();
}

// For Practice Mode, always go through select mode flow
if ($isPractice && !$selectMode) {
    header("Location: game.php?mode=practice&select=mode");
    exit();
}

// Get user settings if logged in
$userSettings = [
    'sound_enabled' => true,
    'music_enabled' => true,
    'haptic_enabled' => true,
    'theme' => 'dark',
    'hints_enabled' => true
];

if ($isLoggedIn) {
    include 'userdb.php';
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT * FROM user_settings WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $userSettings = $row;
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $userSettings['theme']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play Game - NumerIQ</title>
    <link rel="stylesheet" href="game-styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=VT323&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body data-source="<?php echo $source; ?>" data-select-mode="<?php echo $selectMode ? '1' : '0'; ?>" data-is-practice="<?php echo $isPractice ? '1' : '0'; ?>">
    <!-- Game Header -->
    <header class="game-header">
        <a href="<?php echo $isLoggedIn ? 'dashboard.php' : 'index.php'; ?>" class="header-logo">
            <span class="logo-math">Numer</span><span class="logo-quest">IQ</span>
        </a>
        <div class="header-controls">
            <button class="control-btn" id="soundToggle" title="Toggle Sound">
                <i class="fas fa-volume-up"></i>
            </button>
            <button class="control-btn" id="musicToggle" title="Toggle Music">
                <i class="fas fa-music"></i>
            </button>
            <button class="control-btn" id="pauseBtn" onclick="pauseGame()" title="Pause" style="display: none;">
                <i class="fas fa-pause"></i>
            </button>
        </div>
    </header>

    <!-- Game Container -->
    <div class="game-container">
        <!-- Mode Selection Screen (Sidebar Flow - Step 1) -->
        <div id="modeScreen" class="screen active">
            <h2 class="screen-title">Select Game Mode</h2>
            <div class="mode-selection mode-selection-4col">
                <div class="mode-option" data-mode="classic" onclick="selectGameMode('classic')">
                    <div class="mode-icon"><i class="fas fa-book"></i></div>
                    <h3>Classic</h3>
                    <p>10 questions, no time limit</p>
                </div>
                <div class="mode-option" data-mode="timeAttack" onclick="selectGameMode('timeAttack')">
                    <div class="mode-icon"><i class="fas fa-bolt"></i></div>
                    <h3>Time Attack</h3>
                    <p>60 seconds, answer as many as you can</p>
                </div>
                <div class="mode-option" data-mode="survival" onclick="selectGameMode('survival')">
                    <div class="mode-icon"><i class="fas fa-heart"></i></div>
                    <h3>Survival</h3>
                    <p>3 lives, survive as long as you can</p>
                </div>
            </div>
            
            <div class="mode-actions" style="margin-top: 40px;">
                <a href="<?php echo $isLoggedIn ? 'dashboard.php' : 'index.php'; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <!-- Difficulty Selection Screen (Both Flows - Step 2) -->
        <div id="difficultyScreen" class="screen">
            <h2 class="screen-title">Select Difficulty</h2>
            <p class="screen-subtitle" id="selectedModeDisplay">Mode: Classic</p>
            
            <div class="difficulty-selection">
                <button class="diff-option easy" data-diff="easy" onclick="selectDifficulty('easy')">
                    <i class="fas fa-seedling"></i>
                    <span>Easy</span>
                    <small>Numbers 1-10</small>
                </button>
                <button class="diff-option medium selected" data-diff="medium" onclick="selectDifficulty('medium')">
                    <i class="fas fa-leaf"></i>
                    <span>Medium</span>
                    <small>Numbers 1-50</small>
                </button>
                <button class="diff-option hard" data-diff="hard" onclick="selectDifficulty('hard')">
                    <i class="fas fa-fire"></i>
                    <span>Hard</span>
                    <small>Numbers 1-100</small>
                </button>
                <button class="diff-option expert" data-diff="expert" onclick="selectDifficulty('expert')">
                    <i class="fas fa-skull"></i>
                    <span>Expert</span>
                    <small>Numbers 1-1000</small>
                </button>
            </div>
            
            <div class="mode-actions" style="margin-top: 40px;">
                <button class="btn btn-secondary" onclick="goBackToModeSelection()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button class="btn btn-primary btn-lg" onclick="startGame()">
                    <i class="fas fa-play"></i> Start Game
                </button>
            </div>
        </div>

        <!-- Game Screen -->
        <div id="gameScreen" class="screen">
            <!-- Practice Mode Banner -->
            <div id="practiceBanner" class="practice-banner" style="display: none;">
                <i class="fas fa-graduation-cap"></i>
                <span>Practice Mode - Stats are not saved</span>
            </div>

            <!-- HUD -->
            <div class="game-hud">
                <div class="hud-item">
                    <span class="hud-label">Score</span>
                    <span id="scoreDisplay" class="hud-value">0</span>
                </div>
                <div class="hud-item">
                    <span class="hud-label">Streak</span>
                    <span id="streakDisplay" class="hud-value streak">0</span>
                </div>
                <div class="hud-item" id="timerContainer">
                    <span class="hud-label">Time</span>
                    <span id="timerDisplay" class="hud-value">60</span>
                </div>
                <div class="hud-item" id="livesContainer">
                    <span class="hud-label">Lives</span>
                    <span id="livesDisplay" class="hud-value">❤️❤️❤️</span>
                </div>
                <div class="hud-item">
                    <span class="hud-label">Question</span>
                    <span id="questionDisplay" class="hud-value">1/10</span>
                </div>
            </div>

            <!-- Combo Bar -->
            <div class="combo-container">
                <div class="combo-bar">
                    <div id="comboFill" class="combo-fill"></div>
                </div>
                <div id="comboText" class="combo-text"></div>
            </div>

            <!-- Question Area -->
            <div class="question-container">
                <div id="question" class="question">Loading...</div>
                <div class="answer-feedback" id="answerFeedback"></div>
                
                <!-- Hint Button -->
                <button class="hint-btn" id="hintBtn" onclick="useHint()" title="Get a hint">
                    <i class="fas fa-lightbulb"></i>
                    <span id="hintCount">3</span>
                </button>
            </div>

            <!-- Answer Options -->
            <div id="options" class="options-grid">
                <button class="option-btn" onclick="checkAnswer(0)"></button>
                <button class="option-btn" onclick="checkAnswer(1)"></button>
                <button class="option-btn" onclick="checkAnswer(2)"></button>
                <button class="option-btn" onclick="checkAnswer(3)"></button>
            </div>

            <!-- Power-ups -->
            <div class="powerups">
                <button id="powerTime" class="powerup-btn" onclick="usePowerup('time')">
                    <span class="powerup-icon"><i class="fas fa-clock"></i></span>
                    <span class="powerup-count" id="timeCount">3</span>
                </button>
                <button id="powerSkip" class="powerup-btn" onclick="usePowerup('skip')">
                    <span class="powerup-icon"><i class="fas fa-forward"></i></span>
                    <span class="powerup-count" id="skipCount">3</span>
                </button>
                <button id="power5050" class="powerup-btn" onclick="usePowerup('5050')">
                    <span class="powerup-icon">50/50</span>
                    <span class="powerup-count" id="5050Count">3</span>
                </button>
            </div>
        </div>

        <!-- Pause Screen -->
        <div id="pauseScreen" class="screen overlay">
            <div class="pause-content">
                <h2><i class="fas fa-pause"></i> Game Paused</h2>
                <button class="btn btn-primary" onclick="resumeGame()">
                    <i class="fas fa-play"></i> Resume
                </button>
                <button class="btn btn-secondary" onclick="restartGame()">
                    <i class="fas fa-redo"></i> Restart
                </button>
                <button class="btn btn-secondary" onclick="showQuitConfirmation()">
                    <i class="fas fa-sign-out-alt"></i> Quit
                </button>
            </div>
        </div>

        <!-- Quit Confirmation Screen -->
        <div id="quitConfirmScreen" class="screen overlay">
            <div class="quit-confirm-content">
                <h2><i class="fas fa-sign-out-alt"></i> Quit?</h2>
                <div class="quit-options">
                    <button class="btn btn-primary" onclick="saveAndQuit()">
                        <i class="fas fa-save"></i> Save & Quit
                    </button>
                    <a href="<?php echo $isLoggedIn ? 'dashboard.php' : 'index.php'; ?>" class="btn btn-danger">
                        <i class="fas fa-times"></i> Quit (Lose Progress)
                    </a>
                    <button class="btn btn-secondary" onclick="hideQuitConfirmation()">
                        <i class="fas fa-arrow-left"></i> Keep Playing
                    </button>
                </div>
            </div>
        </div>

        <!-- Game Over Screen -->
        <div id="gameOverScreen" class="screen overlay">
            <div class="gameover-content">
                <h2 id="resultTitle">Game Over!</h2>
                
                <div class="result-stats">
                    <div class="result-item">
                        <span class="result-label">Final Score</span>
                        <span id="finalScore" class="result-value">0</span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">Best Streak</span>
                        <span id="bestStreak" class="result-value">0</span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">Accuracy</span>
                        <span id="accuracy" class="result-value">0%</span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">Rank</span>
                        <span id="rankDisplay" class="result-value rank">Novice</span>
                    </div>
                </div>

                <div class="result-buttons">
                    <button class="btn btn-primary" onclick="playAgain()">
                        <i class="fas fa-redo"></i> Play Again
                    </button>
                    <a href="<?php echo $isLoggedIn ? 'dashboard.php' : 'index.php'; ?>" class="btn btn-secondary">
                        <i class="fas fa-<?php echo $isLoggedIn ? 'chart-bar' : 'home'; ?>"></i> 
                        <?php echo $isLoggedIn ? 'Dashboard' : 'Main Menu'; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Audio Elements -->
    <audio id="soundCorrect" preload="auto">
        <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZURE" type="audio/wav">
    </audio>
    <audio id="soundWrong" preload="auto">
        <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZURE" type="audio/wav">
    </audio>

    <script>
        // Pass PHP variables to JavaScript
        const GAME_CONFIG = {
            isLoggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>,
            userSettings: <?php echo json_encode($userSettings); ?>,
            source: '<?php echo $source; ?>',
            selectMode: <?php echo $selectMode ? 'true' : 'false'; ?>,
            isPractice: <?php echo $isPractice ? 'true' : 'false'; ?>,
            redirectUrl: '<?php echo $isLoggedIn ? 'dashboard.php' : 'index.php'; ?>'
        };
    </script>
    <script src="game.js"></script>
</body>
</html>
