<?php
session_start();
header('Content-Type: application/json');

include 'userdb.php';

// Get leaderboard data with player names
$leaderboard = [];
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'all';

// Check if game_stats table exists
$table_check = $conn->query("SHOW TABLES LIKE 'game_stats'");
if ($table_check->num_rows > 0) {
    $query = "SELECT 
        u.id,
        u.name,
        u.profile_pic,
        MAX(gs.score) as high_score,
        COUNT(gs.id) as total_games,
        SUM(gs.score) as total_score
        FROM users u
        LEFT JOIN game_stats gs ON u.id = gs.user_id AND gs.is_practice = 0";
    
    if ($mode !== 'all') {
        $query .= " AND gs.game_mode = '" . $conn->real_escape_string($mode) . "'";
    }
    
    $query .= " GROUP BY u.id, u.name, u.profile_pic
        HAVING high_score > 0
        ORDER BY high_score DESC
        LIMIT 10";
    
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $leaderboard[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'profile_pic' => $row['profile_pic'],
                'high_score' => intval($row['high_score']),
                'total_games' => intval($row['total_games']),
                'total_score' => intval($row['total_score'])
            ];
        }
    }
}

$conn->close();

echo json_encode([
    'success' => true,
    'mode' => $mode,
    'leaderboard' => $leaderboard
]);
?>
