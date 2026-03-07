<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

include 'userdb.php';

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$user_id = $_SESSION['user_id'];
$game_mode = $data['game_mode'] ?? 'classic';
$difficulty = $data['difficulty'] ?? 'medium';
$score = intval($data['score'] ?? 0);
$correct_answers = intval($data['correct_answers'] ?? 0);
$total_questions = intval($data['total_questions'] ?? 0);
$streak = intval($data['streak'] ?? 0);
$is_practice = intval($data['is_practice'] ?? 0);

// Do not save stats for practice mode
if ($is_practice) {
    echo json_encode(['success' => true, 'message' => 'Practice mode - stats not saved']);
    exit();
}

// Insert game stats (only for non-practice modes)
$stmt = $conn->prepare("INSERT INTO game_stats 
    (user_id, game_mode, difficulty, score, correct_answers, total_questions, streak, played_at, is_practice) 
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)");

$stmt->bind_param("isssiiii", $user_id, $game_mode, $difficulty, $score, $correct_answers, $total_questions, $streak, $is_practice);

if ($stmt->execute()) {
    // Check and unlock achievements based on this game
    $unlocked_achievements = checkAndUnlockAchievements($conn, $user_id, $game_mode, $difficulty, $score, $streak, $correct_answers, $total_questions);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Game saved successfully',
        'unlocked_achievements' => $unlocked_achievements
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save game: ' . $stmt->error]);
}

$stmt->close();
$conn->close();

// Helper function to check and unlock achievements with CASCADE logic
function checkAndUnlockAchievements($conn, $user_id, $game_mode, $difficulty, $score, $streak, $correct_answers, $total_questions) {
    $unlocked = [];
    
    // Get all achievements ordered by requirement_type and requirement_value
    $ach_query = $conn->query("SELECT * FROM achievements ORDER BY requirement_type, requirement_value ASC");
    $all_achievements = [];
    while ($row = $ach_query->fetch_assoc()) {
        $all_achievements[$row['requirement_type']][] = $row;
    }
    
    // Get user's current stats from ALL games (for cascade checking)
    $stats_query = $conn->prepare("SELECT 
        COUNT(*) as total_games,
        MAX(score) as high_score,
        MAX(streak) as best_streak,
        SUM(correct_answers) as total_correct,
        SUM(total_questions) as total_questions_sum
        FROM game_stats WHERE user_id = ? AND is_practice = 0");
    $stats_query->bind_param("i", $user_id);
    $stats_query->execute();
    $stats_result = $stats_query->get_result();
    $user_stats = $stats_result->fetch_assoc();
    $stats_query->close();
    
    $total_games = intval($user_stats['total_games']);
    $high_score = intval($user_stats['high_score']);
    $best_streak = intval($user_stats['best_streak']);
    
    // Calculate overall accuracy from all games
    $overall_accuracy = 0;
    if ($user_stats['total_questions_sum'] > 0) {
        $overall_accuracy = round(($user_stats['total_correct'] / $user_stats['total_questions_sum']) * 100);
    }
    
    // Calculate this game's accuracy
    $game_accuracy = 0;
    if ($total_questions > 0) {
        $game_accuracy = round(($correct_answers / $total_questions) * 100);
    }
    
    // Use the HIGHER of overall accuracy OR this game's accuracy
    $best_accuracy = max($overall_accuracy, $game_accuracy);
    
    // Get already unlocked achievements
    $unlocked_query = $conn->prepare("SELECT achievement_id FROM user_achievements WHERE user_id = ?");
    $unlocked_query->bind_param("i", $user_id);
    $unlocked_query->execute();
    $unlocked_result = $unlocked_query->get_result();
    $already_unlocked = [];
    while ($row = $unlocked_result->fetch_assoc()) {
        $already_unlocked[] = $row['achievement_id'];
    }
    $unlocked_query->close();
    
    // ===== CASCADE UNLOCKING LOGIC =====
    // If a user achieves a higher goal, all lower goals of the same type unlock too
    
    // 1. Games played achievements (cascade)
    if (isset($all_achievements['games'])) {
        foreach ($all_achievements['games'] as $achievement) {
            // Cascade: if user has 100 games, they unlock 1, 10, 50, and 100
            if ($total_games >= $achievement['requirement_value']) {
                if (!in_array($achievement['id'], $already_unlocked)) {
                    unlockAchievement($conn, $user_id, $achievement);
                    $unlocked[] = $achievement;
                }
            }
        }
    }
    
    // 2. Streak achievements (cascade)
    if (isset($all_achievements['streak'])) {
        foreach ($all_achievements['streak'] as $achievement) {
            // Cascade: if user has 20 streak, they unlock 5, 10, and 20
            if ($best_streak >= $achievement['requirement_value']) {
                if (!in_array($achievement['id'], $already_unlocked)) {
                    unlockAchievement($conn, $user_id, $achievement);
                    $unlocked[] = $achievement;
                }
            }
        }
    }
    
    // 3. Score achievements (cascade)
    if (isset($all_achievements['score'])) {
        foreach ($all_achievements['score'] as $achievement) {
            // Cascade: if user scored 1000, they unlock 100, 500, and 1000
            if ($high_score >= $achievement['requirement_value']) {
                if (!in_array($achievement['id'], $already_unlocked)) {
                    unlockAchievement($conn, $user_id, $achievement);
                    $unlocked[] = $achievement;
                }
            }
        }
    }
    
    // 4. Accuracy achievements (cascade)
    if (isset($all_achievements['accuracy'])) {
        foreach ($all_achievements['accuracy'] as $achievement) {
            // Cascade: if user has 100% accuracy, they unlock 80%, 90%, and 100%
            if ($best_accuracy >= $achievement['requirement_value']) {
                if (!in_array($achievement['id'], $already_unlocked)) {
                    unlockAchievement($conn, $user_id, $achievement);
                    $unlocked[] = $achievement;
                }
            }
        }
    }
    
    // 5. Speed demon (Time Attack with 10+ questions answered)
    if (isset($all_achievements['speed']) && $game_mode === 'timeAttack') {
        foreach ($all_achievements['speed'] as $achievement) {
            if ($total_questions >= $achievement['requirement_value']) {
                if (!in_array($achievement['id'], $already_unlocked)) {
                    unlockAchievement($conn, $user_id, $achievement);
                    $unlocked[] = $achievement;
                }
            }
        }
    }
    
    // 6. Survivor (Survival mode with 20+ questions)
    if (isset($all_achievements['survival']) && $game_mode === 'survival') {
        foreach ($all_achievements['survival'] as $achievement) {
            if ($total_questions >= $achievement['requirement_value']) {
                if (!in_array($achievement['id'], $already_unlocked)) {
                    unlockAchievement($conn, $user_id, $achievement);
                    $unlocked[] = $achievement;
                }
            }
        }
    }
    
    // 7. Expert mode (completed game on expert difficulty)
    if (isset($all_achievements['difficulty']) && $difficulty === 'expert') {
        foreach ($all_achievements['difficulty'] as $achievement) {
            if (!in_array($achievement['id'], $already_unlocked)) {
                unlockAchievement($conn, $user_id, $achievement);
                $unlocked[] = $achievement;
            }
        }
    }
    
    return $unlocked;
}

// Helper function to unlock a single achievement
function unlockAchievement($conn, $user_id, $achievement) {
    $stmt = $conn->prepare("INSERT INTO user_achievements (user_id, achievement_id, unlocked_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $user_id, $achievement['id']);
    $stmt->execute();
    $stmt->close();
}
?>
