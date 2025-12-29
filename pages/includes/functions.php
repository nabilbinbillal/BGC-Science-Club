<?php
// Generate a unique member ID
function generateMemberId() {
    global $pdo;

    $year = date('y');
    $prefix = "BGC-$year-";

    // Get the max numeric suffix for this year
    $stmt = $pdo->prepare("SELECT MAX(CAST(RIGHT(member_id, 4) AS UNSIGNED)) AS max_num
                           FROM members
                           WHERE member_id LIKE ?");
    $stmt->execute([$prefix . '%']);
    $lastNum = $stmt->fetchColumn();

    $newNum = $lastNum ? $lastNum + 1 : 1;

    return $prefix . str_pad($newNum, 4, '0', STR_PAD_LEFT);
}

// Get department/class options
function getDepartmentOptions() {
    return [
        'Intermediate 1st Year',
        'Intermediate 2nd Year',
        'Physics',
        'Chemistry',
        'Mathematics',
        'Botany',
        'Zoology'
    ];
}

// Get role options
function getRoleOptions() {
    return [
        'member' => 'Member',
        'executive' => 'Executive'
    ];
}

// Get position options for executives
function getPositionOptions() {
    return [
        'President',
        'Vice President',
        'General Secretary',
        'Joint Secretary',
        'Treasurer',
        'Organizing Secretary',
        'Office Secretary',
        'Publication Secretary',
        'Executive Member'
    ];
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Check if user is a superadmin
function isSuperAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'superadmin';
}

// Get avatar URL based on gender
function getAvatarUrl($gender, $image = null) {
    if ($image && file_exists('uploads/members/' . $image)) {
        return 'uploads/members/' . $image;
    }
    
    if ($gender == 'female') {
        return 'assets/images/default-avatar.jpg';
    }
    
    return 'assets/images/default-avatar.jpg';
}

// Get current page name
function getCurrentPage() {
    return isset($_GET['page']) ? $_GET['page'] : 'home';
}

// Format date for display
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

// Sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Compress and save image
function compressAndSaveImage($sourceFile, $targetFile, $quality = 60) {
    $info = getimagesize($sourceFile);
    $mime = $info['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($sourceFile);
            break;
        case 'image/png':
            $image = imagecreatefrompng($sourceFile);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($sourceFile);
            break;
        default:
            return false;
    }
    
    // Calculate new dimensions while maintaining aspect ratio
    $maxWidth = 800;
    $maxHeight = 800;
    $width = imagesx($image);
    $height = imagesy($image);
    
    if ($width > $maxWidth || $height > $maxHeight) {
        $ratio = min($maxWidth/$width, $maxHeight/$height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG images
        if ($mime === 'image/png') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        $image = $newImage;
    }
    
    switch ($mime) {
        case 'image/jpeg':
            return imagejpeg($image, $targetFile, $quality);
        case 'image/png':
            // Convert quality scale from 0-100 to 0-9
            $pngQuality = round((100 - $quality) / 11.111111);
            return imagepng($image, $targetFile, $pngQuality);
        case 'image/gif':
            return imagegif($image, $targetFile);
    }
    
    if (isset($image)) { unset($image); }
    return false;
}

// Upload and compress member image
function uploadMemberImage($file, $targetDir) {
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = uniqid() . '.' . $fileExt;
    $targetFile = $targetDir . $fileName;
    
    // Compress and save the image
    if (compressAndSaveImage($file['tmp_name'], $targetFile)) {
        return $fileName;
    }
    
    return null;
}