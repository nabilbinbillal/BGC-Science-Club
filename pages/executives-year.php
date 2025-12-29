<?php
require_once __DIR__ . '/../config/db.php';

// Get the year parameter
$year = isset($_GET['year']) ? $_GET['year'] : null;

// Redirect to the main executives page with the correct parameter
if ($year) {
    header("Location: ../admin/pages/executives.php?archive_year=" . $year);
} else {
    header("Location: ../admin/pages/executives.php");
}
exit;
