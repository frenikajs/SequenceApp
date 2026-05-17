<?php
/**
 * Run this script once from the command line to create the initial admin user.
 * Usage: php migrations/create-admin.php
 *
 * After running, delete this file.
 */

require_once __DIR__ . '/../app/config/config.php';
require_once APP_ROOT . '/models/Database.php';
require_once APP_ROOT . '/helpers/Security.php';

$username = 'admin';
$email    = 'admin@example.com';
$password = 'Sequence2024!'; // Change this before running!

$hash = Security::hashPassword($password);
$db   = Database::getInstance();

try {
    $existing = $db->fetch('SELECT id FROM admins WHERE username = ?', [$username]);
    if ($existing) {
        echo "Admin user '$username' already exists (id={$existing['id']}).\n";
        exit(0);
    }
    $db->execute(
        'INSERT INTO admins (username, email, password_hash, is_super) VALUES (?, ?, ?, 1)',
        [$username, $email, $hash]
    );
    $id = $db->lastInsertId();
    echo "Admin user '$username' created with id=$id.\n";
    echo "Login at: " . BASE_URL . "/admin/login\n";
    echo "Password: $password\n";
    echo "IMPORTANT: Delete this file and change your password now.\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
