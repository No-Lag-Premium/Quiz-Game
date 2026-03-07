<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php?login_required=1");
    exit();
}

include 'userdb.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];

// Get user profile info
$profile_stmt = $conn->prepare("SELECT profile_pic, created_at FROM users WHERE id = ?");
$profile_stmt->bind_param("i", $user_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
$profile = $profile_result->fetch_assoc();
$profile_stmt->close();

$profile_pic = $profile['profile_pic'] ?: 'default-avatar.png';
$member_since = date('M Y', strtotime($profile['created_at']));

// Get user stats from database
$stats = [
    'total_games' => 0,
    'total_score' => 0,
    'high_score' => 0,
    'accuracy' => 0,
    'best_streak' => 0,
    'rank' => 'Novice',
    'classic_games' => 0,
    'timeattack_games' => 0,
    'survival_games' => 0,
    'practice_games' => 0,
    'weekly_score' => 0,
    'monthly_score' => 0
];

// Check if game_stats table exists
$table_check = $conn->query("SHOW TABLES LIKE 'game_stats'");
if ($table_check->num_rows > 0) {
    // Overall stats (excluding practice mode)
    $stats_query = $conn->prepare("SELECT 
        COUNT(*) as total_games,
        SUM(score) as total_score,
        MAX(score) as high_score,
        AVG(correct_answers / NULLIF(total_questions, 0) * 100) as accuracy,
        MAX(streak) as best_streak
        FROM game_stats WHERE user_id = ? AND is_practice = 0");
    $stats_query->bind_param("i", $user_id);
    $stats_query->execute();
    $result = $stats_query->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['total_games'] = $row['total_games'] ?: 0;
        $stats['total_score'] = $row['total_score'] ?: 0;
        $stats['high_score'] = $row['high_score'] ?: 0;
        $stats['accuracy'] = round($row['accuracy'] ?: 0);
        $stats['best_streak'] = $row['best_streak'] ?: 0;
    }
    $stats_query->close();
    
    // Games by mode
    $mode_query = $conn->prepare("SELECT 
        game_mode, COUNT(*) as count
        FROM game_stats WHERE user_id = ? GROUP BY game_mode");
    $mode_query->bind_param("i", $user_id);
    $mode_query->execute();
    $mode_result = $mode_query->get_result();
    while ($row = $mode_result->fetch_assoc()) {
        if ($row['game_mode'] === 'classic') $stats['classic_games'] = $row['count'];
        if ($row['game_mode'] === 'timeAttack') $stats['timeattack_games'] = $row['count'];
        if ($row['game_mode'] === 'survival') $stats['survival_games'] = $row['count'];
        if ($row['game_mode'] === 'practice') $stats['practice_games'] = $row['count'];
    }
    $mode_query->close();
    
    // Weekly and monthly scores
    $weekly_query = $conn->prepare("SELECT SUM(score) as weekly FROM game_stats 
        WHERE user_id = ? AND is_practice = 0 AND played_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $weekly_query->bind_param("i", $user_id);
    $weekly_query->execute();
    $weekly_result = $weekly_query->get_result();
    if ($row = $weekly_result->fetch_assoc()) {
        $stats['weekly_score'] = $row['weekly'] ?: 0;
    }
    $weekly_query->close();
    
    $monthly_query = $conn->prepare("SELECT SUM(score) as monthly FROM game_stats 
        WHERE user_id = ? AND is_practice = 0 AND played_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $monthly_query->bind_param("i", $user_id);
    $monthly_query->execute();
    $monthly_result = $monthly_query->get_result();
    if ($row = $monthly_result->fetch_assoc()) {
        $stats['monthly_score'] = $row['monthly'] ?: 0;
    }
    $monthly_query->close();
}

// Determine rank based on total score
if ($stats['total_score'] >= 5000) $stats['rank'] = 'Legend';
elseif ($stats['total_score'] >= 2000) $stats['rank'] = 'Grandmaster';
elseif ($stats['total_score'] >= 1000) $stats['rank'] = 'Master';
elseif ($stats['total_score'] >= 600) $stats['rank'] = 'Expert';
elseif ($stats['total_score'] >= 300) $stats['rank'] = 'Scholar';
elseif ($stats['total_score'] >= 100) $stats['rank'] = 'Apprentice';

// Get user's achievements
$user_achievements = [];
$ach_check = $conn->query("SHOW TABLES LIKE 'user_achievements'");
if ($ach_check->num_rows > 0) {
    $ach_query = $conn->prepare("SELECT achievement_id FROM user_achievements WHERE user_id = ?");
    $ach_query->bind_param("i", $user_id);
    $ach_query->execute();
    $ach_result = $ach_query->get_result();
    while ($row = $ach_result->fetch_assoc()) {
        $user_achievements[] = $row['achievement_id'];
    }
    $ach_query->close();
}

// Get all achievements
$all_achievements = [];
$ach_def_check = $conn->query("SHOW TABLES LIKE 'achievements'");
if ($ach_def_check->num_rows > 0) {
    $ach_def_query = $conn->query("SELECT * FROM achievements ORDER BY requirement_value");
    while ($row = $ach_def_query->fetch_assoc()) {
        $all_achievements[] = $row;
    }
}

// Get leaderboard data
$leaderboard = [];
if ($table_check->num_rows > 0) {
    $lb_query = $conn->query("SELECT 
        u.name,
        MAX(gs.score) as high_score,
        COUNT(gs.id) as total_games
        FROM users u
        LEFT JOIN game_stats gs ON u.id = gs.user_id AND gs.is_practice = 0
        GROUP BY u.id
        HAVING high_score > 0
        ORDER BY high_score DESC
        LIMIT 10");
    while ($row = $lb_query->fetch_assoc()) {
        $leaderboard[] = $row;
    }
}

// Get recent games
$recent_games = [];
if ($table_check->num_rows > 0) {
    $recent_query = $conn->prepare("SELECT 
        game_mode, difficulty, score, correct_answers, total_questions, streak, played_at, is_practice
        FROM game_stats WHERE user_id = ? ORDER BY played_at DESC LIMIT 5");
    $recent_query->bind_param("i", $user_id);
    $recent_query->execute();
    $recent_result = $recent_query->get_result();
    while ($row = $recent_result->fetch_assoc()) {
        $recent_games[] = $row;
    }
    $recent_query->close();
}

// Get score history for chart (last 30 days)
$score_history = [];
if ($table_check->num_rows > 0) {
    $history_query = $conn->prepare("SELECT 
        DATE(played_at) as date,
        SUM(score) as daily_score,
        COUNT(*) as games_played
        FROM game_stats 
        WHERE user_id = ? AND is_practice = 0 AND played_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(played_at)
        ORDER BY date ASC");
    $history_query->bind_param("i", $user_id);
    $history_query->execute();
    $history_result = $history_query->get_result();
    while ($row = $history_result->fetch_assoc()) {
        $score_history[] = $row;
    }
    $history_query->close();
}

$conn->close();

// Helper functions
function getNextRankScore($currentScore) {
    if ($currentScore >= 5000) return 10000;
    if ($currentScore >= 2000) return 5000;
    if ($currentScore >= 1000) return 2000;
    if ($currentScore >= 600) return 1000;
    if ($currentScore >= 300) return 600;
    if ($currentScore >= 100) return 300;
    return 100;
}

function getProgressPercent($currentScore) {
    $next = getNextRankScore($currentScore);
    $prev = 0;
    
    if ($currentScore >= 5000) $prev = 5000;
    elseif ($currentScore >= 2000) $prev = 2000;
    elseif ($currentScore >= 1000) $prev = 1000;
    elseif ($currentScore >= 600) $prev = 600;
    elseif ($currentScore >= 300) $prev = 300;
    elseif ($currentScore >= 100) $prev = 100;
    
    $range = $next - $prev;
    $progress = $currentScore - $prev;
    return min(100, max(0, ($progress / $range) * 100));
}

function getRankColor($rank) {
    $colors = [
        'Novice' => '#94a3b8',
        'Apprentice' => '#22c55e',
        'Scholar' => '#3b82f6',
        'Expert' => '#f97316',
        'Master' => '#a855f7',
        'Grandmaster' => '#ec4899',
        'Legend' => '#fbbf24'
    ];
    return $colors[$rank] ?? '#94a3b8';
}

function getModeIcon($mode) {
    $icons = [
        'classic' => 'fa-book',
        'timeAttack' => 'fa-bolt',
        'survival' => 'fa-heart',
        'practice' => 'fa-graduation-cap'
    ];
    return $icons[$mode] ?? 'fa-gamepad';
}

function getDifficultyColor($diff) {
    $colors = [
        'easy' => '#00ff00',
        'medium' => '#00ffff',
        'hard' => '#ffaa00',
        'expert' => '#ff0040'
    ];
    return $colors[$diff] ?? '#94a3b8';
}

// All ranks for progress bar
$all_ranks = [
    ['name' => 'Novice', 'min' => 0],
    ['name' => 'Apprentice', 'min' => 100],
    ['name' => 'Scholar', 'min' => 300],
    ['name' => 'Expert', 'min' => 600],
    ['name' => 'Master', 'min' => 1000],
    ['name' => 'Grandmaster', 'min' => 2000],
    ['name' => 'Legend', 'min' => 5000]
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NumerIQ</title>
    <link rel="stylesheet" href="dashboard-styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=VT323&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
            <i class="fas fa-chevron-left"></i>
        </button>
        
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-logo">
                <span class="logo-math">Numer</span><span class="logo-quest">IQ</span>
            </a>
        </div>
        
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($username, 0, 1)); ?>
            </div>
            <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
            <div class="user-rank" style="color: <?php echo getRankColor($stats['rank']); ?>">
                <?php echo $stats['rank']; ?>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">Menu</div>
            <a href="dashboard.php" class="nav-item active">
                <i class="fas fa-chart-bar"></i>
                <span>Dashboard</span>
            </a>
            <a href="game.php" class="nav-item">
                <i class="fas fa-play"></i>
                <span>Play Game</span>
            </a>
            <a href="achievements.php" class="nav-item">
                <i class="fas fa-medal"></i>
                <span>Achievements</span>
            </a>
            
            <div class="nav-section">Account</div>
            <a href="logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <!-- Header -->
        <header class="dashboard-header">
            <h1>Dashboard</h1>
            <div class="header-actions">
                <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
                    <i class="fas fa-sun"></i>
                </button>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-gamepad"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Games Played</span>
                    <span class="stat-value"><?php echo number_format($stats['total_games']); ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pink">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Total Score</span>
                    <span class="stat-value"><?php echo number_format($stats['total_score']); ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Best Streak</span>
                    <span class="stat-value"><?php echo number_format($stats['best_streak']); ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Accuracy</span>
                    <span class="stat-value"><?php echo $stats['accuracy']; ?>%</span>
                </div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="dashboard-grid">
            <!-- Left Column -->
            <div class="dashboard-column">
                <!-- Rank Progress -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-trophy"></i> Rank Progress</h3>
                    </div>
                    <div class="rank-display">
                        <div class="current-rank" style="color: <?php echo getRankColor($stats['rank']); ?>">
                            <?php echo $stats['rank']; ?>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo getProgressPercent($stats['total_score']); ?>"></div>
                        </div>
                        <div class="progress-info">
                            <span><?php echo $stats['total_score']; ?> XP</span>
                            <span><?php echo getNextRankScore($stats['total_score']) - $stats['total_score']; ?> XP to next rank</span>
                        </div>
                        
                        <!-- Full Rank Progress Bar -->
                        <div class="full-rank-progress">
                            <div class="rank-track">
                                <?php foreach ($all_ranks as $rank): ?>
                                    <div class="rank-segment <?php echo $rank['name'] === $stats['rank'] ? 'current' : ($stats['total_score'] >= $rank['min'] ? 'achieved' : ''); ?>"
                                         title="<?php echo $rank['name']; ?> (<?php echo $rank['min']; ?>+ XP)">
                                        <?php echo substr($rank['name'], 0, 1); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="rank-labels">
                                <span>Novice</span>
                                <span>Legend</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Play -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-play-circle"></i> Quick Play</h3>
                    </div>
                    <div class="quick-play-grid">
                        <a href="game.php?select=mode&source=quickplay" class="quick-play-btn" onclick="localStorage.setItem('selectedMode', 'classic')">
                            <i class="fas fa-book"></i>
                            <span>Classic</span>
                        </a>
                        <a href="game.php?select=mode&source=quickplay" class="quick-play-btn" onclick="localStorage.setItem('selectedMode', 'timeAttack')">
                            <i class="fas fa-bolt"></i>
                            <span>Time Attack</span>
                        </a>
                        <a href="game.php?select=mode&source=quickplay" class="quick-play-btn" onclick="localStorage.setItem('selectedMode', 'survival')">
                            <i class="fas fa-heart"></i>
                            <span>Survival</span>
                        </a>
                        <a href="game.php?mode=practice&select=mode" class="quick-play-btn practice">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Practice</span>
                        </a>
                    </div>
                </div>

                <!-- Recent Games -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Recent Games</h3>
                    </div>
                    <div class="recent-games">
                        <?php if (empty($recent_games)): ?>
                            <p class="empty-state">No games played yet. Start playing!</p>
                        <?php else: ?>
                            <?php foreach ($recent_games as $game): ?>
                                <div class="recent-game-item">
                                    <div class="game-mode-icon" style="border-color: <?php echo $game['is_practice'] ? '#00ff00' : '#00ffff'; ?>">
                                        <i class="fas <?php echo getModeIcon($game['game_mode']); ?>"></i>
                                    </div>
                                    <div class="game-info">
                                        <div class="game-mode">
                                            <?php echo ucfirst($game['game_mode']); ?>
                                            <?php if ($game['is_practice']): ?>
                                                <span class="practice-badge">Practice</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="game-details">
                                            <?php echo ucfirst($game['difficulty']); ?> • 
                                            <?php echo $game['correct_answers']; ?>/<?php echo $game['total_questions']; ?> correct
                                        </div>
                                    </div>
                                    <div class="game-score"><?php echo number_format($game['score']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="dashboard-column">
                <!-- Achievements -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-medal"></i> Achievements</h3>
                        <span class="card-badge"><?php echo count($user_achievements); ?>/<?php echo count($all_achievements); ?></span>
                    </div>
                    <div class="achievements-grid">
                        <?php foreach (array_slice($all_achievements, 0, 8) as $achievement): 
                            $isUnlocked = in_array($achievement['id'], $user_achievements);
                        ?>
                            <div class="achievement-item <?php echo $isUnlocked ? '' : 'locked'; ?>" 
                                 title="<?php echo htmlspecialchars($achievement['description']); ?>">
                                <span class="achievement-icon"><?php echo $achievement['icon']; ?></span>
                                <span class="achievement-name"><?php echo htmlspecialchars($achievement['name']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($all_achievements) > 8): ?>
                        <a href="achievements.php" class="view-all-link">View All Achievements</a>
                    <?php endif; ?>
                </div>

                <!-- Leaderboard -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-list-ol"></i> Leaderboard</h3>
                    </div>
                    <div class="leaderboard-list">
                        <?php if (empty($leaderboard)): ?>
                            <p class="empty-state">No leaderboard data yet.</p>
                        <?php else: ?>
                            <?php foreach ($leaderboard as $index => $entry): ?>
                                <div class="leaderboard-item <?php echo $entry['name'] === $username ? 'current-user' : ''; ?>">
                                    <span class="leaderboard-rank <?php echo $index < 3 ? 'top-' . ($index + 1) : ''; ?>">
                                        <?php echo $index + 1; ?>
                                    </span>
                                    <span class="leaderboard-name"><?php echo htmlspecialchars($entry['name']); ?></span>
                                    <span class="leaderboard-score"><?php echo number_format($entry['high_score']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Performance Chart -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Performance</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Theme toggle
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);

        // Smart Sidebar Logic
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');
        
        // Check for saved sidebar state
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            sidebarToggle.innerHTML = '<i class="fas fa-chevron-right"></i>';
        }
        
        // Toggle sidebar
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            sidebarToggle.innerHTML = isCollapsed ? 
                '<i class="fas fa-chevron-right"></i>' : 
                '<i class="fas fa-chevron-left"></i>';
        });
        
        // Auto-collapse on small screens
        function handleResize() {
            if (window.innerWidth <= 768) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            } else if (localStorage.getItem('sidebarCollapsed') !== 'true') {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
            }
        }
        
        window.addEventListener('resize', handleResize);
        handleResize();

        // Performance Chart
        const ctx = document.getElementById('performanceChart').getContext('2d');
        const scoreHistory = <?php echo json_encode($score_history); ?>;
        
        const labels = scoreHistory.map(h => {
            const date = new Date(h.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        const data = scoreHistory.map(h => h.daily_score);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Daily Score',
                    data: data,
                    borderColor: '#00ffff',
                    backgroundColor: 'rgba(0, 255, 255, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 255, 255, 0.1)' },
                        ticks: { color: '#a0a0ff', font: { family: 'VT323', size: 12 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#a0a0ff', font: { family: 'VT323', size: 12 } }
                    }
                }
            }
        });
    </script>
</body>
</html>
