<?php
session_start();
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Include database for real stats
include 'userdb.php';

// Get real stats from database
$stats = [
    'total_players' => 0,
    'games_played' => 0,
    'problems_solved' => 0,
    'high_score' => 0
];

// Check if tables exist and get stats
$table_check = $conn->query("SHOW TABLES LIKE 'users'");
if ($table_check->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $stats['total_players'] = $result->fetch_assoc()['count'];
    }
}

$table_check = $conn->query("SHOW TABLES LIKE 'game_stats'");
if ($table_check->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count FROM game_stats");
    if ($result) {
        $stats['games_played'] = $result->fetch_assoc()['count'];
    }
    
    $result = $conn->query("SELECT SUM(total_questions) as total FROM game_stats");
    if ($result) {
        $stats['problems_solved'] = $result->fetch_assoc()['total'] ?: 0;
    }
    
    $result = $conn->query("SELECT MAX(score) as max FROM game_stats");
    if ($result) {
        $stats['high_score'] = $result->fetch_assoc()['max'] ?: 0;
    }
}

$conn->close();

// Handle logout message - only show once after actual logout
$logoutMessage = '';
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    $logoutMessage = 'You have been logged out successfully.';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NumerIQ - Master the Numbers</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=VT323&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Scanline Effect -->
    <div class="scanlines"></div>
    
    <!-- Floating Pixel Stars -->
    <div class="pixel-stars">
        <div class="pixel-star"></div>
        <div class="pixel-star"></div>
        <div class="pixel-star"></div>
        <div class="pixel-star"></div>
        <div class="pixel-star"></div>
        <div class="pixel-star"></div>
        <div class="pixel-star"></div>
        <div class="pixel-star"></div>
        <div class="pixel-star"></div>
        <div class="pixel-star"></div>
        <div class="pixel-star"></div>
        <div class="pixel-star"></div>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-brand">
            <span class="logo-num">Numer</span><span class="logo-iq">IQ</span>
        </div>
        <div class="nav-actions">
            <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
                <i class="fas fa-sun"></i>
            </button>
            <?php if ($isLoggedIn): ?>
                <a href="dashboard.php" class="btn btn-nav">Dashboard</a>
                <a href="logout.php" class="btn btn-nav-outline">Logout</a>
            <?php else: ?>
                <button class="btn btn-nav" onclick="openAuthModal('login')">Sign In</button>
                <button class="btn btn-nav-primary" onclick="openAuthModal('register')">Get Started</button>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">
                <span class="gradient-text">Master the Numbers</span>
                <br>Challenge Your Mind
            </h1>
            <p class="hero-subtitle">
                Challenge yourself with thrilling math puzzles. Compete with players worldwide, 
                unlock achievements, and become a Math Legend!
            </p>
            <div class="hero-buttons">
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-play"></i> Play Now
                    </a>
                <?php else: ?>
                    <button class="btn btn-primary btn-lg" onclick="openAuthModal('register')">
                        <i class="fas fa-play"></i> Play Now
                    </button>
                <?php endif; ?>
                <a href="#features" class="btn btn-secondary btn-lg">
                    <i class="fas fa-info-circle"></i> Learn More
                </a>
            </div>
        </div>
        <div class="hero-visual">
            <div class="math-symbols">
                <span class="symbol">+</span>
                <span class="symbol">×</span>
                <span class="symbol">÷</span>
                <span class="symbol">-</span>
                <span class="symbol">=</span>
            </div>
        </div>
    </section>

    <!-- Live Stats Counter -->
    <section class="stats-bar">
        <div class="stats-container">
            <div class="stat-item">
                <i class="fas fa-users"></i>
                <span class="stat-number" id="totalPlayers"><?php echo $stats['total_players']; ?></span>
                <span class="stat-label">Total Players</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-gamepad"></i>
                <span class="stat-number" id="gamesPlayed"><?php echo $stats['games_played']; ?></span>
                <span class="stat-label">Games Played</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-check-circle"></i>
                <span class="stat-number" id="problemsSolved"><?php echo $stats['problems_solved']; ?></span>
                <span class="stat-label">Problems Solved</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-trophy"></i>
                <span class="stat-number" id="highScore"><?php echo $stats['high_score']; ?></span>
                <span class="stat-label">High Score</span>
            </div>
        </div>
    </section>

    <!-- Game Modes Preview -->
    <section class="game-modes" id="modes">
        <h2 class="section-title">Choose Your <span class="gradient-text">Challenge</span></h2>
        <p class="section-subtitle">Select a game mode that matches your style</p>
        
        <div class="mode-cards">
            <div class="mode-card" onclick="selectMode('classic')">
                <div class="mode-icon">
                    <i class="fas fa-book"></i>
                </div>
                <h3>Classic</h3>
                <p>10 questions, no time limit. Perfect for practice and building confidence!</p>
                <div class="mode-stats">
                    <span><i class="fas fa-bullseye"></i> 10 Questions</span>
                    <span><i class="fas fa-infinity"></i> No Limit</span>
                </div>
                <?php if ($isLoggedIn): ?>
                    <a href="game.php?select=mode" class="btn btn-mode">Play Classic</a>
                <?php else: ?>
                    <button class="btn btn-mode" onclick="openAuthModal('login')">Play Classic</button>
                <?php endif; ?>
            </div>
            
            <div class="mode-card featured" onclick="selectMode('timeAttack')">
                <div class="mode-badge">Popular</div>
                <div class="mode-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3>Time Attack</h3>
                <p>Answer as many as you can in 60 seconds. Speed is key!</p>
                <div class="mode-stats">
                    <span><i class="fas fa-bullseye"></i> Unlimited</span>
                    <span><i class="fas fa-clock"></i> 60 Seconds</span>
                </div>
                <?php if ($isLoggedIn): ?>
                    <a href="game.php?select=mode" class="btn btn-mode">Play Time Attack</a>
                <?php else: ?>
                    <button class="btn btn-mode" onclick="openAuthModal('login')">Play Time Attack</button>
                <?php endif; ?>
            </div>
            
            <div class="mode-card" onclick="selectMode('survival')">
                <div class="mode-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3>Survival</h3>
                <p>3 lives. One wrong answer = lose a life. How long can you survive?</p>
                <div class="mode-stats">
                    <span><i class="fas fa-bullseye"></i> Unlimited</span>
                    <span><i class="fas fa-heart"></i> 3 Lives</span>
                </div>
                <?php if ($isLoggedIn): ?>
                    <a href="game.php?select=mode" class="btn btn-mode">Play Survival</a>
                <?php else: ?>
                    <button class="btn btn-mode" onclick="openAuthModal('login')">Play Survival</button>
                <?php endif; ?>
            </div>

            <div class="mode-card practice">
                <div class="mode-badge practice-badge">Free</div>
                <div class="mode-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h3>Practice Mode</h3>
                <p>No pressure, no stats recorded. Just pure learning and fun!</p>
                <div class="mode-stats">
                    <span><i class="fas fa-bullseye"></i> Unlimited</span>
                    <span><i class="fas fa-infinity"></i> No Limit</span>
                </div>
                <a href="game.php?mode=practice&select=mode" class="btn btn-mode-practice">Start Practice</a>
            </div>
        </div>
    </section>

    <!-- Difficulty Levels -->
    <section class="difficulty-section" id="difficulty">
        <h2 class="section-title">Pick Your <span class="gradient-text">Difficulty</span></h2>
        <p class="section-subtitle">From beginner to expert, there's a challenge for everyone</p>
        
        <div class="difficulty-grid">
            <div class="diff-card easy">
                <div class="diff-icon">
                    <i class="fas fa-seedling"></i>
                </div>
                <h3>Easy</h3>
                <p>Numbers 1-10</p>
                <div class="diff-tags">
                    <span class="tag">Addition</span>
                    <span class="tag">Subtraction</span>
                </div>
            </div>
            
            <div class="diff-card medium">
                <div class="diff-icon">
                    <i class="fas fa-leaf"></i>
                </div>
                <h3>Medium</h3>
                <p>Numbers 1-50</p>
                <div class="diff-tags">
                    <span class="tag">+ Addition</span>
                    <span class="tag">- Subtraction</span>
                    <span class="tag">× Multiplication</span>
                </div>
            </div>
            
            <div class="diff-card hard">
                <div class="diff-icon">
                    <i class="fas fa-fire"></i>
                </div>
                <h3>Hard</h3>
                <p>Numbers 1-100</p>
                <div class="diff-tags">
                    <span class="tag">All Operations</span>
                </div>
            </div>
            
            <div class="diff-card expert">
                <div class="diff-icon">
                    <i class="fas fa-skull"></i>
                </div>
                <h3>Expert</h3>
                <p>Numbers 1-1000</p>
                <div class="diff-tags">
                    <span class="tag">All Operations</span>
                    <span class="tag">Complex</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Achievements Grid -->
    <section class="achievements-section" id="achievements">
        <h2 class="section-title">Unlock <span class="gradient-text">Achievements</span></h2>
        <p class="section-subtitle">Complete challenges and earn badges</p>
        
        <div class="achievements-grid">
            <div class="achievement-card locked">
                <div class="achievement-icon">👶</div>
                <h4>First Steps</h4>
                <p>Complete your first game</p>
            </div>
            <div class="achievement-card locked">
                <div class="achievement-icon">🔥</div>
                <h4>On Fire</h4>
                <p>Reach a 5-question streak</p>
            </div>
            <div class="achievement-card locked">
                <div class="achievement-icon">⚡</div>
                <h4>Unstoppable</h4>
                <p>Reach a 10-question streak</p>
            </div>
            <div class="achievement-card locked">
                <div class="achievement-icon">👑</div>
                <h4>Legendary</h4>
                <p>Reach a 20-question streak</p>
            </div>
            <div class="achievement-card locked">
                <div class="achievement-icon">💯</div>
                <h4>Century</h4>
                <p>Score 100 points in one game</p>
            </div>
            <div class="achievement-card locked">
                <div class="achievement-icon">🎯</div>
                <h4>High Roller</h4>
                <p>Score 500 points in one game</p>
            </div>
            <div class="achievement-card locked">
                <div class="achievement-icon">🏆</div>
                <h4>Math Master</h4>
                <p>Score 1000 points in one game</p>
            </div>
            <div class="achievement-card locked">
                <div class="achievement-icon">✨</div>
                <h4>Perfectionist</h4>
                <p>Answer all questions correctly</p>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <h2 class="section-title">Why Play <span class="gradient-text">NumerIQ</span>?</h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-brain"></i>
                </div>
                <h3>Boost Your Brain</h3>
                <p>Improve your mental math skills and reaction time with daily practice.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <h3>Compete Globally</h3>
                <p>Climb the leaderboard and compete with players from around the world.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-medal"></i>
                </div>
                <h3>Earn Achievements</h3>
                <p>Unlock badges and showcase your math mastery to friends.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Track Progress</h3>
                <p>Detailed statistics and charts to monitor your improvement over time.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-gamepad"></i>
                </div>
                <h3>Multiple Modes</h3>
                <p>Classic, Time Attack, Survival - choose your preferred challenge style.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3>Play Anywhere</h3>
                <p>Responsive design works perfectly on desktop, tablet, and mobile devices.</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-content">
            <h2>Ready to Start Your Math Journey?</h2>
            <p>Join players improving their math skills every day!</p>
            <?php if ($isLoggedIn): ?>
                <a href="dashboard.php" class="btn btn-cta">Go to Dashboard</a>
            <?php else: ?>
                <button class="btn btn-cta" onclick="openAuthModal('register')">Create Free Account</button>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand">
                <span class="logo-num">Numer</span><span class="logo-iq">IQ</span>
                <p>Master the Numbers, Challenge Your Mind!</p>
            </div>
            <div class="footer-links">
                <a href="#modes">Game Modes</a>
                <a href="#difficulty">Difficulty</a>
                <a href="#features">Features</a>
                <a href="#achievements">Achievements</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 NumerIQ. All rights reserved.</p>
        </div>
    </footer>

    <!-- Auth Modal -->
    <div class="modal-overlay" id="authModal">
        <div class="modal-container">
            <button class="modal-close" onclick="closeAuthModal()">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="modal-tabs">
                <button class="tab-btn active" onclick="switchTab('login')" data-tab="login">Sign In</button>
                <button class="tab-btn" onclick="switchTab('register')" data-tab="register">Sign Up</button>
            </div>
            
            <!-- Login Form -->
            <div class="tab-content active" id="loginTab">
                <form action="login.php" method="POST" class="auth-form">
                    <h3>Welcome Back!</h3>
                    <p class="form-subtitle">Continue your math journey</p>
                    
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email Address" required>
                    </div>
                    
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required class="has-toggle" id="loginPassword">
                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('loginPassword', this)" title="Show/Hide Password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>
            </div>
            
            <!-- Register Form -->
            <div class="tab-content" id="registerTab">
                <form action="register.php" method="POST" class="auth-form">
                    <h3>Create Account</h3>
                    <p class="form-subtitle">Join the NumerIQ adventure!</p>
                    
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="name" placeholder="Full Name" required minlength="2">
                    </div>
                    
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email Address" required>
                    </div>
                    
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required minlength="6" class="has-toggle" id="registerPassword">
                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('registerPassword', this)" title="Show/Hide Password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-user-plus"></i> Sign Up
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="fas fa-info-circle"></i>
        <span id="toastMessage">Please login to access this feature</span>
    </div>

    <script src="landing.js"></script>
</body>
</html>
