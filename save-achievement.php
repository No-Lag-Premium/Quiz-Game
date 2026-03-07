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

if (!$data || !isset($data['achievement_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

// Do not save achievements for practice mode
if (isset($data['is_practice']) && $data['is_practice']) {
    echo json_encode(['success' => true, 'message' => 'Practice mode - achievements not saved']);
    exit();
}

$user_id = $_SESSION['user_id'];
$achievement_id = $data['achievement_id'];

// Check if achievement already exists
$check_stmt = $conn->prepare("SELECT id FROM user_achievements WHERE user_id = ? AND achievement_id = ?");
$check_stmt->bind_param("is", $user_id, $achievement_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // Achievement already exists
    echo json_encode(['success' => true, 'message' => 'Achievement already unlocked']);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

// Insert achievement
$stmt = $conn->prepare("INSERT INTO user_achievements (user_id, achievement_id, unlocked_at) VALUES (?, ?, NOW())");
$stmt->bind_param("is", $user_id, $achievement_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Achievement saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save achievement: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
