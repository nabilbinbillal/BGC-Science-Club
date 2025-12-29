<?php
session_start();
// Start output buffering so included page handlers can safely send header() redirects
ob_start();
define('ADMIN', true);
require_once '../config/db.php';
require_once '../includes/functions.php';

// Add a theme toggle button functionality
if (isset($_POST['theme'])) {
    $theme = $_POST['theme'] === 'dark' ? 'dark' : 'light';
    setcookie('theme', $theme, time() + (86400 * 30), '/'); // Save theme preference in a cookie for 30 days
    header('Location: index.php');
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - BGC Science Club</title>
    <!-- Favicon (admin area) -->
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="icon" type="image/x-icon" href="/favicon/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon/favicon.svg">
    <link rel="mask-icon" href="/favicon/favicon.svg" color="#2563eb">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon/favicon-16x16.png">
    <meta name="theme-color" content="#2563eb">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/style.css">

</head>
<body class="font-poppins antialiased bg-gray-100 dark:bg-gray-900">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-center h-16 px-4 border-b border-gray-200 dark:border-gray-700">
                    <img src="https://bgcscienceclub.org/assets/images/logo.webp" alt="BGC Science Club" class="h-8 w-auto">
                    <span class="ml-2 text-lg font-semibold text-gray-900 dark:text-white">Admin Panel</span>
                </div>
                
                <div class="flex flex-col flex-1 overflow-y-auto">
                    <nav class="flex-1 px-2 py-4 space-y-1">
                        <a href="index.php" class="<?php echo $page === 'dashboard' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-tachometer-alt w-5 h-5 mr-3"></i>
                            Dashboard
                        </a>
                        
                        <a href="?page=members" class="<?php echo $page === 'members' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-users w-5 h-5 mr-3"></i>
                            Members
                        </a>
                        
                        <a href="?page=executives" class="<?php echo $page === 'executives' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-user-tie w-5 h-5 mr-3"></i>
                            Executives
                        </a>
                        
                        <a href="?page=activities" class="<?php echo $page === 'activities' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-calendar-alt w-5 h-5 mr-3"></i>
                            Activities
                        </a>
                        
                        <a href="?page=events" class="<?php echo $page === 'events' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-bolt w-5 h-5 mr-3"></i>
                            Events
                        </a>
                        <a href="?page=projects" class="<?php echo $page === 'projects' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-project-diagram w-5 h-5 mr-3"></i>
                            Projects
                        </a>
                        
                        <?php if (isSuperAdmin()): ?>
                        <a href="?page=admins" class="<?php echo $page === 'admins' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-user-shield w-5 h-5 mr-3"></i>
                            Admins
                        </a>
                        <?php endif; ?>
                        
                        <a href="?page=settings" class="<?php echo $page === 'settings' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-cog w-5 h-5 mr-3"></i>
                            Settings
                        </a>
                        <a href="?page=variables" class="<?php echo $page === 'variables' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-sliders-h w-5 h-5 mr-3"></i>
                            Variables
                        </a>
                        <a href="?page=departments" class="<?php echo $page === 'departments' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-layer-group w-5 h-5 mr-3"></i>
                            Departments
                        </a>
                        
                        <a href="logout.php" class="flex items-center px-4 py-2 text-red-600 dark:text-red-400 rounded-md hover:bg-red-50 dark:hover:bg-red-900">
                            <i class="fas fa-sign-out-alt w-5 h-5 mr-3"></i>
                            Logout
                        </a>
                    </nav>
                </div>
            </div>
        </aside>

        <!-- Mobile Sidebar -->
        <div id="mobile-sidebar" class="fixed inset-0 z-40 hidden">
            <div class="fixed inset-0 bg-gray-600 bg-opacity-75" id="sidebar-backdrop"></div>
            <div class="fixed inset-y-0 left-0 flex flex-col w-64 bg-white dark:bg-gray-800">
                <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center">
                        <img src="https://bgcscienceclub.org/assets/images/logo.webp" alt="BGC Science Club" class="h-8 w-auto">
                        <span class="ml-2 text-lg font-semibold text-gray-900 dark:text-white">Admin Panel</span>
                    </div>
                    <button id="close-sidebar" class="text-gray-500 hover:text-gray-600 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex flex-col flex-1 overflow-y-auto">
                    <nav class="flex-1 px-2 py-4 space-y-1">
                        <a href="index.php" class="<?php echo $page === 'dashboard' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-tachometer-alt w-5 h-5 mr-3"></i>
                            Dashboard
                        </a>
                        
                        <a href="?page=members" class="<?php echo $page === 'members' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-users w-5 h-5 mr-3"></i>
                            Members
                        </a>
                        
                        <a href="?page=executives" class="<?php echo $page === 'executives' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-user-tie w-5 h-5 mr-3"></i>
                            Executives
                        </a>
                        
                        <a href="?page=activities" class="<?php echo $page === 'activities' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-calendar-alt w-5 h-5 mr-3"></i>
                            Activities
                        </a>
                        
                        <a href="?page=events" class="<?php echo $page === 'events' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-bolt w-5 h-5 mr-3"></i>
                            Events
                        </a>
                        <a href="?page=projects" class="<?php echo $page === 'projects' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-project-diagram w-5 h-5 mr-3"></i>
                            Projects
                        </a>
                        
                        <?php if (isSuperAdmin()): ?>
                        <a href="?page=admins" class="<?php echo $page === 'admins' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-user-shield w-5 h-5 mr-3"></i>
                            Admins
                        </a>
                        <?php endif; ?>
                        
                        <a href="?page=settings" class="<?php echo $page === 'settings' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-cog w-5 h-5 mr-3"></i>
                            Settings
                        </a>
                        <a href="?page=variables" class="<?php echo $page === 'variables' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-sliders-h w-5 h-5 mr-3"></i>
                            Variables
                        </a>
                        <a href="?page=departments" class="<?php echo $page === 'departments' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?> flex items-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-layer-group w-5 h-5 mr-3"></i>
                            Departments
                        </a>
                        
                        <a href="logout.php" class="flex items-center px-4 py-2 text-red-600 dark:text-red-400 rounded-md hover:bg-red-50 dark:hover:bg-red-900">
                            <i class="fas fa-sign-out-alt w-5 h-5 mr-3"></i>
                            Logout
                        </a>
                    </nav>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Top navbar -->
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center">
                        <button type="button" id="open-sidebar" class="md:hidden text-gray-500 hover:text-gray-600 focus:outline-none focus:text-gray-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="flex items-center">
                        
                        
                        <div class="ml-3 relative">
                            <button id="adminProfileButton" class="flex items-center focus:outline-none rounded-md px-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <span class="text-gray-700 dark:text-gray-300 mr-2"><?php echo $_SESSION['admin_name']; ?></span>
                                <img class="h-8 w-8 rounded-full" src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['admin_name']); ?>&background=random" alt="">
                            </button>
                            <div id="adminProfileMenu" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 hidden z-50">
                                <div class="py-1">
                                    <a href="/admin/index.php?page=settings" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">Profile</a>
                                    <a href="/admin/logout.php" class="block px-4 py-2 text-sm text-red-600 dark:text-red-300 hover:bg-gray-100 dark:hover:bg-red-900">Logout</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Main content area -->
            <main class="flex-1 overflow-y-auto bg-gray-100 dark:bg-gray-900">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <?php
                    switch ($page) {
                        case 'dashboard':
                            include 'pages/dashboard.php';
                            break;
                        case 'members':
                            include 'pages/members.php';
                            break;
                        case 'executives':
                            include 'pages/executives.php';
                            break;
                        case 'activities':
                            include 'pages/activities.php';
                            break;
                        case 'events':
                            include 'pages/events.php';
                            break;
                        case 'projects':
                            include 'pages/projects.php';
                            break;
                        case 'admins':
                            if (isSuperAdmin()) {
                                include 'pages/admins.php';
                            } else {
                                include 'pages/unauthorized.php';
                            }
                            break;
                        case 'settings':
                            include 'pages/settings.php';
                            break;
                        case 'variables':
                            include 'pages/variables.php';
                            break;
                        case 'departments':
                            include 'pages/departments.php';
                            break;
                        default:
                            include 'pages/dashboard.php';
                            break;
                    }
                    ?>
                </div>
            </main>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script>
        // Mobile sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileSidebar = document.getElementById('mobile-sidebar');
            const openSidebarBtn = document.getElementById('open-sidebar');
            const closeSidebarBtn = document.getElementById('close-sidebar');
            const sidebarBackdrop = document.getElementById('sidebar-backdrop');

            function openSidebar() {
                mobileSidebar.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                mobileSidebar.classList.add('hidden');
                document.body.style.overflow = '';
            }

            openSidebarBtn.addEventListener('click', openSidebar);
            closeSidebarBtn.addEventListener('click', closeSidebar);
            sidebarBackdrop.addEventListener('click', closeSidebar);

            // Close sidebar when clicking on any link
            const sidebarLinks = mobileSidebar.querySelectorAll('a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', closeSidebar);
            });

            // Admin profile button toggle
            const profileButton = document.getElementById('adminProfileButton');
            const profileMenu = document.getElementById('adminProfileMenu');
            if (profileButton && profileMenu) {
                profileButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileMenu.classList.toggle('hidden');
                });

                // Close when clicking outside
                document.addEventListener('click', function(ev) {
                    if (!profileMenu.contains(ev.target) && !profileButton.contains(ev.target)) {
                        profileMenu.classList.add('hidden');
                    }
                });

                // Close on escape
                document.addEventListener('keydown', function(ev) {
                    if (ev.key === 'Escape') profileMenu.classList.add('hidden');
                });
            }
        });
    </script>
</body>
<?php
// Flush any output buffering started above. This ensures headers can be sent from included pages.
if (ob_get_level()) ob_end_flush();
?>
</html>