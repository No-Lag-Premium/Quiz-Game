// ===== NUMERIQ GAME ENGINE =====

// ===== GAME STATE =====
const gameState = {
    currentScreen: 'modeScreen',
    gameMode: 'classic',
    difficulty: 'medium',
    score: 0,
    streak: 0,
    bestStreak: 0,
    questionsAnswered: 0,
    correctAnswers: 0,
    currentQuestion: 1,
    maxQuestions: 10,
    timeLeft: 60,
    lives: 3,
    timer: null,
    currentProblem: null,
    isPaused: false,
    isPractice: false,
    powerups: {
        time: 3,
        skip: 3,
        '5050': 3
    },
    hints: 3,
    achievements: [],
    unlockedAchievements: [],
    newlyUnlockedAchievements: [],
    soundEnabled: true,
    musicEnabled: true,
    hapticEnabled: true
};

// ===== DIFFICULTY SETTINGS =====
const difficultySettings = {
    easy: { min: 1, max: 10, operations: ['+', '-'] },
    medium: { min: 1, max: 50, operations: ['+', '-', '×'] },
    hard: { min: 1, max: 100, operations: ['+', '-', '×', '÷'] },
    expert: { min: 1, max: 1000, operations: ['+', '-', '×', '÷'] }
};

// ===== ACHIEVEMENTS DATA =====
const achievementsData = [
    { id: 'first_win', name: 'First Steps', desc: 'Complete your first game', icon: '👶', type: 'games', value: 1 },
    { id: 'games_10', name: 'Regular Player', desc: 'Play 10 games', icon: '🎮', type: 'games', value: 10 },
    { id: 'games_50', name: 'Dedicated', desc: 'Play 50 games', icon: '🕹️', type: 'games', value: 50 },
    { id: 'games_100', name: 'Centurion', desc: 'Play 100 games', icon: '💪', type: 'games', value: 100 },
    { id: 'streak_5', name: 'On Fire', desc: 'Reach a 5-question streak', icon: '🔥', type: 'streak', value: 5 },
    { id: 'streak_10', name: 'Unstoppable', desc: 'Reach a 10-question streak', icon: '⚡', type: 'streak', value: 10 },
    { id: 'streak_20', name: 'Legendary', desc: 'Reach a 20-question streak', icon: '👑', type: 'streak', value: 20 },
    { id: 'score_100', name: 'Century', desc: 'Score 100 points in one game', icon: '💯', type: 'score', value: 100 },
    { id: 'score_500', name: 'High Roller', desc: 'Score 500 points in one game', icon: '🎯', type: 'score', value: 500 },
    { id: 'score_1000', name: 'Math Master', desc: 'Score 1000 points in one game', icon: '🏆', type: 'score', value: 1000 },
    { id: 'accuracy_80', name: 'Sharp Shooter', desc: 'Achieve 80% accuracy', icon: '🎯', type: 'accuracy', value: 80 },
    { id: 'accuracy_90', name: 'Precision Master', desc: 'Achieve 90% accuracy', icon: '🎖️', type: 'accuracy', value: 90 },
    { id: 'perfect_game', name: 'Perfectionist', desc: 'Answer all questions correctly (100% accuracy)', icon: '✨', type: 'accuracy', value: 100 },
    { id: 'speed_demon', name: 'Speed Demon', desc: 'Answer 10 questions in 30 seconds in Time Attack', icon: '⚡', type: 'speed', value: 10 },
    { id: 'survivor', name: 'Survivor', desc: 'Reach 20 questions in Survival mode', icon: '❤️', type: 'survival', value: 20 },
    { id: 'expert_mode', name: 'Expert', desc: 'Complete a game on Expert difficulty', icon: '💀', type: 'difficulty', value: 1 }
];

// ===== RANKS =====
const ranks = [
    { min: 0, name: 'Novice', color: '#94a3b8' },
    { min: 100, name: 'Apprentice', color: '#22c55e' },
    { min: 300, name: 'Scholar', color: '#3b82f6' },
    { min: 600, name: 'Expert', color: '#f97316' },
    { min: 1000, name: 'Master', color: '#a855f7' },
    { min: 2000, name: 'Grandmaster', color: '#ec4899' },
    { min: 5000, name: 'Legend', color: '#fbbf24' }
];

