<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$activeClass = static function (array $pages) use ($currentPage): string {
    return in_array($currentPage, $pages, true) ? 'active' : '';
};
?>

<aside class="admin-sidebar">
    <div class="sidebar-header">
        <h2>OTT Admin</h2>
        <p><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrator'); ?></p>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="dashboard.php" class="<?php echo $activeClass(['dashboard.php']); ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="manage-videos.php" class="<?php echo $activeClass(['manage-videos.php']); ?>">
                    <i class="fas fa-video"></i>
                    <span>Manage Videos</span>
                </a>
            </li>
            <li>
                <a href="upload-video.php" class="<?php echo $activeClass(['upload-video.php']); ?>">
                    <i class="fas fa-upload"></i>
                    <span>Upload Video</span>
                </a>
            </li>
            <li>
                <a href="manage-categories.php" class="<?php echo $activeClass(['manage-categories.php']); ?>">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
            </li>
            <li>
                <a href="manage-users.php" class="<?php echo $activeClass(['manage-users.php']); ?>">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>
            <li>
                <a href="subscriptions.php" class="<?php echo $activeClass(['subscriptions.php']); ?>">
                    <i class="fas fa-crown"></i>
                    <span>Subscriptions</span>
                </a>
            </li>
            <li>
                <a href="reports.php" class="<?php echo $activeClass(['reports.php']); ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-globe"></i>
                    <span>View Site</span>
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
