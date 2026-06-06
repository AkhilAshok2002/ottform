<?php
require_once 'includes/db.php';
try {
    $db = Database::getInstance()->getConnection();
    $rs = $db->query("SHOW COLUMNS FROM users");
    $columns = $rs->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
