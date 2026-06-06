<?php
require_once 'includes/db.php';
try {
    $db = Database::getInstance()->getConnection();
    
    // Check if category 1 exists, if not, create it
    $catStmt = $db->query("SELECT id FROM categories LIMIT 1");
    $cat = $catStmt->fetch();
    $categoryId = $cat ? $cat['id'] : 1;
    if (!$cat) {
        $db->query("INSERT INTO categories (id, name, type) VALUES (1, 'Action', 'movie')");
    }

    // Insert Premium Video
    $db->prepare("
        INSERT INTO videos (title, description, category_id, thumbnail_path, video_path, duration, release_year, type, featured, access_level, created_at)
        VALUES ('Test Premium Movie', 'This is a test premium movie to show the crown badge.', ?, 'dummy.jpg', 'dummy.mp4', '1h 30m', '2025', 'movie', 1, 'premium', NOW())
    ")->execute([$categoryId]);

    // Insert Free Video
    $db->prepare("
        INSERT INTO videos (title, description, category_id, thumbnail_path, video_path, duration, release_year, type, featured, access_level, created_at)
        VALUES ('Test Free Movie', 'This is a test free movie without a badge.', ?, 'dummy2.jpg', 'dummy2.mp4', '2h', '2024', 'movie', 1, 'free', NOW())
    ")->execute([$categoryId]);

    echo "Successfully inserted test videos!\n";
} catch (Exception $e) {
    echo "Error inserting test data: " . $e->getMessage() . "\n";
}
