<?php
// Get the member_id from the query string
$member_id = isset($_GET['member_id']) ? trim($_GET['member_id']) : '';

// Validate member_id (basic validation)
if (!empty($member_id) && preg_match('/^BGC-\d{2}-\d{4}$/', $member_id)) {
    // Redirect to the member card page in the pages directory
    header('Location: /pages/member_card.php?member_id=' . urlencode($member_id));
    exit;
} else {
    // Invalid member ID, redirect to 404
    header('Location: /pages/404.php');
    exit;
}
