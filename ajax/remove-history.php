<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$userId = (int) $_SESSION['user_id'];
$videoId = (int) ($_POST['video_id'] ?? 0);

if ($videoId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid video ID']);
    exit();
}

$stmt = $db->prepare('DELETE FROM watch_history WHERE user_id = ? AND video_id = ?');
$success = $stmt->execute([$userId, $videoId]);

echo json_encode(['success' => $success]);
