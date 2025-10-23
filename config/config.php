<?php
/**
 * External Configuration File
 * Contains all database credentials and application settings
 * 
 * This file should be excluded from version control in production
 * and have appropriate file permissions for security
 */

// Database Configuration - moved from db_model.php
define("DB_CONFIG", [
    'server' => 'localhost',
    'database' => 'ellenfoodhouse',
    'username' => 'root',
    'password' => 'password', // Empty password for default XAMPP setup
    'charset' => 'utf8mb4'
]);

// Application Settings
define("APP_CONFIG", [
    'timezone' => 'America/New_York',
    'debug_mode' => true,
    'session_timeout' => 3600
]);

// Upload Configuration
define("UPLOAD_CONFIG", [
    'base_path' => dirname(__DIR__) . '/uploads/',
    'max_file_size' => 5 * 1024 * 1024, // 5MB
    'allowed_image_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']
]);

// Pagination Settings
define("PAGINATION_CONFIG", [
    'default_limit' => 20,
    'max_limit' => 100
]);
?>