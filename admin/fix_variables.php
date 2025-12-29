<?php
// Include necessary files
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// Create settings table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recaptcha_enabled TINYINT(1) DEFAULT 0,
    recaptcha_site_key VARCHAR(255) DEFAULT '',
    recaptcha_secret_key VARCHAR(255) DEFAULT '',
    recaptcha_score_threshold FLOAT DEFAULT 0.5,
    whatsapp_enabled TINYINT(1) DEFAULT 0,
    whatsapp_country_code VARCHAR(10) DEFAULT '880',
    whatsapp_number VARCHAR(20) DEFAULT '',
    whatsapp_link VARCHAR(255) DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

try {
    // Create the table
    $pdo->exec($createTableSQL);
    
    // Check if we need to insert a default row
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM settings");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        // Insert default settings
        $insertSQL = "INSERT INTO settings 
            (recaptcha_enabled, recaptcha_site_key, recaptcha_secret_key, recaptcha_score_threshold,
             whatsapp_enabled, whatsapp_country_code, whatsapp_number, whatsapp_link)
            VALUES (0, '', '', 0.5, 0, '880', '', '')";
        $pdo->exec($insertSQL);
    }
    
    echo "Settings table has been created/updated successfully. <a href='index.php?page=variables'>Go to Variables page</a>";
    
} catch (PDOException $e) {
    die("Error creating/updating settings table: " . $e->getMessage());
}
?>
