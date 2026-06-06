<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check admin access
$auth->requireAdmin();

$message = '';
$error = '';

// Handle plan updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_plan'])) {
        $id = $_POST['id'];
        $plan_name = $_POST['plan_name'];
        $price = $_POST['price'];
        $duration_days = $_POST['duration_days'];
        $max_quality = $_POST['max_quality'];
        
        $stmt = $db->prepare("UPDATE subscription_plans SET plan_name = ?, price = ?, duration_days = ?, max_quality = ? WHERE id = ?");
        if ($stmt->execute([$plan_name, $price, $duration_days, $max_quality, $id])) {
            $message = 'Plan updated successfully!';
        } else {
            $error = 'Failed to update plan';
        }
    }
}

// Get all subscription plans
$plans = $db->query("SELECT * FROM subscription_plans ORDER BY price")->fetchAll();

// Get recent payments
$recentPayments = $db->query("
    SELECT p.*, u.name as user_name, u.email, sp.plan_name
    FROM payments p
    JOIN users u ON p.user_id = u.id
    JOIN subscription_plans sp ON p.plan_id = sp.id
    ORDER BY p.payment_date DESC
    LIMIT 20
")->fetchAll();

// Get subscription statistics
$stats = [
    'total_subscribers' => $db->query("SELECT COUNT(*) as count FROM users WHERE subscription_status != 'free'")->fetch()['count'],
    'total_revenue' => $db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'")->fetch()['total'] ?? 0,
    'active_subscriptions' => $db->query("SELECT COUNT(*) as count FROM users WHERE subscription_expiry > NOW()")->fetch()['count'],
    'expiring_soon' => $db->query("SELECT COUNT(*) as count FROM users WHERE subscription_expiry BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)")->fetch()['count']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscriptions - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1>Subscription Management</h1>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Subscription Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_subscribers']); ?></h3>
                        <p>Total Subscribers</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹<?php echo formatIndianCurrency($stats['total_revenue']); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['active_subscriptions']); ?></h3>
                        <p>Active Subscriptions</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['expiring_soon']); ?></h3>
                        <p>Expiring Soon</p>
                    </div>
                </div>
            </div>
            
            <!-- Subscription Plans -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Subscription Plans</h2>
                </div>
                <div class="card-body">
                    <div class="plans-grid">
                        <?php foreach ($plans as $plan): ?>
                        <div class="plan-card">
                            <form method="POST" class="plan-form">
                                <input type="hidden" name="update_plan" value="1">
                                <input type="hidden" name="id" value="<?php echo $plan['id']; ?>">
                                
                                <h3>
                                    <input type="text" name="plan_name" value="<?php echo htmlspecialchars($plan['plan_name']); ?>" 
                                           class="plan-name-input">
                                </h3>
                                
                                <div class="plan-price">
                                    <input type="number" name="price" value="<?php echo $plan['price']; ?>" step="0.01" 
                                           class="price-input"> / month
                                </div>
                                
                                <div class="plan-details">
                                    <p>
                                        <i class="fas fa-calendar"></i>
                                        <input type="number" name="duration_days" value="<?php echo $plan['duration_days']; ?>" 
                                               class="duration-input"> days
                                    </p>
                                    <p>
                                        <i class="fas fa-video"></i>
                                        Quality: 
                                        <input type="text" name="max_quality" value="<?php echo $plan['max_quality']; ?>" 
                                               class="quality-input">
                                    </p>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-block">Update Plan</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Payments -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Recent Payments</h2>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Expiry</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPayments as $payment): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($payment['payment_date'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($payment['user_name']); ?>
                                    <br>
                                    <small><?php echo htmlspecialchars($payment['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($payment['plan_name']); ?></td>
                                <td>₹<?php echo formatIndianCurrency($payment['amount']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $payment['status']; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($payment['expiry_date'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <style>
    .plans-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .plan-card {
        background: #f9fafb;
        border-radius: 8px;
        padding: 20px;
        border: 1px solid #e5e7eb;
    }
    
    .plan-name-input {
        font-size: 20px;
        font-weight: bold;
        width: 100%;
        padding: 5px;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
        text-align: center;
    }
    
    .plan-price {
        text-align: center;
        margin: 15px 0;
        font-size: 18px;
    }
    
    .price-input {
        width: 80px;
        padding: 5px;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
        text-align: center;
    }
    
    .plan-details p {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }
    
    .duration-input,
    .quality-input {
        width: 60px;
        padding: 3px;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
    }
    
    .badge-pending {
        background: #fef3c7;
        color: #92400e;
    }
    
    .badge-completed {
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-failed {
        background: #fee2e2;
        color: #991b1b;
    }
    </style>
</body>
</html>