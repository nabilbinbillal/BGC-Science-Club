<?php
session_start();
include 'config/db.php';
include 'includes/functions.php';

// -------------------------------------------------------------------------
// Canonical URL + redirect handling (host, HTTPS, /index.php)
// -------------------------------------------------------------------------
$preferredScheme = 'https';
$preferredHost   = 'bgcscienceclub.org';

$currentHost = $_SERVER['HTTP_HOST'] ?? $preferredHost;
$requestUri  = $_SERVER['REQUEST_URI'] ?? '/';

// Detect HTTPS
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443')
);

$parsedUrl = parse_url($requestUri);
$path      = $parsedUrl['path'] ?? '/';
$query     = isset($parsedUrl['query']) ? $parsedUrl['query'] : '';

// Normalize /index.php -> /
$normalizedPath = ($path === '/index.php') ? '/' : $path;

$redirectNeeded = false;

// Force canonical host
if (strtolower($currentHost) !== strtolower($preferredHost)) {
    $redirectNeeded = true;
}

// Force HTTPS
if (!$isHttps) {
    $redirectNeeded = true;
}

// Force normalized path
if ($normalizedPath !== $path) {
    $redirectNeeded = true;
}

if ($redirectNeeded) {
    $targetUrl = $preferredScheme . '://' . $preferredHost . $normalizedPath;
    if ($query !== '') {
        $targetUrl .= '?' . $query;
    }
    header('Location: ' . $targetUrl, true, 301);
    exit;
}

// Parse the URL path to determine the current page
// Use the already-normalized path for routing
$requestUri = $_SERVER['REQUEST_URI'];
$urlPath    = $normalizedPath;
$pathSegments = explode('/', trim($urlPath, '/'));

// Default page
$page = 'home';

