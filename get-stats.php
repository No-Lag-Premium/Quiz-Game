<?php
header('Content-Type: application/json');

include 'userdb.php';

$response = [
    'success' => true,
    'totalPlayers' => 0,
    'gamesPlayed' => 0,
    'problemsSolved' => 0,
    'highScore' => 0
];

try {
    // Get total players (registered users)
    $result = $conn->query("SELECT COUNT(*) as total FROM users");
    if ($result && $row = $result->fetch_assoc()) {
        $response['totalPlayers'] = intval($row['total']);
    }
    
    // Get games played (excluding practice mode)
    $tableCheck = $conn->query("SHOW TABLES LIKE 'game_stats'");
    if ($tableCheck->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as total FROM game_stats WHERE is_practice = 0");
        if ($result && $row = $result->fetch_assoc()) {
            $response['gamesPlayed'] = intval($row['total']);
        }
        
        // Get problems solved (sum of total_questions, excluding practice mode)
        $result = $conn->query("SELECT SUM(total_questions) as total FROM game_stats WHERE is_practice = 0");
        if ($result && $row = $result->fetch_assoc()) {
            $response['problemsSolved'] = intval($row['total'] ?: 0);
        }
        
        // Get high score (excluding practice mode)
        $result = $conn->query("SELECT MAX(score) as high_score FROM game_stats WHERE is_practice = 0");
        if ($result && $row = $result->fetch_assoc()) {
            $response['highScore'] = intval($row['high_score'] ?: 0);
        }
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

$conn->close();

echo json_encode($response);
?>
