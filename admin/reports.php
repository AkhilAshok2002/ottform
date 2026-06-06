<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check admin access
$auth->requireAdmin();

// Get date range
$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-30 days'));

if (isset($_GET['start']) && isset($_GET['end'])) {
    $startDate = $_GET['start'];
    $endDate = $_GET['end'];
}

// Get overview statistics
$stats = [];

// Total users
$stats['total_users'] = $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];

// New users in period
$stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$startDate, $endDate]);
$stats['new_users'] = $stmt->fetch()['count'];

// Total videos
$stats['total_videos'] = $db->query("SELECT COUNT(*) as count FROM videos")->fetch()['count'];

// Total views
$stats['total_views'] = $db->query("SELECT SUM(views) as total FROM videos")->fetch()['total'] ?? 0;

// Revenue
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed' AND DATE(payment_date) BETWEEN ? AND ?");
$stmt->execute([$startDate, $endDate]);
$stats['revenue'] = $stmt->fetch()['total'];

// Subscription breakdown
$subscriptions = $db->query("
    SELECT subscription_status, COUNT(*) as count 
    FROM users 
    GROUP BY subscription_status
")->fetchAll();

// Top videos
$topVideos = $db->query("
    SELECT v.title, v.views, COUNT(w.id) as unique_watches
    FROM videos v
    LEFT JOIN watch_history w ON v.id = w.video_id
    GROUP BY v.id
    ORDER BY v.views DESC
    LIMIT 10
")->fetchAll();

// Daily views for chart
$dailyViews = $db->prepare("
    SELECT DATE(last_watched) as date, COUNT(*) as views
    FROM watch_history
    WHERE DATE(last_watched) BETWEEN ? AND ?
    GROUP BY DATE(last_watched)
    ORDER BY date
");
$dailyViews->execute([$startDate, $endDate]);
$dailyData = $dailyViews->fetchAll();

// Category distribution
$categoryStats = $db->query("
    SELECT c.name, COUNT(v.id) as video_count
    FROM categories c
    LEFT JOIN videos v ON c.id = v.category_id
    GROUP BY c.id
    ORDER BY video_count DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1>Reports & Analytics</h1>
            </header>
            
            <!-- Date Range Filter -->
            <div class="date-filter">
                <form method="GET" class="date-form">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end" value="<?php echo $endDate; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Apply</button>
                </form>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p>Total Users</p>
                        <small>+<?php echo $stats['new_users']; ?> new</small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_videos']); ?></h3>
                        <p>Total Videos</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_views']); ?></h3>
                        <p>Total Views</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹<?php echo formatIndianCurrency($stats['revenue']); ?></h3>
                        <p>Revenue (Period)</p>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="charts-row">
                <!-- Daily Views Chart -->
                <div class="chart-card">
                    <h2>Daily Views</h2>
                    <canvas id="viewsChart"></canvas>
                </div>
                
                <!-- Subscription Distribution -->
                <div class="chart-card">
                    <h2>Subscription Distribution</h2>
                    <canvas id="subscriptionChart"></canvas>
                </div>
            </div>
            
            <!-- Top Videos Table -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Top 10 Most Viewed Videos</h2>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Video Title</th>
                                <th>Total Views</th>
                                <th>Unique Watches</th>
                                <th>Engagement</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topVideos as $index => $video): ?>
                            <tr>
                                <td>#<?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($video['title']); ?></td>
                                <td><?php echo number_format($video['views']); ?></td>
                                <td><?php echo number_format($video['unique_watches']); ?></td>
                                <td>
                                    <?php 
                                    $engagement = $video['views'] > 0 ? round(($video['unique_watches'] / $video['views']) * 100, 1) : 0;
                                    ?>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo $engagement; ?>%"></div>
                                        <span><?php echo $engagement; ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Category Distribution -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Content by Category</h2>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Videos</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categoryStats as $cat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td><?php echo $cat['video_count']; ?></td>
                                <td>
                                    <?php 
                                    $percentage = $stats['total_videos'] > 0 ? round(($cat['video_count'] / $stats['total_videos']) * 100, 1) : 0;
                                    ?>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo $percentage; ?>%"></div>
                                        <span><?php echo $percentage; ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <style>
    .date-filter {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .date-form {
        display: flex;
        gap: 20px;
        align-items: flex-end;
    }
    
    .charts-row {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .chart-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .chart-card h2 {
        font-size: 16px;
        margin-bottom: 20px;
        color: #374151;
    }
    
    .progress-bar {
        position: relative;
        height: 20px;
        background: #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .progress-bar .progress {
        height: 100%;
        background: var(--admin-primary);
    }
    
    .progress-bar span {
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        color: white;
        font-size: 11px;
        line-height: 20px;
        text-shadow: 0 0 2px rgba(0,0,0,0.5);
    }
    
    @media (max-width: 768px) {
        .charts-row {
            grid-template-columns: 1fr;
        }
        
        .date-form {
            flex-direction: column;
            align-items: stretch;
        }
    }
    </style>
    
    <script>
    // Daily Views Chart
    const viewsCtx = document.getElementById('viewsChart').getContext('2d');
    new Chart(viewsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($dailyData, 'date')); ?>,
            datasets: [{
                label: 'Daily Views',
                data: <?php echo json_encode(array_column($dailyData, 'views')); ?>,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#e5e7eb'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
    
    // Subscription Chart
    const subCtx = document.getElementById('subscriptionChart').getContext('2d');
    new Chart(subCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($subscriptions, 'subscription_status')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($subscriptions, 'count')); ?>,
                backgroundColor: [
                    '#4f46e5',
                    '#10b981',
                    '#f59e0b',
                    '#ef4444'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    </script>
</body>
</html>