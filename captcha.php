<?php
require_once 'includes/captcha.php';

// Generate a new captcha string
$captchaString = generateCaptchaString();

// Store the captcha string in session
$_SESSION['captcha'] = $captchaString;

// Generate and output the captcha image
generateCaptchaImage($captchaString); 