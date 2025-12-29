<!DOCTYPE html>
<html lang="en" class="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>BGC Science Club Admin</title>
    
    <!-- Preload fonts -->
    <link rel="preload" href="/assets/fonts/kalpurush.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="/assets/fonts/kalpurush ANSI.ttf" as="font" type="font/ttf" crossorigin>
    
    <!-- Preconnect to Google font endpoints to reduce latency -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Load Google Fonts non-blocking (preload + onload) -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>

    <!-- Local styles non-blocking -->
    <link rel="preload" href="/assets/css/style.css" as="style" onload="this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="/assets/css/style.css"></noscript>

    <!-- Custom JS (deferred) -->
    <script src="/assets/js/script.js" defer></script>
</head>
<body>
<header class="bg-white dark:bg-gray-800 shadow-md transition-colors duration-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-4">
            <div class="flex items-center space-x-4">
                <a href="index.php" class="flex items-center">
                    <img src="https://nabilbinbillal.github.io/scienceclub.jpg" alt="BGC Science Club Logo" class="h-12 w-auto">
                    <span class="ml-3 text-xl font-semibold text-gray-900 dark:text-white">BGC Science Club</span>
                </a>
            </div>
            
            <div class="hidden md:flex items-center space-x-1">
                <nav class="flex items-center space-x-1">
                    <?php
                    $navItems = [
                        'home' => 'Home',
                        'about' => 'About',
                        'executives' => 'Executives',
                        'members' => 'Members',
                        'activities' => 'Activities'
                    ];
                    
                    $currentPage = getCurrentPage();
                    
                    foreach ($navItems as $slug => $label) {
                        $activeClass = $currentPage == $slug 
                            ? 'bg-primary-500 text-white dark:bg-primary-600' 
                            : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700';
                        
                        echo '<a href="?page=' . $slug . '" class="px-3 py-2 rounded-md text-sm font-medium ' . $activeClass . '">' . $label . '</a>';
                    }
                    ?>
                    
                    <a href="/join" class="ml-4 px-4 py-2 rounded-md text-sm font-medium bg-secondary-500 hover:bg-secondary-600 text-white transition duration-150 ease-in-out transform hover:scale-105">
                        Join Us
                    </a>
                    
                    
                    
                    <?php if (isLoggedIn()): ?>
                    <a href="admin/index.php" class="ml-4 px-3 py-2 rounded-md text-sm font-medium bg-gray-700 hover:bg-gray-800 text-white">
                        Admin Panel
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
            
            <div class="md:hidden flex items-center">
                
                
                <button id="mobileMenuButton" class="p-2 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div id="mobileMenu" class="md:hidden hidden pb-4">
            <nav class="flex flex-col space-y-2">
                <?php
                foreach ($navItems as $slug => $label) {
                    $activeClass = $currentPage == $slug 
                        ? 'bg-primary-500 text-white dark:bg-primary-600' 
                        : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700';
                    
                    echo '<a href="?page=' . $slug . '" class="px-3 py-2 rounded-md text-sm font-medium ' . $activeClass . '">' . $label . '</a>';
                }
                ?>
                
                <a href="/join" class="px-4 py-2 rounded-md text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white shadow-md">
    Join Us
</a>

                
                <?php if (isLoggedIn()): ?>
                <a href="admin/index.php" class="px-3 py-2 rounded-md text-sm font-medium bg-gray-700 hover:bg-gray-800 text-white text-center">
                    Admin Panel
                </a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</header>

<!-- Preserve BGC Science Club text from translation -->
<span class="text-xl font-bold text-primary-600 dark:text-primary-400 no-translate">BGC Science Club</span>

<!-- Google Translate Widget -->
<div class="gtranslate_wrapper"></div>
<script>
window.gtranslateSettings = {
    "default_language": "en",
    "native_language_names": true,
    "languages": ["en", "bn"],
    "wrapper_selector": ".gtranslate_wrapper",
    "switcher_horizontal_position": "right",
    "detect_browser_language": true,
    "flag_style": "3d",
    "float_switcher_open_direction": "top",
    "custom_domains": {
        "bn": "bn-BD"
    },
    "custom_css": `
        #goog-gt-tt, .goog-te-balloon-frame { font-family: 'Kalpurush', sans-serif !important; }
        .goog-text-highlight { font-family: 'Kalpurush', sans-serif !important; }
        .translated-content { font-family: 'Kalpurush', sans-serif !important; }
    `
}
</script>
<script src="https://cdn.gtranslate.net/widgets/latest/float.js" defer></script>

<!-- Tailwind / local styles are preloaded earlier in the document to avoid render-blocking -->
<!-- Custom JS already loaded with defer -->

<nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
    <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <a href="/admin" class="text-xl font-semibold text-gray-900 dark:text-white">Admin Panel</a>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <a href="?page=dashboard" class="<?php echo $page === 'dashboard' ? 'border-primary-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Dashboard
                    </a>
                    <a href="?page=executives" class="<?php echo $page === 'executives' ? 'border-primary-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Executives
                    </a>
                    <a href="?page=members" class="<?php echo $page === 'members' ? 'border-primary-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Members
                    </a>
                    <a href="?page=activities" class="<?php echo $page === 'activities' ? 'border-primary-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Activities
                    </a>
                    <a href="?page=settings" class="<?php echo $page === 'settings' ? 'border-primary-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Settings
                    </a>
                    <a href="?page=variables" class="<?php echo $page === 'variables' ? 'border-primary-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Variables
                    </a>
                </div>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:items-center">
                
                <a href="/admin/logout.php" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                    Logout
                </a>
            </div>
        </div>
    </div>
</nav>
</body>
</html>