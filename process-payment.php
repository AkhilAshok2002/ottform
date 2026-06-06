<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!$auth->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $planId = $_POST['plan_id'] ?? 0;
    
    // Get plan details
    $stmt = $db->prepare("SELECT * FROM subscription_plans WHERE id = ?");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch();
    
    if ($plan) {
        // Calculate expiry date
        $expiryDate = date('Y-m-d', strtotime('+' . $plan['duration_days'] . ' days'));
        
        // Create payment record
        $stmt = $db->prepare("
            INSERT INTO payments (user_id, plan_id, amount, expiry_date, status) 
            VALUES (?, ?, ?, ?, 'completed')
        ");
        $stmt->execute([$userId, $planId, $plan['price'], $expiryDate]);
        
        // Update user subscription
        $stmt = $db->prepare("
            UPDATE users 
            SET subscription_status = ?, subscription_expiry = ? 
            WHERE id = ?
        ");
        $stmt->execute([strtolower($plan['plan_name']), $expiryDate, $userId]);
        
        header('Location: subscription.php?success=Payment successful! Your subscription has been upgraded.');
    } else {
        header('Location: subscription.php?error=Invalid plan selected');
    }
} else {
    header('Location: ' . SITE_URL);
}