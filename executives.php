<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the year parameter
$year = isset($_GET['year']) ? $_GET['year'] : null;

// Debug output
error_log("Year parameter: " . $year);

// Redirect to the main executives page with the correct parameter
if ($year) {
    $redirectUrl = "http://" . $_SERVER['HTTP_HOST'] . "/executives?year=" . $year;
    error_log("Redirecting to: " . $redirectUrl);
    header("Location: " . $redirectUrl);
} else {
    $redirectUrl = "http://" . $_SERVER['HTTP_HOST'] . "/executives.php";
    error_log("Redirecting to: " . $redirectUrl);
    header("Location: " . $redirectUrl);
}
exit; 