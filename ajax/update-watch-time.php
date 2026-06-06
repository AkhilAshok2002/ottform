<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$videoId = $_POST['video_id'] ?? 0;
$watchTime = $_POST['watch_time'] ?? 0;

if (!$videoId) {
    echo json_encode(['error' => 'Invalid video ID']);
    exit();
}

$video = new VideoFunctions();
$result = $video->addToWatchHistory($_SESSION['user_id'], $videoId, $watchTime);

echo json_encode(['success' => $result]);
?>