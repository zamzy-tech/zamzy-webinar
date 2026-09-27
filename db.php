<?php
// db.php - Database connection for webinar database

$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'webinar';

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host={$db_host};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Ensure database exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$db_name}`");

    // Ensure contact_messages table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `contact_messages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `email` VARCHAR(150) NOT NULL,
        `subject` VARCHAR(255) NULL DEFAULT 'General Inquiry',
        `message` TEXT NOT NULL,
        `status` ENUM('new', 'read', 'replied') DEFAULT 'new',
        `ip_address` VARCHAR(45) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Ensure admin_users table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admin_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(60) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Seed default admin if table is empty (admin / admin123)
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `admin_users`");
    $count = $stmt->fetch()['cnt'] ?? 0;
    if ($count == 0) {
        $default_user = 'admin';
        $default_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO `admin_users` (`username`, `password_hash`) VALUES (?, ?)");
        $insert->execute([$default_user, $default_hash]);
    }

} catch (PDOException $e) {
    // If running as API return JSON error
    if (defined('API_REQUEST')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection error: ' . $e->getMessage()]);
        exit;
    }
    die('Database connection error: ' . $e->getMessage());
}
