<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check admin access
$auth->requireAdmin();

$message = '';
$error = '';

// Get categories for dropdown
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$toBytes = static function (string $size): int {
    $value = (int) trim($size);
    $unit = strtolower(substr(trim($size), -1));

    switch ($unit) {
        case 'g':
            $value *= 1024;
            // no break
        case 'm':
            $value *= 1024;
            // no break
        case 'k':
            $value *= 1024;
            break;
    }

    return $value;
};

$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . ' GB';
    }
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postMaxBytes = $toBytes((string) ini_get('post_max_size'));
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

    if ($contentLength > 0 && empty($_POST) && empty($_FILES) && $contentLength > $postMaxBytes) {
        $error = 'Upload failed: request size (' . $formatBytes($contentLength) . ') exceeds server limit (' . ini_get('post_max_size') . ').';
    } else {
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $category_id = (int) ($_POST['category_id'] ?? 0);
        $duration = $_POST['duration'] ?? '';
        $release_year = $_POST['release_year'] ?? '';
        $featured = isset($_POST['featured']) ? 1 : 0;
        $access_level = $_POST['access_level'] ?? 'free';
        if (!in_array($access_level, ['free', 'premium'], true)) {
            $access_level = 'free';
        }
        $videoType = null;

        // Validate inputs
        if (empty($title) || empty($category_id)) {
            $error = 'Please fill in required fields';
        } else {
            $categoryStmt = $db->prepare("SELECT type FROM categories WHERE id = ? LIMIT 1");
            $categoryStmt->execute([$category_id]);
            $categoryData = $categoryStmt->fetch();

            if (!$categoryData) {
                $error = 'Selected category not found';
            } else {
                $videoType = $categoryData['type'] ?? null;
            }

            if (!$error && !in_array($videoType, ['movie', 'series'], true)) {
                $error = 'Invalid category type selected';
            }

            $thumbnailPath = '';
            $videoPath = '';

            // Handle thumbnail upload
            if (!$error) {
                $thumbnail = $_FILES['thumbnail'] ?? null;
                if ($thumbnail && $thumbnail['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($thumbnail['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('', true) . '.' . $ext;
                    $uploadPath = THUMBNAIL_PATH . $filename;

                    if (move_uploaded_file($thumbnail['tmp_name'], $uploadPath)) {
                        $thumbnailPath = $filename;
                    } else {
                        $error = 'Failed to upload thumbnail image';
                    }
                } elseif ($thumbnail && $thumbnail['error'] !== UPLOAD_ERR_NO_FILE) {
                    $error = 'Thumbnail upload failed with error code ' . (int) $thumbnail['error'] . '.';
                }
            }

            if (!$error) {
                $videoFile = $_FILES['video_file'] ?? null;
                if (!$videoFile || ($videoFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    $error = 'Please select a video file to upload';
                } elseif (in_array($videoFile['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                    $error = 'Video file is larger than the server upload limit (' . ini_get('upload_max_filesize') . ').';
                } elseif (($videoFile['size'] ?? 0) > MAX_VIDEO_SIZE) {
                    $error = 'Video file exceeds application limit of ' . $formatBytes(MAX_VIDEO_SIZE) . '.';
                } elseif ($videoFile['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Video upload failed with error code ' . (int) $videoFile['error'] . '.';
                } else {
                    $ext = pathinfo($videoFile['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('', true) . '.' . $ext;
                    $uploadPath = VIDEO_PATH . $filename;

                    if (move_uploaded_file($videoFile['tmp_name'], $uploadPath)) {
                        $videoPath = $filename;
                    } else {
                        $error = 'Failed to move uploaded video file';
                    }
                }
            }

            if (!$error) {
                // Insert video
                $stmt = $db->prepare(" 
                    INSERT INTO videos (title, description, category_id, thumbnail_path, video_path, duration, release_year, type, featured, access_level)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                if ($stmt->execute([$title, $description, $category_id, $thumbnailPath, $videoPath, $duration, $release_year, $videoType, $featured, $access_level])) {
                    // Log admin action
                    $logStmt = $db->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, 'upload_video', ?)");
                    $logStmt->execute([$_SESSION['user_id'], "Uploaded $videoType: $title"]);

                    $message = 'Video uploaded successfully!';
                } else {
                    $error = 'Failed to upload video';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video - Admin</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1>Upload Video</h1>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" action="" enctype="multipart/form-data" class="admin-form">
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category_id">Category *</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name'] . ' (' . ucfirst($category['type']) . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration">Duration (e.g., 2h 30m)</label>
                            <input type="text" id="duration" name="duration" placeholder="2h 30m">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="release_year">Release Year</label>
                            <input type="number" id="release_year" name="release_year" min="1900" max="<?php echo date('Y'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="access_level">Access Level *</label>
                            <select id="access_level" name="access_level" required>
                                <option value="free">Free Access</option>
                                <option value="premium">Premium Access</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="featured"> Featured Video
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="thumbnail">Thumbnail Image</label>
                        <input type="file" id="thumbnail" name="thumbnail" accept="image/*">
                        <small>Recommended size: 1280x720px</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="video_file">Video File *</label>
                        <input type="file" id="video_file" name="video_file" accept="video/mp4,video/webm,video/ogg,video/quicktime" required>
                        <small>
                            Application limit: <?php echo round(MAX_VIDEO_SIZE / (1024 * 1024)); ?>MB.
                            Server limits: <?php echo ini_get('upload_max_filesize'); ?> per file,
                            <?php echo ini_get('post_max_size'); ?> per request.
                        </small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Upload Video</button>
                        <a href="manage-videos.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>