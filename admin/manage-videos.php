<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Check admin access
$auth->requireAdmin();

$message = '';
$error = '';

// Handle video deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get video file paths first
    $stmt = $db->prepare("SELECT thumbnail_path, video_path FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $video = $stmt->fetch();
    
    if ($video) {
        // Delete files
        if ($video['thumbnail_path'] && file_exists(THUMBNAIL_PATH . $video['thumbnail_path'])) {
            unlink(THUMBNAIL_PATH . $video['thumbnail_path']);
        }
        if ($video['video_path'] && file_exists(VIDEO_PATH . $video['video_path'])) {
            unlink(VIDEO_PATH . $video['video_path']);
        }
        
        // Delete from database
        $stmt = $db->prepare("DELETE FROM videos WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = 'Video deleted successfully!';
        } else {
            $error = 'Failed to delete video';
        }
    }
}

// Toggle featured status
if (isset($_GET['featured'])) {
    $id = $_GET['featured'];
    $stmt = $db->prepare("UPDATE videos SET featured = NOT featured WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Featured status updated!';
}

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Search and filter
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$type = $_GET['type'] ?? '';

$query = "SELECT v.*, c.name as category_name FROM videos v LEFT JOIN categories c ON v.category_id = c.id WHERE 1=1";
$countQuery = "SELECT COUNT(*) as count FROM videos v LEFT JOIN categories c ON v.category_id = c.id WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND v.title LIKE ?";
    $countQuery .= " AND title LIKE ?";
    $params[] = "%$search%";
}

if ($category) {
    $query .= " AND v.category_id = ?";
    $countQuery .= " AND category_id = ?";
    $params[] = $category;
}

if ($type) {
    $query .= " AND c.type = ?";
    $countQuery .= " AND c.type = ?";
    $params[] = $type;
}

// Get total count
$stmt = $db->prepare($countQuery);
$stmt->execute($params);
$totalVideos = $stmt->fetch()['count'];

// Get videos for current page
$query .= " ORDER BY v.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $db->prepare($query);
$stmt->execute($params);
$videos = $stmt->fetchAll();

$totalPages = ceil($totalVideos / $limit);

// Get categories for filter
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Videos - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1>Manage Videos</h1>
                <a href="upload-video.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Upload New Video
                </a>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="Search videos..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name'] . ' (' . ucfirst($cat['type']) . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select name="type">
                            <option value="">All Types</option>
                            <option value="movie" <?php echo $type == 'movie' ? 'selected' : ''; ?>>Movies</option>
                            <option value="series" <?php echo $type == 'series' ? 'selected' : ''; ?>>Series</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?" class="btn btn-outline">Clear</a>
                </form>
            </div>
            
            <!-- Videos Grid -->
            <div class="videos-grid">
                <?php foreach ($videos as $video): ?>
                <div class="video-card">
                    <div class="video-thumbnail">
                        <img src="<?php echo THUMBNAIL_URL . $video['thumbnail_path']; ?>" 
                             alt="<?php echo htmlspecialchars($video['title']); ?>">
                        <?php if ($video['featured']): ?>
                        <span class="featured-badge">Featured</span>
                        <?php endif; ?>
                        <span class="duration-badge"><?php echo $video['duration']; ?></span>
                    </div>
                    <div class="video-info">
                        <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                        <p class="video-meta">
                            <span class="category"><?php echo htmlspecialchars($video['category_name']); ?></span>
                            <span class="views"><i class="fas fa-eye"></i> <?php echo number_format($video['views']); ?></span>
                        </p>
                        <div class="video-actions">
                            <a href="?featured=<?php echo $video['id']; ?>" class="btn-icon" title="Toggle Featured">
                                <i class="fas fa-star <?php echo $video['featured'] ? 'active' : ''; ?>"></i>
                            </a>
                            <a href="edit-video.php?id=<?php echo $video['id']; ?>" class="btn-icon" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?delete=<?php echo $video['id']; ?>" class="btn-icon delete" 
                               onclick="return confirm('Delete this video? This action cannot be undone.')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&type=<?php echo $type; ?>" 
                   class="page-btn">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&type=<?php echo $type; ?>" 
                   class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&type=<?php echo $type; ?>" 
                   class="page-btn">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <style>
    .filters-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .filters-form {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .filter-group {
        flex: 1;
        min-width: 200px;
    }
    
    .filter-group input,
    .filter-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
    }
    
    .videos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .video-card {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .video-thumbnail {
        position: relative;
        aspect-ratio: 16/9;
    }
    
    .video-thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .featured-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        background: gold;
        color: #000;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .duration-badge {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
    }
    
    .video-info {
        padding: 15px;
    }
    
    .video-info h3 {
        margin-bottom: 5px;
        font-size: 16px;
    }
    
    .video-meta {
        display: flex;
        justify-content: space-between;
        color: #6b7280;
        font-size: 13px;
        margin-bottom: 10px;
    }
    
    .video-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        border-top: 1px solid #e5e7eb;
        padding-top: 10px;
    }
    
    .fa-star.active {
        color: gold;
    }
    </style>
</body>
</html>