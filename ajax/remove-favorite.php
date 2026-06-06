<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$userId = $_SESSION['user_id'];
$videoId = $_POST['video_id'] ?? 0;

if ($videoId) {
    $stmt = $db->prepare("DELETE FROM user_favorites WHERE user_id = ? AND video_id = ?");
    $success = $stmt->execute([$userId, $videoId]);
    
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid video ID']);
}