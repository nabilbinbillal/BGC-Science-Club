<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', __DIR__);

// Debug information
echo "<!-- Debug Info -->";
echo "<!-- BASE_PATH: " . BASE_PATH . " -->";
echo "<!-- Current Directory: " . getcwd() . " -->";

// Include required files
$db_file = BASE_PATH . '/config/db.php';
$functions_file = BASE_PATH . '/includes/functions.php';
$header_file = BASE_PATH . '/includes/header.php';

echo "<!-- DB File: " . $db_file . " -->";
echo "<!-- Functions File: " . $functions_file . " -->";
echo "<!-- Header File: " . $header_file . " -->";

if (!file_exists($db_file)) {
    die("Error: Database configuration file not found at: " . $db_file);
}

if (!file_exists($functions_file)) {
    die("Error: Functions file not found at: " . $functions_file);
}

if (!file_exists($header_file)) {
    die("Error: Header file not found at: " . $header_file);
}

require_once $db_file;
require_once $functions_file;

// Set page title
$page_title = "Club Logo - BGC Science Club";

// Include header
require_once $header_file;

// Define logo dimensions and their descriptions
$logoDimensions = [
    'original' => [
        'width' => 'Original',
        'height' => 'Original',
        'description' => 'High resolution original logo',
        'file' => 'logo.png'
    ],
    'large' => [
        'width' => '1200px',
        'height' => '1200px',
        'description' => 'Large size for banners and posters',
        'file' => 'logo-1200x1200.png'
    ],
    'medium' => [
        'width' => '600px',
        'height' => '600px',
        'description' => 'Medium size for website headers',
        'file' => 'logo-600x600.png'
    ],
    'small' => [
        'width' => '300px',
        'height' => '300px',
        'description' => 'Small size for social media profiles',
        'file' => 'logo-300x300.png'
    ],
    'favicon' => [
        'width' => '32px',
        'height' => '32px',
        'description' => 'Favicon size for browser tabs',
        'file' => 'logo-32x32.png'
    ]
];
?>

<style>
/* Main Styles */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #1a202c;
    line-height: 1.5;
}

.container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}

/* Header Styles */
.page-header {
    background-color: #f7fafc;
    padding: 2rem 0;
}

.page-header h1 {
    font-size: 2.25rem;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 1rem;
}

.breadcrumb {
    color: #718096;
    font-size: 0.875rem;
}

.breadcrumb a {
    color: #718096;
    text-decoration: none;
}

.breadcrumb a:hover {
    color: #805ad5;
}

/* Card Styles */
.card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.card h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 1rem;
}

.card p {
    color: #4a5568;
    margin-bottom: 1rem;
}

/* Alert Styles */
.alert {
    background-color: #fffaf0;
    border-left: 4px solid #f6ad55;
    padding: 1rem;
    margin-bottom: 1rem;
    display: flex;
}

.alert-icon {
    flex-shrink: 0;
    color: #f6ad55;
    margin-right: 0.75rem;
}

.alert-content {
    font-size: 0.875rem;
    color: #b7791f;
}

/* Grid Styles */
.logo-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (min-width: 768px) {
    .logo-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .logo-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Logo Card Styles */
.logo-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    overflow: hidden;
}

.logo-card-content {
    padding: 1rem;
}

.logo-card h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 0.5rem;
}

.logo-card p {
    color: #718096;
    font-size: 0.875rem;
    margin-bottom: 1rem;
}

.logo-image-container {
    position: relative;
    width: 100%;
    padding-bottom: 100%; /* 1:1 Aspect Ratio */
    margin-bottom: 1rem;
}

.logo-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
}

/* Button Styles */
.btn {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background-color: #805ad5;
    color: white;
}

.btn-primary:hover {
    background-color: #6b46c1;
}

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
}

.btn svg {
    margin-right: 0.5rem;
}

/* Utility Classes */
.text-center {
    text-align: center;
}

.mb-4 {
    margin-bottom: 1rem;
}

.mb-8 {
    margin-bottom: 2rem;
}

.mt-8 {
    margin-top: 2rem;
}

.py-8 {
    padding-top: 2rem;
    padding-bottom: 2rem;
}

.px-4 {
    padding-left: 1rem;
    padding-right: 1rem;
}

.py-3 {
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
}

.px-6 {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
}

.flex {
    display: flex;
}

.justify-center {
    justify-content: center;
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Club Logo</h1>
        <nav class="breadcrumb">
            <a href="/">Home</a>
            <span style="margin: 0 0.5rem;">/</span>
            <span>Logo</span>
        </nav>
    </div>
</div>

<!-- Logo Information -->
<div class="container">
    <div class="card">
        <h2>About Our Logo</h2>
        <p>
            The BGC Science Club logo was designed by <a href="https://nabilbinbillal.github.io/" target="_blank" style="color: #805ad5; font-weight: bold; text-decoration: underline;">Nabil Bin Billal</a>. 
            It represents our commitment to scientific excellence and innovation.
        </p>
        <div class="alert">
            <div class="alert-icon">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="alert-content">
                When using our logo, please ensure proper attribution is given to the designer and the BGC Science Club.
                The logo should not be modified or used in a way that could be misleading or damaging to our brand.
            </div>
        </div>
    </div>

    <!-- Logo Grid -->
    <div class="logo-grid">
        <?php foreach ($logoDimensions as $key => $dimension): ?>
            <div class="logo-card">
                <div class="logo-card-content">
                    <h3><?php echo $dimension['width']; ?> × <?php echo $dimension['height']; ?></h3>
                    <p><?php echo $dimension['description']; ?></p>
                    
                    <div class="logo-image-container">
                        <img 
                            src="/uploads/logo/<?php echo $dimension['file']; ?>" 
                            alt="BGC Science Club Logo - <?php echo $dimension['width']; ?> × <?php echo $dimension['height']; ?>"
                            class="logo-image"
                            onerror="this.src='/assets/images/default-avatar.jpg'"
                        >
                    </div>
                    
                    <div class="flex justify-center">
                        <a href="/uploads/logo/<?php echo $dimension['file']; ?>" 
                           download="<?php echo $dimension['file']; ?>"
                           class="btn btn-primary">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download Logo
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Download All Section -->
    <div class="mt-8 text-center">
        <a href="/BGCSC-logos.zip" 
           class="btn btn-primary btn-lg">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Download All Logos (ZIP)
        </a>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>