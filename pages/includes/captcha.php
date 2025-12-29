<?php
session_start();

// Get reCAPTCHA settings
$stmt = $pdo->query("SELECT recaptcha_enabled FROM settings LIMIT 1");
$settings = $stmt->fetch();
$recaptcha_enabled = $settings['recaptcha_enabled'] ?? 1;

// Add reCAPTCHA v3 site key
$recaptcha_site_key = '6LeJSiArAAAAAOqrpowgI-KQ_czZMz76tNFcTiR9'; // Replace with your actual site key

// Generate a random captcha string
function generateCaptchaString($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $captchaString = '';
    for ($i = 0; $i < $length; $i++) {
        $captchaString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $captchaString;
}

// Generate captcha image
function generateCaptchaImage($captchaString) {
    // Create image
    $width = 120;
    $height = 40;
    $image = imagecreatetruecolor($width, $height);
    
    // Colors
    $bgColor = imagecolorallocate($image, 255, 255, 255);
    $textColor = imagecolorallocate($image, 0, 0, 0);
    $lineColor = imagecolorallocate($image, 200, 200, 200);
    
    // Fill background
    imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
    
    // Add some random lines
    for ($i = 0; $i < 5; $i++) {
        imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
    }
    
    // Add some random dots
    for ($i = 0; $i < 50; $i++) {
        imagesetpixel($image, rand(0, $width), rand(0, $height), $lineColor);
    }
    
    // Add text using built-in font
    $x = 10;
    $y = 25;
    
    // Add each character with slight rotation
    for ($i = 0; $i < strlen($captchaString); $i++) {
        $angle = rand(-10, 10);
        $char = $captchaString[$i];
        imagestring($image, 5, $x, $y - 10, $char, $textColor);
        $x += 20;
    }
    
    // Output image
    header('Content-Type: image/png');
    imagepng($image);
    if (isset($image)) { unset($image); }
}

// Verify captcha
function verifyCaptcha($userInput) {
    if (!isset($_SESSION['captcha'])) {
        return false;
    }
    
    $isValid = strtolower($userInput) === strtolower($_SESSION['captcha']);
    unset($_SESSION['captcha']); // Clear the captcha after verification
    return $isValid;
}

// Check if captcha is enabled
function isCaptchaEnabled() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT recaptcha_enabled FROM settings LIMIT 1");
        $result = $stmt->fetch();
        return $result['recaptcha_enabled'] == 1;
    } catch (PDOException $e) {
        error_log("Error checking captcha status: " . $e->getMessage());
        return false;
    }
} 