<?php
// =============================================
// OTT Streaming Platform - Configuration File
// =============================================

// =============================================
// DATABASE CONFIGURATION
// =============================================

define('DB_HOST', 'localhost');      // Database host (usually localhost)
define('DB_USER', 'root');           // Database username (default for XAMPP)
define('DB_PASS', '');               // Database password (empty for XAMPP)
define('DB_NAME', 'ott_platform');   // Database name

// =============================================
// SITE CONFIGURATION
// =============================================

// Base URL of your application (change this to your actual// --- 5. Base URLs and Paths ---
define('SITE_URL', 'http://localhost/ott anti');

// Optional constants for finer control if needed later
// define('ASSETS_URL', SITE_URL . '/assets');
// define('IMG_URL', SITE_URL . '/assets/images/movies');

// Base physical path (useful for includes and file operations)
// Adjust if your document root is different
define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/ott anti/');
define('UPLOAD_PATH', BASE_PATH . 'assets/uploads/');
define('THUMBNAIL_PATH', UPLOAD_PATH . 'thumbnails/');
define('VIDEO_PATH', UPLOAD_PATH . 'videos/');

define('SITE_NAME', 'OTT Platform');
define('SITE_DESCRIPTION', 'Watch unlimited movies, TV shows, and more');

// URL paths for accessing uploaded files
define('UPLOAD_URL', SITE_URL . '/assets/uploads/');
define('THUMBNAIL_URL', UPLOAD_URL . 'thumbnails/');
define('VIDEO_URL', UPLOAD_URL . 'videos/');

// =============================================
// FILE UPLOAD CONFIGURATION
// =============================================

// Maximum file sizes (in bytes)
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024);      // 5MB for thumbnails
define('MAX_VIDEO_SIZE', 500 * 1024 * 1024);     // 500MB for videos (adjust as needed)

// Allowed file types
define('ALLOWED_IMAGE_TYPES', serialize(['image/jpeg', 'image/png', 'image/gif', 'image/webp']));
define('ALLOWED_VIDEO_TYPES', serialize(['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime']));

// =============================================
// SUBSCRIPTION PLANS
// =============================================

define('PLAN_FREE', 'free');
define('PLAN_BASIC', 'basic');
define('PLAN_PREMIUM', 'premium');

// Plan restrictions
define('FREE_VIDEO_LIMIT', 10);           // Free users can watch first 10 minutes
define('BASIC_QUALITY', '720p');           // Basic plan quality
define('PREMIUM_QUALITY', '1080p');        // Premium plan quality

// =============================================
// SECURITY CONFIGURATION
// =============================================

// Password hashing algorithm (using PHP's default which is currently bcrypt)
define('PASSWORD_ALGO', PASSWORD_DEFAULT);
define('PASSWORD_OPTIONS', serialize(['cost' => 12]));

// Session security
define('SESSION_TIMEOUT', 3600);           // Session timeout in seconds (1 hour)
define('SESSION_NAME', 'ott_session');

// CSRF Protection
define('CSRF_TOKEN_NAME', 'csrf_token');

// =============================================
// PAGINATION SETTINGS
// =============================================

define('ITEMS_PER_PAGE', 20);               // Items per page for pagination
define('MAX_RECENT_ITEMS', 10);              // Max items for recent sections

// =============================================
// VIDEO PLAYER SETTINGS
// =============================================

define('DEFAULT_QUALITY', 'auto');           // Default video quality (auto, 480p, 720p, 1080p)
define('AUTOPLAY', false);                    // Autoplay videos by default
define('VOLUME', 1);                          // Default volume (0 to 1)

// =============================================
// CACHE SETTINGS
// =============================================

define('CACHE_ENABLED', false);               // Enable/disable caching
define('CACHE_PATH', BASE_PATH . 'cache/');
define('CACHE_TIME', 3600);                    // Cache time in seconds

// =============================================
// ERROR REPORTING (Development vs Production)
// =============================================

// Set to false in production
define('DEVELOPMENT_MODE', true);

if (DEVELOPMENT_MODE) {
    // Show all errors in development
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    // Hide errors in production
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . 'logs/error.log');
}

// =============================================
// TIME ZONE SETTINGS
// =============================================

date_default_timezone_set('UTC');  // Set to your preferred timezone

// =============================================
// EMAIL CONFIGURATION (for future features)
// =============================================

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-password');
define('SMTP_FROM', 'noreply@ottplatform.com');
define('SMTP_FROM_NAME', 'OTT Platform');

