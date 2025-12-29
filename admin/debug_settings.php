<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    die('Please log in to view this page');
}

// Get current settings
$settings = getSiteSettings(true);

// Get database structure
try {
    // Check if settings table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'settings'")->fetchAll();
    $settingsTableExists = !empty($tables);
    
    // Get columns if table exists
    $columns = [];
    if ($settingsTableExists) {
        $stmt = $pdo->query("SHOW COLUMNS FROM settings");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Get first row from settings
    $settingsRow = [];
    if ($settingsTableExists) {
        $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
        $settingsRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">
        <h1 class="text-2xl font-bold mb-6">Debug Settings</h1>
        
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Database Structure</h2>
            <?php if (isset($dbError)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    Database Error: <?php echo htmlspecialchars($dbError); ?>
                </div>
            <?php else: ?>
                <div class="mb-4">
                    <p><strong>Settings table exists:</strong> <?php echo $settingsTableExists ? 'Yes' : 'No'; ?></p>
                    <?php if ($settingsTableExists): ?>
                        <p class="mt-2"><strong>Columns in settings table:</strong></p>
                        <ul class="list-disc pl-5 mt-1">
                            <?php foreach ($columns as $column): ?>
                                <li><?php echo htmlspecialchars($column); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <div class="mb-4">
                    <h3 class="font-semibold mb-2">Settings Row from Database:</h3>
                    <pre class="bg-gray-100 p-4 rounded overflow-auto"><?php echo htmlspecialchars(print_r($settingsRow, true)); ?></pre>
                </div>
            <?php endif; ?>
        </div>
        
        <div>
            <h2 class="text-xl font-semibold mb-4">Current Settings (from getSiteSettings())</h2>
            <pre class="bg-gray-100 p-4 rounded overflow-auto"><?php echo htmlspecialchars(print_r($settings, true)); ?></pre>
        </div>
    </div>
</body>
</html>
