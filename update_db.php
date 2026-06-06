<?php
require_once 'includes/config.php';
try {
    $db->exec("ALTER TABLE videos ADD COLUMN access_level ENUM('free', 'premium') DEFAULT 'free'");
    echo "Database updated successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
