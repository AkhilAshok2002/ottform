<?php
require_once 'db.php';

class VideoFunctions {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getFeaturedVideos() {
        $stmt = $this->db->query("
            SELECT v.*, c.name as category_name 
            FROM videos v 
            LEFT JOIN categories c ON v.category_id = c.id 
            WHERE v.featured = 1 
            ORDER BY v.created_at DESC 
            LIMIT 10
        ");
        return $stmt->fetchAll();
    }
    
    public function getTrendingVideos() {
        $stmt = $this->db->query("
            SELECT v.*, c.name as category_name,
                   COUNT(w.id) as watch_count
            FROM videos v 
            LEFT JOIN categories c ON v.category_id = c.id
            LEFT JOIN watch_history w ON v.id = w.video_id
            WHERE w.last_watched >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY v.id
            ORDER BY watch_count DESC, v.views DESC
            LIMIT 20
        ");
        return $stmt->fetchAll();
    }
    
    public function getLatestVideos() {
        $stmt = $this->db->query("
            SELECT v.*, c.name as category_name 
            FROM videos v 
            LEFT JOIN categories c ON v.category_id = c.id 
            ORDER BY v.created_at DESC 
            LIMIT 20
        ");
        return $stmt->fetchAll();
    }
    
    public function getVideosByCategory($categoryId) {
        $stmt = $this->db->prepare("
            SELECT v.*, c.name as category_name 
            FROM videos v 
            LEFT JOIN categories c ON v.category_id = c.id 
            WHERE v.category_id = ?
            ORDER BY v.created_at DESC
        ");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }
    
    public function getVideoDetails($videoId) {
        $stmt = $this->db->prepare("
            SELECT v.*, c.name as category_name 
            FROM videos v 
            LEFT JOIN categories c ON v.category_id = c.id 
            WHERE v.id = ?
        ");
        $stmt->execute([$videoId]);
        return $stmt->fetch();
    }
    
    public function searchVideos($query) {
        $search = "%$query%";
        $stmt = $this->db->prepare("
            SELECT v.*, c.name as category_name 
            FROM videos v 
            LEFT JOIN categories c ON v.category_id = c.id 
            WHERE v.title LIKE ? OR v.description LIKE ?
            ORDER BY v.views DESC
            LIMIT 50
        ");
        $stmt->execute([$search, $search]);
        return $stmt->fetchAll();
    }
    
    public function incrementViews($videoId) {
        $stmt = $this->db->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
        $stmt->execute([$videoId]);
    }
    
    public function addToWatchHistory($userId, $videoId, $watchTime = 0) {
        // Check if exists
        $stmt = $this->db->prepare("
            SELECT id FROM watch_history 
            WHERE user_id = ? AND video_id = ?
        ");
        $stmt->execute([$userId, $videoId]);
        
        if ($stmt->fetch()) {
            // Update
            $stmt = $this->db->prepare("
                UPDATE watch_history 
                SET watch_time = ?, last_watched = NOW() 
                WHERE user_id = ? AND video_id = ?
            ");
            return $stmt->execute([$watchTime, $userId, $videoId]);
        } else {
            // Insert
            $stmt = $this->db->prepare("
                INSERT INTO watch_history (user_id, video_id, watch_time) 
                VALUES (?, ?, ?)
            ");
            return $stmt->execute([$userId, $videoId, $watchTime]);
        }
    }
    
    public function getWatchHistory($userId) {
        $stmt = $this->db->prepare("
            SELECT v.*, wh.watch_time, wh.last_watched
            FROM watch_history wh
            JOIN videos v ON wh.video_id = v.id
            WHERE wh.user_id = ?
            ORDER BY wh.last_watched DESC
            LIMIT 30
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function getRecommendations($userId) {
        // Get user's most watched genres
        $stmt = $this->db->prepare("
            SELECT v.category_id, COUNT(*) as count
            FROM watch_history wh
            JOIN videos v ON wh.video_id = v.id
            WHERE wh.user_id = ?
            GROUP BY v.category_id
            ORDER BY count DESC
            LIMIT 3
        ");
        $stmt->execute([$userId]);
        $genres = $stmt->fetchAll();
        
        if (empty($genres)) {
            // If no history, return popular videos
            return $this->getTrendingVideos();
        }
        
        $genreIds = array_column($genres, 'category_id');
        $placeholders = implode(',', array_fill(0, count($genreIds), '?'));
        
        // Get videos from those genres, excluding watched ones
        $stmt = $this->db->prepare("
            SELECT DISTINCT v.*, c.name as category_name
            FROM videos v
            LEFT JOIN categories c ON v.category_id = c.id
            WHERE v.category_id IN ($placeholders)
            AND v.id NOT IN (
                SELECT video_id FROM watch_history WHERE user_id = ?
            )
            ORDER BY v.views DESC
            LIMIT 20
        ");
        
        $params = array_merge($genreIds, [$userId]);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function toggleFavorite($userId, $videoId) {
        // Check if exists
        $stmt = $this->db->prepare("
            SELECT id FROM user_favorites 
            WHERE user_id = ? AND video_id = ?
        ");
        $stmt->execute([$userId, $videoId]);
        
        if ($stmt->fetch()) {
            // Remove
            $stmt = $this->db->prepare("
                DELETE FROM user_favorites 
                WHERE user_id = ? AND video_id = ?
            ");
            $stmt->execute([$userId, $videoId]);
            return ['action' => 'removed'];
        } else {
            // Add
            $stmt = $this->db->prepare("
                INSERT INTO user_favorites (user_id, video_id) 
                VALUES (?, ?)
            ");
            $stmt->execute([$userId, $videoId]);
            return ['action' => 'added'];
        }
    }
    
    public function getFavorites($userId) {
        $stmt = $this->db->prepare("
            SELECT v.*, c.name as category_name
            FROM user_favorites uf
            JOIN videos v ON uf.video_id = v.id
            LEFT JOIN categories c ON v.category_id = c.id
            WHERE uf.user_id = ?
            ORDER BY uf.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function isFavorite($userId, $videoId) {
        $stmt = $this->db->prepare("
            SELECT id FROM user_favorites 
            WHERE user_id = ? AND video_id = ?
        ");
        $stmt->execute([$userId, $videoId]);
        return $stmt->fetch() ? true : false;
    }
}

// Initialize video functions
$video = new VideoFunctions();
?>