// Handle dynamic URLs (case-insensitive)
if (count($pathSegments) > 0 && $pathSegments[0] !== '') {
    $segment = strtolower($pathSegments[0]);
    switch ($segment) {
        case 'executive':
            if (isset($pathSegments[1])) {
                $page = 'executive';
                $_GET['slug'] = $pathSegments[1];
            }
            break;
        case 'project':
            if (isset($pathSegments[1])) {
                $page = 'project';
                $_GET['slug'] = $pathSegments[1];
            } else {
                $page = 'projects';
            }
            break;
        case 'class':
            if (isset($pathSegments[1])) {
                $page = 'class';
                $_GET['slug'] = $pathSegments[1];
                if (isset($pathSegments[2])) {
                    $_GET['group'] = $pathSegments[2];
                }
            } else {
                $page = 'classes';
            }
            break;
        case 'department':
            if (isset($pathSegments[1])) {
                $page = 'department';
                $_GET['slug'] = $pathSegments[1];
            } else {
                $page = 'departments';
            }
            break;
        case 'activity':
            if (isset($pathSegments[1])) {
                $page = 'activity';
                // Get the raw slug from URL
                $slug = $pathSegments[1];
                
                // If the slug starts with a number followed by a hyphen, extract just the slug part
                if (preg_match('/^\d+-(.*)/', $slug, $matches)) {
                    $slug = $matches[1];
                }
                
                // Decode any URL-encoded characters and HTML entities
                $slug = urldecode($slug);
                $slug = html_entity_decode($slug, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                
                // Store the cleaned slug in GET
                $_GET['slug'] = $slug;
            } else {
                $page = 'activities';
            }
            break;
        default:
            $validPages = ['about', 'executives', 'members', 'activities', 'events', 'join', 'about-developer', 'projects', 'classes', 'departments', 'logo'];
            if (in_array($segment, $validPages)) {
                $page = $segment;
            } else {
                // Unknown first segment -> 404
                $page = '404';
            }
            break;
    }
}

// Set parameters based on the page
$params = [];
if (isset($_GET['slug'])) {
    $params['slug'] = $_GET['slug'];
}
// Add group parameter if it exists
if (isset($_GET['group'])) {
    $params['group'] = $_GET['group'];
}
// Add filter parameters for projects page
if ($page === 'projects') {
    if (!empty($_GET['class'])) {
        $params['class_filter'] = trim($_GET['class']);
    }
    if (!empty($_GET['department'])) {
        $params['department_filter'] = trim($_GET['department']);
    }
    if (!empty($_GET['member_id'])) {
        $params['member_id_filter'] = strtoupper(trim($_GET['member_id']));
    }
    if (isset($_GET['collaborative']) && $_GET['collaborative'] === '1') {
        $params['collaborative_filter'] = true;
    }
    if (!empty($_GET['search'])) {
        $params['search_filter'] = trim($_GET['search']);
    }
}

// Add filter parameters for members page
if ($page === 'members') {
    if (!empty($_GET['department'])) {
        $params['department_filter'] = trim($_GET['department']);
    }
    if (!empty($_GET['class_level'])) {
        $params['class_level_filter'] = trim($_GET['class_level']);
    }
    if (!empty($_GET['search'])) {
        $params['search_filter'] = trim($_GET['search']);
    }
}

// Initialize theme
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 
    (isset($_SERVER['HTTP_SEC_CH_PREFERS_COLOR_SCHEME']) && $_SERVER['HTTP_SEC_CH_PREFERS_COLOR_SCHEME'] === 'dark' ? 'dark' : 'light');

// Define allowed pages
$allowed_pages = [
    'home',
    'about',
    'members',
    'executives',
    'executive',
    'activities',
    'activity',
    'events',
    'join',
    'about-developer',
    'projects',
    'project',
    'classes',
    'class',
    'logo',
    'departments',
    'department'
];

// Ensure database connection is working
try {
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    die("Database connection failed. Please check your configuration.");
}
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $theme; ?>">
<?php
// Include meta helper
include 'includes/meta-helper.php';

// Fetch data early for SEO meta tags and schema markup
$seoData = [];
if ($page === 'project' && !empty($params['slug'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE slug = ? LIMIT 1");
        $stmt->execute([$params['slug']]);
        $projectRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($projectRow) {
            $seoData['project'] = normalizeProjectRecord($projectRow);
        }
    } catch (PDOException $e) {
        error_log("Error fetching project for SEO: " . $e->getMessage());
    }
} elseif ($page === 'activity' && !empty($params['slug'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM activities WHERE slug = ? LIMIT 1");
        $stmt->execute([$params['slug']]);
        $activityRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$activityRow && is_numeric($params['slug'])) {
            $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = ? LIMIT 1");
            $stmt->execute([$params['slug']]);
            $activityRow = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        if ($activityRow) {
            $seoData['activity'] = $activityRow;
        }
    } catch (PDOException $e) {
        error_log("Error fetching activity for SEO: " . $e->getMessage());
    }
} elseif ($page === 'executive' && !empty($params['slug'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM executives WHERE slug = ? LIMIT 1");
        $stmt->execute([$params['slug']]);
        $executiveRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($executiveRow) {
            $seoData['executive'] = $executiveRow;
        }
    } catch (PDOException $e) {
        error_log("Error fetching executive for SEO: " . $e->getMessage());
    }
} elseif ($page === 'department' && !empty($params['slug'])) {
    $departments = getDepartmentOptions();
    foreach ($departments as $dept) {
        if (slugifyText($dept) === $params['slug']) {
            $seoData['department_name'] = $dept;
            break;
        }
    }
}

// Prepare meta parameters based on the current page
$metaParams = [];

// Set up meta parameters based on the page
switch ($page) {
    case 'project':
        if (!empty($seoData['project'])) {
            $project = $seoData['project'];
            $metaParams = [
                'title' => $project['title'] ?? '',
                'short_description' => $project['short_description'] ?? '',
                'excerpt' => $project['short_description'] ?? $project['description'] ?? ''
            ];
        }
        break;
    case 'activity':
        if (!empty($seoData['activity'])) {
            $activity = $seoData['activity'];
            $metaParams = [
                'title' => $activity['title'] ?? '',
                'short_description' => $activity['short_description'] ?? '',
                'description' => $activity['description'] ?? ''
            ];
        }
        break;
    case 'executive':
        if (!empty($seoData['executive'])) {
            $executive = $seoData['executive'];
            $metaParams = [
                'name' => $executive['name'] ?? '',
                'role' => $executive['role'] ?? $executive['position'] ?? '',
                'bio' => $executive['bio'] ?? ''
            ];
        }
        break;
    case 'department':
        if (!empty($seoData['department_name'])) {
            $metaParams = [
                'department_name' => $seoData['department_name']
            ];
        }
        break;
    case 'class':
        $metaParams = [
            'class_name' => $class['name'] ?? '',
            'group' => $_GET['group'] ?? '',
            'description' => $class['description'] ?? ''
        ];
        break;
    case 'member':
        if (isset($member)) {
            $metaParams = [
                'name' => $member['name'],
                'role' => $member['role'] ?? ''
            ];
        }
        break;
    case 'event':
        if (isset($event)) {
            $metaParams = [
                'title' => $event['title'],
                'date' => $event['date'] ?? '',
                'description' => $event['description'] ?? ''
            ];
        }
        break;
    case 'article':
        if (isset($article)) {
            $metaParams = [
                'title' => $article['title'],
                'excerpt' => $article['excerpt'] ?? ''
            ];
        }
        break;
}

// Get meta description and page title
// For project, activity, and executive pages, we now have data in $seoData and $metaParams
// Use merged params with metaParams for accurate descriptions
if ($page === 'project' || $page === 'activity' || $page === 'executive') {
    $pageTitle = getPageTitle($page, $params);
    // Use metaParams which now contains fetched data for better descriptions
    $metaDescription = getMetaDescription($page, array_merge($params, $metaParams));
} else {
    $metaDescription = getMetaDescription($page, $metaParams);
    $pageTitle = getPageTitle($page, array_merge($params, $metaParams));
}

// Override for 404 page title/description
if ($page === '404') {
    $pageTitle = '404 - Page Not Found';
    if (empty($metaDescription) || $metaDescription === getMetaDescription('home')) {
        $metaDescription = 'The page you were looking for could not be found on BGC Science Club.';
    }
}
?>
<head>
        <!-- Dedicated to my beloved parents, my dear brother Nafis, and my grandparents from both sides. And to that one special friend — if you ever find this, even by chance, know that you have a place in my story. -->
        <?php
    echo '<div style="display:none;">
Dedicated to my beloved parents, my dear brother Nafis, and my grandparents from both sides.
And to that one special friend — if you ever find this, even by chance, know that you have a place in my story.
</div>';
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
        // Build canonical URL based on normalized path and preferred host
        // For filtered pages (projects, members), canonical should point to base URL without filters
        // This prevents duplicate content issues
        $canonicalUrl = 'https://bgcscienceclub.org' . $normalizedPath;
        // Only include query params for specific pages that need them, but typically canonical should be without filters
        // Canonical for filtered pages should point to the base listing page to avoid duplicate content
        if (($page === 'projects' || $page === 'members') && !empty($query)) {
            // For filtered listing pages, keep canonical but it's better practice to point to base
            // However, we'll keep it as-is for now since filters are legitimate URLs
            $canonicalUrl .= '?' . $query;
        } elseif (!empty($query) && $page !== 'projects' && $page !== 'members') {
            $canonicalUrl .= '?' . $query;
        }
        
        // Determine OG image
        $ogImage = 'https://bgcscienceclub.org/pages/assets/images/home_og.png';
        if (!empty($seoData['project']['image'])) {
            $ogImage = 'https://bgcscienceclub.org/uploads/projects/' . htmlspecialchars($seoData['project']['image']);
        } elseif (!empty($seoData['activity']['image'])) {
            $ogImage = 'https://bgcscienceclub.org/uploads/activities/' . htmlspecialchars($seoData['activity']['image']);
        } elseif (!empty($seoData['executive']['profile_pic'])) {
            $ogImage = 'https://bgcscienceclub.org/uploads/executives/' . htmlspecialchars($seoData['executive']['profile_pic']);
        }
    ?>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl); ?>">
    <?php
        // For home, keep the title as-is; for inner pages, append site name
        $fullTitle = ($page === 'home')
            ? $pageTitle
            : $pageTitle . ' | BGC Science Club';
    ?>
    <title><?php echo htmlspecialchars($fullTitle); ?></title>
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($fullTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($fullTitle); ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($fullTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage); ?>">
    
    <!-- Favicon -->
    <!-- Root fallback many browsers expect: /favicon.ico -->
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="icon" type="image/x-icon" href="/favicon/favicon.ico">
    <!-- SVG (mask / modern browsers) -->
    <link rel="icon" type="image/svg+xml" href="/favicon/favicon.svg">
    <link rel="mask-icon" href="/favicon/favicon.svg" color="#2563eb">
    <!-- Explicit higher-res icons preferred by Chrome (PWA/installed icons) -->
    <link rel="icon" type="image/png" sizes="192x192" href="/favicon/android-chrome-192x192.png">
    <link rel="icon" type="image/png" sizes="128x128" href="/favicon/favicon-128x128.png">
    <!-- PNG favicons (multiple sizes) -->
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon/favicon-96x96.png">
    <meta name="msapplication-TileColor" content="#2563eb">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">
    
    <!-- Web App Manifest -->
    <link rel="manifest" href="/favicon/site.webmanifest">
    
    <!-- Theme Color for Mobile Browsers -->
    <meta name="theme-color" content="#2563eb">
    <!-- Preload critical resources -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"></noscript>
    
    <!-- Load critical CSS inline -->
    <style>
        /* Critical CSS - minimal styles needed for above-the-fold content */
        * { 
            box-sizing: border-box; 
            margin: 0;
            padding: 0;
        }
        
        body { 
            font-family: system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        
        /* Layout */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header styles */
        header {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1000;
        }
        
        .flex { 
            display: flex; 
        }
        
        .items-center { 
            align-items: center; 
        }
        
        .justify-between { 
            justify-content: space-between; 
        }
        
        .py-4 { 
            padding-top: 1rem; 
            padding-bottom: 1rem; 
        }
        
        .px-4 { 
            padding-left: 1rem; 
            padding-right: 1rem; 
        }
        
        .hidden { 
            display: none; 
        }
        
        .h-12 { 
            height: 3rem; 
        }
        
        .w-12 { 
            width: 3rem; 
        }
        
        .rounded-full { 
            border-radius: 9999px; 
        }
        
        .object-cover { 
            object-fit: cover; 
        }
        
        .ml-3 { 
            margin-left: 0.75rem; 
        }
        
        .text-xl { 
            font-size: 1.25rem; 
            line-height: 1.5;
        }
        
        .font-semibold { 
            font-weight: 600; 
        }
        
        /* Navigation */
        .space-x-4 > * + * { 
            margin-left: 1rem; 
        }
        
        .text-primary-500 { 
            color: #2563eb; 
        }
        
        .px-3 { 
            padding-left: 0.75rem; 
            padding-right: 0.75rem; 
        }
        
        .py-2 { 
            padding-top: 0.5rem; 
            padding-bottom: 0.5rem; 
        }
        
        .rounded-md { 
            border-radius: 0.375rem; 
        }
        
        .text-sm { 
            font-size: 0.875rem; 
            line-height: 1.5; 
        }
        
        .font-medium { 
            font-weight: 500; 
        }
        
        /* Font loading */
        .font-poppins-loaded body {
            font-family: 'Poppins', system-ui, -apple-system, sans-serif;
        }
        
        @media (min-width: 768px) {
            .md\:flex { 
                display: flex; 
            }
            
            .container { 
                padding: 0 1.5rem; 
            }
        }
        
        @media (min-width: 1024px) {
            .lg\:px-8 { 
                padding-left: 2rem; 
                padding-right: 2rem; 
            }
        }
    </style>
    
    <!-- Load non-critical CSS asynchronously -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css"></noscript>
    
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"></noscript>
    
    <link rel="preload" href="assets/css/style.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="assets/css/style.css"></noscript>
    
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    <!-- Font loading script -->
    <script>
    (function() {
        // Load Poppins font
        var font = new FontFace('Poppins', 'url(https://fonts.gstatic.com/s/poppins/v20/pxiEyp8kv8JHgFVrJJfecnFHGPc.woff2) format("woff2")', {
            weight: '400',
            style: 'normal',
            display: 'swap'
        });
        
        // When fonts are loaded, update the UI
        font.load().then(function() {
            document.documentElement.classList.add('font-poppins-loaded');
        }).catch(function(error) {
            console.error('Font loading failed:', error);
        });
        
        // Load non-critical CSS
        function loadDeferredStyles() {
            var links = document.querySelectorAll('link[rel="preload"][as="style"]');
            links.forEach(function(link) {
                if (link.onload) {
                    link.onload();
                } else if (link.rel === 'preload' && link.getAttribute('as') === 'style') {
                    link.rel = 'stylesheet';
                }
            });
        }
        
        // Load non-critical styles after page becomes interactive
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(loadDeferredStyles, 0);
            });
        } else {
            setTimeout(loadDeferredStyles, 0);
        }
    })();
    </script>
</head>
<body class="font-poppins antialiased min-h-screen bg-gray-50 text-gray-800">

    
    <?php include 'includes/header.php'; ?>
    
    <main>
        <?php
        // Include the page content
        if (in_array($page, $allowed_pages)) {
            include "pages/{$page}.php";
        } else {
            // 404 page
            header("HTTP/1.0 404 Not Found");
            include "pages/404.php";
        }
        ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <?php
    // Generate and output schema markup
    if (function_exists('generateSchemaMarkup')) {
        echo generateSchemaMarkup($page, $seoData, $canonicalUrl);
    }
    ?>
    
    <script src="assets/js/script.js"></script>
</body>
</html>