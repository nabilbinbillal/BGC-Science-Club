<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: text/plain');

try {
    // Check if settings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
    if ($stmt->rowCount() === 0) {
        die("Error: 'settings' table does not exist.\n");
    }
    
    echo "Settings table exists. Checking columns...\n\n";
    
    // Check columns in settings table
    $stmt = $pdo->query("DESCRIBE settings");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Columns in settings table:\n";
    foreach ($columns as $column) {
        echo "- $column\n";
    }
    
    // Check if whatsapp_number column exists
    if (!in_array('whatsapp_number', $columns)) {
        echo "\nWarning: 'whatsapp_number' column is missing.\n";
    }
    
    // Get current settings
    $settings = getSiteSettings(true);
    
    echo "\nCurrent settings:\n";
    print_r($settings);
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