// ===== SOUND EFFECTS =====
const sounds = {
    correct: null,
    wrong: null,
    click: null,
    powerup: null,
    achievement: null
};

// Initialize sounds
function initSounds() {
    if (typeof AudioContext !== 'undefined' || typeof webkitAudioContext !== 'undefined') {
        sounds.correct = () => playTone(800, 0.1, 'sine');
        sounds.wrong = () => playTone(200, 0.2, 'sawtooth');
        sounds.click = () => playTone(600, 0.05, 'sine');
        sounds.powerup = () => playTone(1000, 0.15, 'square');
        sounds.achievement = () => playMelody([523, 659, 784, 1047], 0.1);
    }
}

function playTone(frequency, duration, type = 'sine') {
    if (!gameState.soundEnabled) return;
    
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const ctx = new AudioContext();
        const oscillator = ctx.createOscillator();
        const gainNode = ctx.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(ctx.destination);
        
        oscillator.frequency.value = frequency;
        oscillator.type = type;
        
        gainNode.gain.setValueAtTime(0.3, ctx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + duration);
        
        oscillator.start(ctx.currentTime);
        oscillator.stop(ctx.currentTime + duration);
    } catch (e) {
        console.log('Audio not supported');
    }
}

function playMelody(frequencies, duration) {
    if (!gameState.soundEnabled) return;
    
    frequencies.forEach((freq, index) => {
        setTimeout(() => playTone(freq, duration), index * duration * 1000);
    });
}

function playSound(soundName) {
    if (sounds[soundName]) {
        sounds[soundName]();
    }
}

// ===== HAPTIC FEEDBACK =====
function hapticFeedback(type = 'light') {
    if (!gameState.hapticEnabled) return;
    if (!('vibrate' in navigator)) return;
    
    const patterns = {
        light: 10,
        medium: 20,
        heavy: 30,
        success: [10, 50, 10],
        error: [30, 50, 30],
        powerup: [20, 30, 20]
    };
    
    navigator.vibrate(patterns[type] || patterns.light);
}

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', () => {
    // Load configuration from PHP
    if (typeof GAME_CONFIG !== 'undefined') {
        gameState.soundEnabled = GAME_CONFIG.userSettings.sound_enabled;
        gameState.musicEnabled = GAME_CONFIG.userSettings.music_enabled;
        gameState.hapticEnabled = GAME_CONFIG.userSettings.haptic_enabled;
        gameState.source = GAME_CONFIG.source;
        gameState.selectMode = GAME_CONFIG.selectMode;
        gameState.isPractice = GAME_CONFIG.isPractice;
    }
    
    // Initialize sounds
    initSounds();
    
    // Initialize achievements
    initAchievements();
    
    // Setup event listeners
    setupEventListeners();
    
    // Update UI
    updateSoundButtons();
    
    // Handle Practice Mode - skip mode selection, go directly to difficulty
    if (gameState.isPractice) {
        gameState.gameMode = 'practice';
        showScreen('difficultyScreen');
        updateModeDisplay();
    }
    // Check if coming from Quick Play with pre-selected mode
    else {
        const preSelectedMode = localStorage.getItem('selectedMode');
        if (preSelectedMode && gameState.selectMode) {
            gameState.gameMode = preSelectedMode;
            gameState.isPractice = preSelectedMode === 'practice';
            showScreen('difficultyScreen');
            updateModeDisplay();
            localStorage.removeItem('selectedMode');
        }
    }
});

function setupEventListeners() {
    document.querySelectorAll('.diff-option').forEach(option => {
        option.addEventListener('click', () => {
            playSound('click');
            hapticFeedback('light');
            selectDifficulty(option.dataset.diff);
        });
    });
    
    document.getElementById('soundToggle').addEventListener('click', toggleSound);
    document.getElementById('musicToggle').addEventListener('click', toggleMusic);
}

