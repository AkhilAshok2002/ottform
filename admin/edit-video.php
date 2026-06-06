<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Check admin access
$auth->requireAdmin();

$message = '';
$error = '';

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

$allowedImageTypes = @unserialize(ALLOWED_IMAGE_TYPES);
if (!is_array($allowedImageTypes)) {
	$allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
}

$allowedVideoTypes = @unserialize(ALLOWED_VIDEO_TYPES);
if (!is_array($allowedVideoTypes)) {
	$allowedVideoTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
}

$videoId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($videoId <= 0) {
	header('Location: manage-videos.php');
	exit();
}

$getVideoById = static function (PDO $db, int $id) {
	$stmt = $db->prepare("SELECT * FROM videos WHERE id = ? LIMIT 1");
	$stmt->execute([$id]);
	return $stmt->fetch();
};

$videoRecord = $getVideoById($db, $videoId);
if (!$videoRecord) {
	header('Location: manage-videos.php');
	exit();
}

// Get categories for dropdown
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
	$postMaxBytes = $toBytes((string) ini_get('post_max_size'));
	$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

	if ($contentLength > 0 && empty($_POST) && empty($_FILES) && $contentLength > $postMaxBytes) {
		$error = 'Upload failed: request size (' . $formatBytes($contentLength) . ') exceeds server limit (' . ini_get('post_max_size') . ').';
	} else {
		$title = trim($_POST['title'] ?? '');
		$description = trim($_POST['description'] ?? '');
		$categoryId = (int) ($_POST['category_id'] ?? 0);
		$duration = trim($_POST['duration'] ?? '');
		$releaseYearInput = trim($_POST['release_year'] ?? '');
		$videoType = null;
		$ratingInput = trim($_POST['rating'] ?? '0');
		$featured = isset($_POST['featured']) ? 1 : 0;
		$access_level = $_POST['access_level'] ?? 'free';
		if (!in_array($access_level, ['free', 'premium'], true)) {
			$access_level = 'free';
		}

		if ($title === '' || $categoryId <= 0) {
			$error = 'Please fill in required fields';
		}

		if (!$error) {
			$categoryStmt = $db->prepare("SELECT type FROM categories WHERE id = ? LIMIT 1");
			$categoryStmt->execute([$categoryId]);
			$categoryData = $categoryStmt->fetch();

			if (!$categoryData) {
				$error = 'Selected category not found';
			} else {
				$videoType = $categoryData['type'] ?? null;
			}

			if (!$error && !in_array($videoType, ['movie', 'series'], true)) {
				$error = 'Invalid category type selected';
			}
		}

		$releaseYear = null;
		if ($releaseYearInput !== '') {
			$releaseYear = (int) $releaseYearInput;
			$currentYear = (int) date('Y');
			if ($releaseYear < 1900 || $releaseYear > $currentYear) {
				$error = 'Release year must be between 1900 and ' . $currentYear . '.';
			}
		}

		$rating = is_numeric($ratingInput) ? (float) $ratingInput : -1;
		if ($rating < 0 || $rating > 10) {
			$error = 'Rating must be between 0 and 10.';
		}

		$newThumbnailPath = $videoRecord['thumbnail_path'];
		$newVideoPath = $videoRecord['video_path'];
		$newThumbnailAbsolute = null;
		$newVideoAbsolute = null;
		$replaceThumbnail = false;
		$replaceVideo = false;

		// Optional thumbnail replacement
		$thumbnail = $_FILES['thumbnail'] ?? null;
		if (!$error && $thumbnail && ($thumbnail['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
			if (in_array($thumbnail['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
				$error = 'Thumbnail image is larger than the server upload limit (' . ini_get('upload_max_filesize') . ').';
			} elseif ($thumbnail['error'] !== UPLOAD_ERR_OK) {
				$error = 'Thumbnail upload failed with error code ' . (int) $thumbnail['error'] . '.';
			} elseif (($thumbnail['size'] ?? 0) > MAX_IMAGE_SIZE) {
				$error = 'Thumbnail exceeds application limit of ' . $formatBytes(MAX_IMAGE_SIZE) . '.';
			} elseif (!in_array($thumbnail['type'] ?? '', $allowedImageTypes, true)) {
				$error = 'Invalid thumbnail type. Allowed: JPG, PNG, GIF, WEBP.';
			} else {
				$ext = strtolower((string) pathinfo($thumbnail['name'], PATHINFO_EXTENSION));
				$filename = uniqid('thumb_', true) . ($ext !== '' ? '.' . $ext : '');
				$newThumbnailAbsolute = THUMBNAIL_PATH . $filename;

				if (!move_uploaded_file($thumbnail['tmp_name'], $newThumbnailAbsolute)) {
					$error = 'Failed to upload thumbnail image.';
				} else {
					$newThumbnailPath = $filename;
					$replaceThumbnail = true;
				}
			}
		}

		// Optional video replacement
		$videoFile = $_FILES['video_file'] ?? null;
		if (!$error && $videoFile && ($videoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
			if (in_array($videoFile['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
				$error = 'Video file is larger than the server upload limit (' . ini_get('upload_max_filesize') . ').';
			} elseif ($videoFile['error'] !== UPLOAD_ERR_OK) {
				$error = 'Video upload failed with error code ' . (int) $videoFile['error'] . '.';
			} elseif (($videoFile['size'] ?? 0) > MAX_VIDEO_SIZE) {
				$error = 'Video exceeds application limit of ' . $formatBytes(MAX_VIDEO_SIZE) . '.';
			} elseif (!in_array($videoFile['type'] ?? '', $allowedVideoTypes, true)) {
				$error = 'Invalid video type. Allowed: MP4, WEBM, OGG, MOV.';
			} else {
				$ext = strtolower((string) pathinfo($videoFile['name'], PATHINFO_EXTENSION));
				$filename = uniqid('video_', true) . ($ext !== '' ? '.' . $ext : '');
				$newVideoAbsolute = VIDEO_PATH . $filename;

				if (!move_uploaded_file($videoFile['tmp_name'], $newVideoAbsolute)) {
					$error = 'Failed to upload video file.';
				} else {
					$newVideoPath = $filename;
					$replaceVideo = true;
				}
			}
		}

		if (!$error) {
			$stmt = $db->prepare(" 
				UPDATE videos 
				SET title = ?, description = ?, category_id = ?, thumbnail_path = ?, video_path = ?, duration = ?, release_year = ?, type = ?, rating = ?, featured = ?, access_level = ?
				WHERE id = ?
			");

			$updated = $stmt->execute([
				$title,
				$description,
				$categoryId,
				$newThumbnailPath,
				$newVideoPath,
				$duration,
				$releaseYear,
				$videoType,
				$rating,
				$featured,
				$access_level,
				$videoId,
			]);

			if ($updated) {
				if ($replaceThumbnail && !empty($videoRecord['thumbnail_path'])) {
					$oldThumbAbsolute = THUMBNAIL_PATH . $videoRecord['thumbnail_path'];
					if (is_file($oldThumbAbsolute)) {
						unlink($oldThumbAbsolute);
					}
				}

				if ($replaceVideo && !empty($videoRecord['video_path'])) {
					$oldVideoAbsolute = VIDEO_PATH . $videoRecord['video_path'];
					if (is_file($oldVideoAbsolute)) {
						unlink($oldVideoAbsolute);
					}
				}

				$logStmt = $db->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, 'edit_video', ?)");
				$logStmt->execute([$_SESSION['user_id'], 'Updated video: ' . $title . ' (ID: ' . $videoId . ')']);

				$message = 'Video updated successfully!';
				$videoRecord = $getVideoById($db, $videoId);
			} else {
				$error = 'Failed to update video';
			}
		}

		// Roll back newly uploaded files on error to avoid orphaned files.
		if ($error) {
			if ($replaceThumbnail && $newThumbnailAbsolute && is_file($newThumbnailAbsolute)) {
				unlink($newThumbnailAbsolute);
			}
			if ($replaceVideo && $newVideoAbsolute && is_file($newVideoAbsolute)) {
				unlink($newVideoAbsolute);
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
	<title>Edit Video - Admin</title>
	<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
	<div class="admin-container">
		<?php include __DIR__ . '/includes/sidebar.php'; ?>

		<main class="admin-main">
			<header class="admin-header">
				<h1>Edit Video</h1>
				<a href="manage-videos.php" class="btn btn-outline">
					<i class="fas fa-arrow-left"></i> Back to Videos
				</a>
			</header>

			<?php if ($message): ?>
				<div class="alert alert-success"><?php echo $message; ?></div>
			<?php endif; ?>

			<?php if ($error): ?>
				<div class="alert alert-error"><?php echo $error; ?></div>
			<?php endif; ?>

			<div class="form-container">
				<form method="POST" action="" enctype="multipart/form-data" class="admin-form">
					<input type="hidden" name="id" value="<?php echo (int) $videoRecord['id']; ?>">

					<div class="form-group">
						<label for="title">Title *</label>
						<input type="text" id="title" name="title" required value="<?php echo htmlspecialchars((string) $videoRecord['title']); ?>">
					</div>

					<div class="form-group">
						<label for="description">Description</label>
						<textarea id="description" name="description" rows="5"><?php echo htmlspecialchars((string) ($videoRecord['description'] ?? '')); ?></textarea>
					</div>

					<div class="form-row">
						<div class="form-group">
							<label for="category_id">Category *</label>
							<select id="category_id" name="category_id" required>
								<option value="">Select Category</option>
								<?php foreach ($categories as $category): ?>
									<option value="<?php echo (int) $category['id']; ?>" <?php echo ((int) $videoRecord['category_id'] === (int) $category['id']) ? 'selected' : ''; ?>>
										<?php echo htmlspecialchars((string) $category['name'] . ' (' . ucfirst((string) $category['type']) . ')'); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="form-group">
							<label>Type</label>
							<p class="static-info">Automatically set from selected category</p>
						</div>
					</div>

					<div class="form-row">
						<div class="form-group">
							<label for="duration">Duration (e.g., 2h 30m)</label>
							<input type="text" id="duration" name="duration" value="<?php echo htmlspecialchars((string) ($videoRecord['duration'] ?? '')); ?>">
						</div>

						<div class="form-group">
							<label for="release_year">Release Year</label>
							<input type="number" id="release_year" name="release_year" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo htmlspecialchars((string) ($videoRecord['release_year'] ?? '')); ?>">
						</div>
					</div>

					<div class="form-row">
						<div class="form-group">
							<label for="rating">Rating (0-10)</label>
							<input type="number" id="rating" name="rating" step="0.1" min="0" max="10" value="<?php echo htmlspecialchars((string) ($videoRecord['rating'] ?? '0')); ?>">
						</div>

						<div class="form-group">
							<label for="access_level">Access Level *</label>
							<select id="access_level" name="access_level" required>
								<option value="free" <?php echo ($videoRecord['access_level'] ?? 'free') === 'free' ? 'selected' : ''; ?>>Free Access</option>
								<option value="premium" <?php echo ($videoRecord['access_level'] ?? '') === 'premium' ? 'selected' : ''; ?>>Premium Access</option>
							</select>
						</div>
					</div>

					<div class="form-row">
						<div class="form-group">
							<label class="checkbox-label">
								<input type="checkbox" name="featured" <?php echo !empty($videoRecord['featured']) ? 'checked' : ''; ?>> Featured Video
							</label>
						</div>
					</div>

					<div class="current-media">
						<h3>Current Media</h3>
						<div class="media-grid">
							<div class="media-card">
								<h4>Thumbnail</h4>
								<?php if (!empty($videoRecord['thumbnail_path'])): ?>
									<img src="<?php echo THUMBNAIL_URL . $videoRecord['thumbnail_path']; ?>" alt="Current thumbnail">
									<p><?php echo htmlspecialchars((string) $videoRecord['thumbnail_path']); ?></p>
								<?php else: ?>
									<p>No thumbnail uploaded.</p>
								<?php endif; ?>
							</div>

							<div class="media-card">
								<h4>Video File</h4>
								<?php if (!empty($videoRecord['video_path'])): ?>
									<p><?php echo htmlspecialchars((string) $videoRecord['video_path']); ?></p>
									<a href="<?php echo VIDEO_URL . $videoRecord['video_path']; ?>" target="_blank" rel="noopener noreferrer" class="view-link">Open Current Video</a>
								<?php else: ?>
									<p>No video file uploaded.</p>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<div class="form-group">
						<label for="thumbnail">Replace Thumbnail (optional)</label>
						<input type="file" id="thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/gif,image/webp">
						<small>Allowed: JPG, PNG, GIF, WEBP. Max: <?php echo round(MAX_IMAGE_SIZE / (1024 * 1024)); ?>MB.</small>
					</div>

					<div class="form-group">
						<label for="video_file">Replace Video File (optional)</label>
						<input type="file" id="video_file" name="video_file" accept="video/mp4,video/webm,video/ogg,video/quicktime">
						<small>
							Application limit: <?php echo round(MAX_VIDEO_SIZE / (1024 * 1024)); ?>MB.
							Server limits: <?php echo ini_get('upload_max_filesize'); ?> per file,
							<?php echo ini_get('post_max_size'); ?> per request.
						</small>
					</div>

					<div class="form-actions">
						<button type="submit" class="btn btn-primary">Save Changes</button>
						<a href="manage-videos.php" class="btn btn-outline">Cancel</a>
					</div>
				</form>
			</div>
		</main>
	</div>

	<style>
	.current-media {
		margin: 10px 0 20px;
		padding: 15px;
		background: #f9fafb;
		border: 1px solid #e5e7eb;
		border-radius: 8px;
	}

	.current-media h3 {
		margin-bottom: 12px;
		font-size: 16px;
	}

	.media-grid {
		display: grid;
		gap: 15px;
		grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
	}

	.media-card {
		background: white;
		border: 1px solid #e5e7eb;
		border-radius: 8px;
		padding: 12px;
	}

	.media-card h4 {
		margin-bottom: 8px;
		font-size: 14px;
		color: #374151;
	}

	.media-card img {
		width: 100%;
		border-radius: 6px;
		margin-bottom: 8px;
		aspect-ratio: 16 / 9;
		object-fit: cover;
	}

	.media-card p {
		font-size: 12px;
		color: #6b7280;
		word-break: break-all;
	}

	.view-link {
		display: inline-block;
		margin-top: 8px;
		color: var(--admin-primary);
		text-decoration: none;
		font-size: 13px;
	}

	.view-link:hover {
		text-decoration: underline;
	}

	.static-info {
		margin-top: 10px;
		color: #6b7280;
		font-size: 14px;
	}
	</style>
</body>
</html>
