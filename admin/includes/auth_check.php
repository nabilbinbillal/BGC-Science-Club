<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user has permission to access the page
$allowed_pages = ['dashboard', 'members', 'executives', 'tickets', 'settings'];
$current_page = basename($_SERVER['PHP_SELF'], '.php');

if (!in_array($current_page, $allowed_pages)) {
    header('Location: index.php');
    exit();
}

// Check if user is superadmin
$is_superadmin = isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin';

// If not superadmin, restrict access to certain pages
if (!$is_superadmin && in_array($current_page, ['settings'])) {
    header('Location: index.php');
    exit();
} 