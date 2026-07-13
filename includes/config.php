<?php
date_default_timezone_set('Asia/Manila');

if (!defined('APP_NAME')) {
    define('APP_NAME', 'StudyBuddy Finder');
}

if (!defined('DB_HOST')) {
    define('DB_HOST', '127.0.0.1');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', 'studybuddy');
}

if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars');
}

if (!defined('UPLOAD_URL')) {
    define('UPLOAD_URL', 'uploads/avatars');
}