function selectMode(mode) {
    gameState.gameMode = mode;
    gameState.isPractice = mode === 'practice';
    
    document.querySelectorAll('.mode-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    document.querySelector(`.mode-option[data-mode="${mode}"]`).classList.add('selected');
}

// ===== NEW FLOW FUNCTIONS =====
function selectGameMode(mode) {
    playSound('click');
    hapticFeedback('light');
    
    gameState.gameMode = mode;
    gameState.isPractice = mode === 'practice';
    
    showScreen('difficultyScreen');
    updateModeDisplay();
}

function updateModeDisplay() {
    const modeNames = {
        'classic': 'Classic',
        'timeAttack': 'Time Attack',
        'survival': 'Survival',
        'practice': 'Practice'
    };
    
    const modeDisplay = document.getElementById('selectedModeDisplay');
    if (modeDisplay) {
        modeDisplay.textContent = 'Mode: ' + (modeNames[gameState.gameMode] || 'Classic');
    }
}

function goBackToModeSelection() {
    playSound('click');
    hapticFeedback('light');
    showScreen('modeScreen');
}

function selectDifficulty(diff) {
    gameState.difficulty = diff;
    
    document.querySelectorAll('.diff-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    document.querySelector(`.diff-option[data-diff="${diff}"]`).classList.add('selected');
}

function updateSoundButtons() {
    const soundBtn = document.getElementById('soundToggle');
    const musicBtn = document.getElementById('musicToggle');
    
    soundBtn.innerHTML = `<i class="fas fa-volume-${gameState.soundEnabled ? 'up' : 'mute'}"></i>`;
    soundBtn.classList.toggle('active', gameState.soundEnabled);
    
    musicBtn.innerHTML = `<i class="fas fa-music"></i>`;
    musicBtn.classList.toggle('active', gameState.musicEnabled);
}

function toggleSound() {
    gameState.soundEnabled = !gameState.soundEnabled;
    updateSoundButtons();
    if (gameState.soundEnabled) playSound('click');
}

function toggleMusic() {
    gameState.musicEnabled = !gameState.musicEnabled;
    updateSoundButtons();
    if (gameState.soundEnabled) playSound('click');
}

// ===== SCREEN NAVIGATION =====
function showScreen(screenId) {
    document.querySelectorAll('.screen').forEach(screen => {
        screen.classList.remove('active');
    });
    document.getElementById(screenId).classList.add('active');
    gameState.currentScreen = screenId;
}

// ===== GAME INITIALIZATION =====
function startGame() {
    playSound('click');
    hapticFeedback('medium');
    
    // Reset game state
    gameState.score = 0;
    gameState.streak = 0;
    gameState.bestStreak = 0;
    gameState.questionsAnswered = 0;
    gameState.correctAnswers = 0;
    gameState.currentQuestion = 1;
    gameState.isPaused = false;
    gameState.powerups = { time: 3, skip: 3, '5050': 3 };
    gameState.hints = 3;
    gameState.unlockedAchievements = [];
    gameState.newlyUnlockedAchievements = [];
    
    // Mode-specific settings
    const settings = difficultySettings[gameState.difficulty];
    
    if (gameState.gameMode === 'classic') {
        gameState.maxQuestions = 10;
        gameState.timeLeft = 0;
        document.getElementById('timerContainer').style.display = 'none';
        document.getElementById('livesContainer').style.display = 'none';
    } else if (gameState.gameMode === 'timeAttack') {
        gameState.maxQuestions = Infinity;
        gameState.timeLeft = 60;
        document.getElementById('timerContainer').style.display = 'flex';
        document.getElementById('livesContainer').style.display = 'none';
        startTimer();
    } else if (gameState.gameMode === 'survival') {
        gameState.maxQuestions = Infinity;
        gameState.lives = 3;
        gameState.timeLeft = 0;
        document.getElementById('timerContainer').style.display = 'none';
        document.getElementById('livesContainer').style.display = 'flex';
    } else if (gameState.gameMode === 'practice') {
        gameState.maxQuestions = Infinity;
        gameState.timeLeft = 0;
        document.getElementById('timerContainer').style.display = 'none';
        document.getElementById('livesContainer').style.display = 'none';
    }
    
    updateHUD();
    updatePowerups();
    updateHintButton();
    generateQuestion();
    showScreen('gameScreen');
    
    // Show pause button when game starts
    const pauseBtn = document.getElementById('pauseBtn');
    if (pauseBtn) pauseBtn.style.display = 'flex';
    
    // Show practice banner if in practice mode
    const practiceBanner = document.getElementById('practiceBanner');
    if (practiceBanner) {
        practiceBanner.style.display = gameState.isPractice ? 'flex' : 'none';
    }
}

// ===== QUESTION GENERATION =====
function generateQuestion() {
    const settings = difficultySettings[gameState.difficulty];
    const operation = settings.operations[Math.floor(Math.random() * settings.operations.length)];
    
    let num1, num2, answer;
    
    switch (operation) {
        case '+':
            num1 = randomInt(settings.min, settings.max);
            num2 = randomInt(settings.min, settings.max);
            answer = num1 + num2;
            break;
        case '-':
            num1 = randomInt(settings.min, settings.max);
            num2 = randomInt(settings.min, num1);
            answer = num1 - num2;
            break;
        case '×':
            num1 = randomInt(settings.min, Math.min(settings.max, 20));
            num2 = randomInt(settings.min, Math.min(settings.max, 20));
            answer = num1 * num2;
            break;
        case '÷':
            num2 = randomInt(Math.max(2, settings.min), Math.min(settings.max, 20));
            answer = randomInt(settings.min, Math.min(settings.max, 20));
            num1 = num2 * answer;
            break;
    }
    
    gameState.currentProblem = { num1, num2, operation, answer };
    
    // Display question
    document.getElementById('question').textContent = `${num1} ${operation} ${num2} = ?`;
    
    // Generate options
    const options = generateOptions(answer);
    const optionButtons = document.querySelectorAll('.option-btn');
    optionButtons.forEach((btn, index) => {
        btn.textContent = options[index];
        btn.className = 'option-btn';
        btn.disabled = false;
    });
    
    // Update question counter
    updateQuestionCounter();
}

function generateOptions(correctAnswer) {
    const options = [correctAnswer];
    const range = Math.max(10, Math.abs(correctAnswer));
    
    while (options.length < 4) {
        const offset = randomInt(1, range);
        const wrongAnswer = Math.random() > 0.5 ? correctAnswer + offset : correctAnswer - offset;
        
        if (!options.includes(wrongAnswer) && wrongAnswer >= 0) {
            options.push(wrongAnswer);
        }
    }
    
    return shuffleArray(options);
}

function updateQuestionCounter() {
    const display = gameState.gameMode === 'classic' 
        ? `${gameState.currentQuestion}/${gameState.maxQuestions}`
        : gameState.currentQuestion;
    document.getElementById('questionDisplay').textContent = display;
}

// ===== ANSWER CHECKING =====
function checkAnswer(optionIndex) {
    const optionButtons = document.querySelectorAll('.option-btn');
    const selectedAnswer = parseInt(optionButtons[optionIndex].textContent);
    const correctAnswer = gameState.currentProblem.answer;
    const isCorrect = selectedAnswer === correctAnswer;
    
    gameState.questionsAnswered++;
    
    if (isCorrect) {
        gameState.correctAnswers++;
        gameState.streak++;
        if (gameState.streak > gameState.bestStreak) {
            gameState.bestStreak = gameState.streak;
        }
        
        // Calculate score with streak bonus and difficulty multiplier
        const basePoints = 10;
        const streakBonus = Math.floor(gameState.streak / 5) * 5;
        const difficultyMultiplier = { easy: 1, medium: 1.5, hard: 2, expert: 3 }[gameState.difficulty];
        const points = Math.floor((basePoints + streakBonus) * difficultyMultiplier);
        
        gameState.score += points;
        
        // Visual feedback
        optionButtons[optionIndex].classList.add('correct');
        showFeedback('✓', 'correct');
        
        // Sound and haptic
        playSound('correct');
        hapticFeedback('success');
    } else {
        gameState.streak = 0;
        optionButtons[optionIndex].classList.add('wrong');
        
        // Highlight correct answer
        optionButtons.forEach((btn, idx) => {
            if (parseInt(btn.textContent) === correctAnswer) {
                btn.classList.add('correct');
            }
        });
        
        showFeedback('✗', 'wrong');
        playSound('wrong');
        hapticFeedback('error');
        
        // Survival mode: lose a life
        if (gameState.gameMode === 'survival') {
            gameState.lives--;
            updateHUD();
            
            if (gameState.lives <= 0) {
                setTimeout(endGame, 1000);
                return;
            }
        }
    }
    
    updateHUD();
    updateComboBar();
    
    // Disable all buttons
    optionButtons.forEach(btn => btn.disabled = true);
    
    // Next question
    setTimeout(() => {
        if (gameState.gameMode === 'classic' && gameState.currentQuestion >= gameState.maxQuestions) {
            endGame();
        } else {
            gameState.currentQuestion++;
            generateQuestion();
        }
    }, 1000);
}

function showFeedback(text, type) {
    const feedback = document.getElementById('answerFeedback');
    feedback.textContent = text;
    feedback.className = `answer-feedback ${type} show`;
    
    setTimeout(() => {
        feedback.classList.remove('show');
    }, 500);
}

// ===== HINT SYSTEM =====
function useHint() {
    if (gameState.hints <= 0 || !gameState.currentProblem) return;
    
    playSound('powerup');
    hapticFeedback('powerup');
    
    gameState.hints--;
    updateHintButton();
    
    const correctAnswer = gameState.currentProblem.answer;
    const optionButtons = document.querySelectorAll('.option-btn');
    
    // Find wrong answers that haven't been disabled
    const wrongButtons = [];
    optionButtons.forEach(btn => {
        if (parseInt(btn.textContent) !== correctAnswer && !btn.disabled) {
            wrongButtons.push(btn);
        }
    });
    
    // Disable one wrong answer
    if (wrongButtons.length > 0) {
        const randomWrong = wrongButtons[Math.floor(Math.random() * wrongButtons.length)];
        randomWrong.classList.add('disabled');
        randomWrong.disabled = true;
    }
}

function updateHintButton() {
    const hintBtn = document.getElementById('hintBtn');
    const hintCount = document.getElementById('hintCount');
    
    hintCount.textContent = gameState.hints;
    hintBtn.disabled = gameState.hints <= 0;
}

// ===== HUD UPDATES =====
function updateHUD() {
    document.getElementById('scoreDisplay').textContent = gameState.score;
    document.getElementById('streakDisplay').textContent = gameState.streak;
    
    if (gameState.gameMode === 'timeAttack') {
        document.getElementById('timerDisplay').textContent = gameState.timeLeft;
    }
    
    if (gameState.gameMode === 'survival') {
        document.getElementById('livesDisplay').textContent = '❤️'.repeat(gameState.lives);
    }
}

function updateComboBar() {
    const comboFill = document.getElementById('comboFill');
    const comboText = document.getElementById('comboText');
    
    const comboPercent = Math.min((gameState.streak % 5) * 20, 100);
    comboFill.style.width = `${comboPercent}%`;
    
    if (gameState.streak >= 5) {
        const multiplier = Math.floor(gameState.streak / 5);
        comboText.textContent = `${multiplier}x MULTIPLIER!`;
    } else {
        comboText.textContent = '';
    }
}

function updatePowerups() {
    document.getElementById('timeCount').textContent = gameState.powerups.time;
    document.getElementById('skipCount').textContent = gameState.powerups.skip;
    document.getElementById('5050Count').textContent = gameState.powerups['5050'];
    
    document.getElementById('powerTime').disabled = gameState.powerups.time <= 0 || gameState.gameMode !== 'timeAttack';
    document.getElementById('powerSkip').disabled = gameState.powerups.skip <= 0;
    document.getElementById('power5050').disabled = gameState.powerups['5050'] <= 0;
}

// ===== POWER-UPS =====
function usePowerup(type) {
    if (gameState.powerups[type] <= 0) return;
    
    playSound('powerup');
    hapticFeedback('powerup');
    
    gameState.powerups[type]--;
    updatePowerups();
    
    switch (type) {
        case 'time':
            if (gameState.gameMode === 'timeAttack') {
                gameState.timeLeft += 15;
                updateHUD();
            }
            break;
        case 'skip':
            generateQuestion();
            break;
        case '5050':
            const optionButtons = document.querySelectorAll('.option-btn');
            const correctAnswer = gameState.currentProblem.answer;
            let removed = 0;
            
            optionButtons.forEach(btn => {
                if (removed < 2 && parseInt(btn.textContent) !== correctAnswer) {
                    btn.classList.add('disabled');
                    btn.disabled = true;
                    removed++;
                }
            });
            break;
    }
}

// ===== TIMER =====
function startTimer() {
    gameState.timer = setInterval(() => {
        if (!gameState.isPaused) {
            gameState.timeLeft--;
            updateHUD();
            
            if (gameState.timeLeft <= 0) {
                endGame();
            }
        }
    }, 1000);
}

// ===== PAUSE =====
function pauseGame() {
    gameState.isPaused = true;
    showScreen('pauseScreen');
}

function resumeGame() {
    gameState.isPaused = false;
    showScreen('gameScreen');
}

function restartGame() {
    clearInterval(gameState.timer);
    startGame();
}

// ===== QUIT CONFIRMATION =====
function showQuitConfirmation() {
    showScreen('quitConfirmScreen');
}

function hideQuitConfirmation() {
    showScreen('pauseScreen');
}

async function saveAndQuit() {
    // Save current game progress before quitting
    if (!gameState.isPractice && typeof GAME_CONFIG !== 'undefined' && GAME_CONFIG.isLoggedIn) {
        const gameData = {
            game_mode: gameState.gameMode,
            difficulty: gameState.difficulty,
            score: gameState.score,
            correct_answers: gameState.correctAnswers,
            total_questions: gameState.questionsAnswered,
            streak: gameState.bestStreak,
            is_practice: gameState.isPractice ? 1 : 0
        };
        
        try {
            await fetch('save-game.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(gameData)
            });
        } catch (error) {
            console.log('Error saving game:', error);
        }
    }
    
    // Redirect to dashboard or index
    window.location.href = typeof GAME_CONFIG !== 'undefined' && GAME_CONFIG.isLoggedIn ? 'dashboard.php' : 'index.php';
}

// ===== GAME OVER =====
async function endGame() {
    clearInterval(gameState.timer);
    
    // Calculate accuracy for this game
    const gameAccuracy = gameState.questionsAnswered > 0 
        ? Math.round((gameState.correctAnswers / gameState.questionsAnswered) * 100) 
        : 0;
    
    // Check achievements (but don't unlock in practice mode)
    if (!gameState.isPractice) {
        checkEndGameAchievements(gameAccuracy);
    }
    
    // Save to database if not practice mode and logged in
    let serverUnlockedAchievements = [];
    if (!gameState.isPractice && typeof GAME_CONFIG !== 'undefined' && GAME_CONFIG.isLoggedIn) {
        serverUnlockedAchievements = await saveGameToDatabase();
    }
    
    // Update results
    document.getElementById('finalScore').textContent = gameState.score;
    document.getElementById('bestStreak').textContent = gameState.bestStreak;
    document.getElementById('accuracy').textContent = `${gameAccuracy}%`;
    
    const rank = getRank(gameState.score);
    const rankElement = document.getElementById('rankDisplay');
    rankElement.textContent = rank.name;
    rankElement.style.color = rank.color;
    
    // Hide pause button when game ends
    const pauseBtn = document.getElementById('pauseBtn');
    if (pauseBtn) pauseBtn.style.display = 'none';
    
    showScreen('gameOverScreen');
}

function getRank(score) {
    for (let i = ranks.length - 1; i >= 0; i--) {
        if (score >= ranks[i].min) {
            return ranks[i];
        }
    }
    return ranks[0];
}

function playAgain() {
    // Hide pause button when returning to mode selection
    const pauseBtn = document.getElementById('pauseBtn');
    if (pauseBtn) pauseBtn.style.display = 'none';
    
    // Hide practice banner
    const practiceBanner = document.getElementById('practiceBanner');
    if (practiceBanner) practiceBanner.style.display = 'none';
    
    // Reset to mode selection screen
    showScreen('modeScreen');
}

// ===== ACHIEVEMENTS =====
function initAchievements() {
    // Load achievements from localStorage or initialize
    const saved = localStorage.getItem('numeriqAchievements');
    if (saved) {
        gameState.achievements = JSON.parse(saved);
    } else {
        gameState.achievements = achievementsData.map(a => ({ ...a, unlocked: false }));
    }
}

function checkEndGameAchievements(gameAccuracy) {
    // In practice mode, no achievements can be unlocked
    if (gameState.isPractice) {
        console.log('Practice mode - achievements not checked');
        return;
    }
    
    // First win - always unlock after first non-practice game
    unlockAchievement('first_win');
    
    // Score achievements (cascade)
    if (gameState.score >= 100) unlockAchievement('score_100');
    if (gameState.score >= 500) unlockAchievement('score_500');
    if (gameState.score >= 1000) unlockAchievement('score_1000');
    
    // Streak achievements (cascade)
    if (gameState.bestStreak >= 5) unlockAchievement('streak_5');
    if (gameState.bestStreak >= 10) unlockAchievement('streak_10');
    if (gameState.bestStreak >= 20) unlockAchievement('streak_20');
    
    // Accuracy achievements (cascade)
    if (gameAccuracy >= 80) unlockAchievement('accuracy_80');
    if (gameAccuracy >= 90) unlockAchievement('accuracy_90');
    if (gameAccuracy >= 100) unlockAchievement('perfect_game');
    
    // Speed demon (Time Attack with 10+ questions answered)
    if (gameState.gameMode === 'timeAttack' && gameState.questionsAnswered >= 10) {
        unlockAchievement('speed_demon');
    }
    
    // Survivor (Survival mode with 20+ questions)
    if (gameState.gameMode === 'survival' && gameState.currentQuestion >= 20) {
        unlockAchievement('survivor');
    }
    
    // Expert mode (completed game on expert difficulty)
    if (gameState.difficulty === 'expert') {
        unlockAchievement('expert_mode');
    }
}

function unlockAchievement(achievementId) {
    // Do not unlock achievements in practice mode
    if (gameState.isPractice) {
        console.log('Practice mode - achievement not unlocked:', achievementId);
        return;
    }
    
    const achievement = gameState.achievements.find(a => a.id === achievementId);
    if (achievement && !achievement.unlocked) {
        achievement.unlocked = true;
        gameState.unlockedAchievements.push(achievement);
        gameState.newlyUnlockedAchievements.push(achievement);
        
        // Save to localStorage
        localStorage.setItem('numeriqAchievements', JSON.stringify(gameState.achievements));
        
        console.log('Achievement unlocked:', achievementId);
    }
}

// ===== SAVE TO DATABASE =====
async function saveGameToDatabase() {
    const gameData = {
        game_mode: gameState.gameMode,
        difficulty: gameState.difficulty,
        score: gameState.score,
        correct_answers: gameState.correctAnswers,
        total_questions: gameState.questionsAnswered,
        streak: gameState.bestStreak,
        is_practice: gameState.isPractice ? 1 : 0
    };
    
    try {
        const response = await fetch('save-game.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(gameData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('Game saved to database');
            // Handle unlocked achievements from server
            if (data.unlocked_achievements && data.unlocked_achievements.length > 0) {
                data.unlocked_achievements.forEach(ach => {
                    const localAch = gameState.achievements.find(a => a.id === ach.id);
                    if (localAch && !localAch.unlocked) {
                        localAch.unlocked = true;
                        gameState.unlockedAchievements.push(localAch);
                    }
                });
                // Save to localStorage
                localStorage.setItem('numeriqAchievements', JSON.stringify(gameState.achievements));
            }
            return data.unlocked_achievements || [];
        } else {
            console.log('Error saving game:', data.message);
        }
    } catch (error) {
        console.log('Error saving game:', error);
    }
    return [];
}

// ===== UTILITIES =====
function randomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function shuffleArray(array) {
    const newArray = [...array];
    for (let i = newArray.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [newArray[i], newArray[j]] = [newArray[j], newArray[i]];
    }
    return newArray;
}
