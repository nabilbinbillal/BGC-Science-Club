<?php
require_once '../config/db.php';

try {
    // Check database connection
    $stmt = $pdo->query("SELECT 1");
    echo "Database connection is successful.<br>";

    // Check if the admins table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
    if ($stmt->rowCount() > 0) {
        echo "The 'admins' table exists.<br>";
    } else {
        echo "The 'admins' table does not exist. Please run the SQL script to create it.<br>";
    }

    // Check if there are any admin users
    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM admins");
    $result = $stmt->fetch();
    if ($result['count'] > 0) {
        echo "Admin users are present in the database.<br>";
    } else {
        echo "No admin users found. Please insert an admin user.<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>