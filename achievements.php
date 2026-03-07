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

// Get user's unlocked achievements
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

$conn->close();

// Filter to show only unlocked achievements
$unlocked_achievements = array_filter($all_achievements, function($ach) use ($user_achievements) {
    return in_array($ach['id'], $user_achievements);
});

// Group achievements by category (for all achievements to show locked ones)
$categories = [
    'streak' => ['icon' => 'fa-fire', 'name' => 'Streak Achievements', 'desc' => 'Build impressive answer streaks'],
    'score' => ['icon' => 'fa-star', 'name' => 'Score Achievements', 'desc' => 'Reach high scores in games'],
    'games' => ['icon' => 'fa-gamepad', 'name' => 'Games Played', 'desc' => 'Play more games to unlock'],
    'difficulty' => ['icon' => 'fa-skull', 'name' => 'Difficulty', 'desc' => 'Conquer harder challenges'],
    'other' => ['icon' => 'fa-trophy', 'name' => 'Special Achievements', 'desc' => 'Complete special challenges']
];

$grouped_achievements = [];
foreach ($all_achievements as $achievement) {
    $type = $achievement['requirement_type'];
    if (!isset($categories[$type])) {
        $type = 'other';
    }
    $grouped_achievements[$type][] = $achievement;
}

function getProgressPercent($achievement, $user_stats) {
    return in_array($achievement['id'], $user_stats) ? 100 : 0;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achievements - NumerIQ</title>
    <link rel="stylesheet" href="dashboard-styles.css">
    <link rel="stylesheet" href="achievements-styles.css">
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
            <div class="user-stats">
                <span class="achievement-count">
                    <i class="fas fa-medal"></i> <?php echo count($user_achievements); ?>/<?php echo count($all_achievements); ?>
                </span>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">Menu</div>
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-chart-bar"></i>
                <span>Dashboard</span>
            </a>
            <a href="game.php" class="nav-item">
                <i class="fas fa-play"></i>
                <span>Play Game</span>
            </a>
            <a href="achievements.php" class="nav-item active">
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
            <div class="header-left">
                <a href="dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <h1>Achievements</h1>
            </div>
            <div class="header-actions">
                <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
                    <i class="fas fa-sun"></i>
                </button>
            </div>
        </header>

        <!-- Achievement Summary -->
        <div class="achievement-summary">
            <div class="summary-card">
                <div class="summary-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="summary-info">
                    <span class="summary-value"><?php echo count($user_achievements); ?></span>
                    <span class="summary-label">Unlocked</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon locked">
                    <i class="fas fa-lock"></i>
                </div>
                <div class="summary-info">
                    <span class="summary-value"><?php echo count($all_achievements) - count($user_achievements); ?></span>
                    <span class="summary-label">Locked</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon progress">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="summary-info">
                    <span class="summary-value"><?php echo count($all_achievements) > 0 ? round((count($user_achievements) / count($all_achievements)) * 100) : 0; ?>%</span>
                    <span class="summary-label">Complete</span>
                </div>
            </div>
        </div>

        <!-- Unlocked Achievements Section -->
        <?php if (count($user_achievements) > 0): ?>
        <div class="dashboard-card" style="margin-bottom: 30px;">
            <div class="card-header">
                <h3><i class="fas fa-unlock"></i> Your Unlocked Achievements</h3>
            </div>
            <div class="unlocked-achievements-grid">
                <?php foreach ($unlocked_achievements as $achievement): ?>
                    <div class="unlocked-achievement-card">
                        <div class="unlocked-achievement-icon">
                            <span class="icon-emoji"><?php echo $achievement['icon']; ?></span>
                            <span class="unlocked-badge"><i class="fas fa-check"></i></span>
                        </div>
                        <div class="unlocked-achievement-content">
                            <h3><?php echo htmlspecialchars($achievement['name']); ?></h3>
                            <p><?php echo htmlspecialchars($achievement['description']); ?></p>
                            <div class="unlocked-achievement-meta">
                                <span class="points">
                                    <i class="fas fa-star"></i> <?php echo $achievement['points']; ?> pts
                                </span>
                                <span class="status unlocked">Unlocked</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="dashboard-card" style="margin-bottom: 30px; text-align: center; padding: 40px;">
            <div style="font-size: 3rem; margin-bottom: 20px;">🏆</div>
            <h3 style="margin-bottom: 15px;">No Achievements Yet</h3>
            <p style="color: var(--text-secondary);">Start playing to unlock your first achievement!</p>
            <a href="game.php" class="btn btn-primary" style="margin-top: 20px;">Play Now</a>
        </div>
        <?php endif; ?>

        <!-- Achievement Categories (All achievements with locked status) -->
        <div class="achievements-container">
            <?php foreach ($grouped_achievements as $category => $achievements): ?>
                <?php if (!empty($achievements)): ?>
                <div class="achievement-category">
                    <div class="category-header">
                        <div class="category-icon">
                            <i class="fas <?php echo $categories[$category]['icon']; ?>"></i>
                        </div>
                        <div class="category-info">
                            <h2><?php echo $categories[$category]['name']; ?></h2>
                            <p><?php echo $categories[$category]['desc']; ?></p>
                        </div>
                        <div class="category-progress">
                            <?php 
                            $unlocked_in_category = 0;
                            foreach ($achievements as $ach) {
                                if (in_array($ach['id'], $user_achievements)) $unlocked_in_category++;
                            }
                            ?>
                            <span class="progress-text"><?php echo $unlocked_in_category; ?>/<?php echo count($achievements); ?></span>
                        </div>
                    </div>
                    
                    <div class="achievement-grid">
                        <?php foreach ($achievements as $achievement): 
                            $isUnlocked = in_array($achievement['id'], $user_achievements);
                        ?>
                            <div class="achievement-card <?php echo $isUnlocked ? 'unlocked' : 'locked'; ?>">
                                <div class="achievement-icon">
                                    <span class="icon-emoji"><?php echo $achievement['icon']; ?></span>
                                    <?php if ($isUnlocked): ?>
                                        <span class="unlocked-badge"><i class="fas fa-check"></i></span>
                                    <?php endif; ?>
                                </div>
                                <div class="achievement-content">
                                    <h3><?php echo htmlspecialchars($achievement['name']); ?></h3>
                                    <p><?php echo htmlspecialchars($achievement['description']); ?></p>
                                    <div class="achievement-meta">
                                        <span class="points">
                                            <i class="fas fa-star"></i> <?php echo $achievement['points']; ?> pts
                                        </span>
                                        <?php if ($isUnlocked): ?>
                                            <span class="status unlocked">Unlocked</span>
                                        <?php else: ?>
                                            <span class="status locked">Locked</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
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
    </script>
</body>
</html>