// =============================================
// API KEYS (for future integrations)
// =============================================

define('TMDB_API_KEY', '');  // The Movie Database API key (optional)
define('YOUTUBE_API_KEY', ''); // YouTube API key (optional)

// =============================================
// FUNCTION TO GET CONFIGURATION
// =============================================

/**
 * Get configuration value by key
 * @param string $key Configuration key
 * @return mixed Configuration value or null if not found
 */
function config($key) {
    $config = [
        'site_name' => SITE_NAME,
        'site_url' => SITE_URL,
        'site_description' => SITE_DESCRIPTION,
        'items_per_page' => ITEMS_PER_PAGE,
        'development_mode' => DEVELOPMENT_MODE,
        'subscription_plans' => [
            PLAN_FREE => 'Free',
            PLAN_BASIC => 'Basic',
            PLAN_PREMIUM => 'Premium'
        ],
        'video_qualities' => ['auto', '480p', '720p', '1080p']
    ];
    
    return isset($config[$key]) ? $config[$key] : null;
}

// =============================================
// CREATE UPLOAD DIRECTORIES IF THEY DON'T EXIST
// =============================================

if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!file_exists(THUMBNAIL_PATH)) {
    mkdir(THUMBNAIL_PATH, 0755, true);
}
if (!file_exists(VIDEO_PATH)) {
    mkdir(VIDEO_PATH, 0755, true);
}
if (!file_exists(CACHE_PATH)) {
    mkdir(CACHE_PATH, 0755, true);
}

// =============================================
// AUTO-LOAD COMPOSER DEPENDENCIES (if using Composer)
// =============================================

// if (file_exists(BASE_PATH . 'vendor/autoload.php')) {
//     require_once BASE_PATH . 'vendor/autoload.php';
// }

// =============================================
// SET CUSTOM SESSION HANDLER (optional)
// =============================================

// Initialize session safely.
// session_name must be set before session_start to avoid warnings.
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// =============================================
// CSRF TOKEN GENERATION
// =============================================

/**
 * Generate CSRF token for form security
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 * @param string $token Token to verify
 * @return bool True if token is valid
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME]) || $token !== $_SESSION[CSRF_TOKEN_NAME]) {
        return false;
    }
    return true;
}

// Generate initial CSRF token
generateCSRFToken();

// =============================================
// DATABASE CONNECTION FUNCTION
// =============================================

/**
 * Get database connection
 * @return PDO Database connection
 */
function getDB() {
    static $db = null;
    
    if ($db === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $db = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (DEVELOPMENT_MODE) {
                die('Database Connection Error: ' . $e->getMessage());
            } else {
                // Log error and show user-friendly message
                error_log('Database Connection Error: ' . $e->getMessage());
                die('Unable to connect to database. Please try again later.');
            }
        }
    }
    
    return $db;
}

// =============================================
// INITIALIZATION CHECK
// =============================================

// Check if required directories are writable
$writable_dirs = [UPLOAD_PATH, THUMBNAIL_PATH, VIDEO_PATH, CACHE_PATH];
foreach ($writable_dirs as $dir) {
    if (!is_writable($dir) && DEVELOPMENT_MODE) {
        error_log("Warning: Directory $dir is not writable");
    }
}

// =============================================
// VERSION INFORMATION
// =============================================

define('APP_VERSION', '1.0.0');
define('APP_BUILD', '2024.01.01');

// =============================================
// HELPER FUNCTIONS
// =============================================

/**
 * Format a number as Indian Currency (Lakhs and Crores)
 * @param float $number The number to format
 * @return string Formatted string
 */
function formatIndianCurrency($number) {
    if (!$number) return '0.00';
    $number = round($number, 2);
    $parts = explode('.', (string)$number);
    $whole = $parts[0];
    $fraction = isset($parts[1]) ? $parts[1] : '00';
    $fraction = str_pad($fraction, 2, '0', STR_PAD_RIGHT);

    $lastThree = substr($whole, -3);
    $restUnits = substr($whole, 0, -3);

    if ($restUnits != '') {
        $lastThree = ',' . $lastThree;
        $restUnits = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $restUnits);
        $whole = $restUnits . $lastThree;
    }

    return $whole . '.' . $fraction;
}

// =============================================
// END OF CONFIGURATION
// =============================================
?>