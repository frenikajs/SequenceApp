<?php
declare(strict_types=1);

// Application root (the /app directory)
define('APP_ROOT', dirname(__DIR__));
// Project root
define('PROJECT_ROOT', dirname(__DIR__, 2));
// Public root
define('PUBLIC_ROOT', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'public');

define('APP_NAME', 'Sequence Mystery System');
define('APP_VERSION', '1.0.0');

// Set BASE_URL dynamically if not already defined
if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $scheme . '://' . $host);
}

define('UPLOAD_PATH', PUBLIC_ROOT . DIRECTORY_SEPARATOR . 'uploads');
define('UPLOAD_URL',  BASE_URL . '/uploads');

// File limits & allowed types
define('MAX_FILE_SIZE', 500 * 1024 * 1024); // 500 MB
define('ALLOWED_EXTENSIONS', ['png', 'jpg', 'jpeg', 'pdf', 'mp3', 'wav', 'ogg', 'm4a', 'mov']);
define('ALLOWED_MIMES', [
    'image/png'        => 'image',
    'image/jpeg'       => 'image',
    'application/pdf'  => 'pdf',
    'audio/mpeg'       => 'audio',
    'audio/mp3'        => 'audio',
    'audio/wav'        => 'audio',
    'audio/x-wav'      => 'audio',
    'audio/ogg'        => 'audio',
    'audio/vorbis'     => 'audio',
    'audio/mp4'        => 'audio',
    'audio/x-m4a'      => 'audio',
    'video/mp4'        => 'audio', // some systems report m4a as video/mp4
    'video/quicktime'  => 'video',
    'video/x-quicktime'=> 'video',
    'video/x-m4v'      => 'video',
]);

// Security / sessions
define('SESSION_LIFETIME', 3600 * 8); // 8 hours
define('CSRF_TOKEN_LENGTH', 32);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_WINDOW', 900); // 15 minutes in seconds

// Pagination
define('ITEMS_PER_PAGE', 10);

// Error logging (disable display_errors in production)
ini_set('display_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', PROJECT_ROOT . '/logs/error.log');
