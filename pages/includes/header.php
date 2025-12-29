<?php
// Initialize page variable if not set
if (!isset($page)) {
    $page = 'home'; // Default to 'home' if not specified
}
?>
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

<header class="bg-white dark:bg-gray-800 shadow-md transition-colors duration-200 relative z-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-4">
            <div class="flex items-center">
                <a href="/" class="flex items-center">
                    <img src="https://bgcscienceclub.org/assets/images/logo.webp" 
                         srcset="https://bgcscienceclub.org/assets/images/logo-48w.webp 1x, https://bgcscienceclub.org/assets/images/logo.webp 2x"
                         alt="BGC Science Club Logo" 
                         class="h-12 w-12 rounded-full object-cover" 
                         width="48" 
                         height="48" 
                         loading="lazy">
                    <span class="ml-3 text-xl font-semibold text-gray-900 dark:text-white no-translate">BGC Science Club</span>
                </a>
            </div>
            
<nav class="hidden md:flex items-center space-x-4">

    <!-- Other nav links -->
    <a href="/" class="<?php echo !isset($page) || $page === 'home' ? 'text-primary-500' : 'text-gray-700 dark:text-gray-300'; ?> px-3 py-2 rounded-md text-sm font-medium hover:text-primary-500">Home</a>
    <a href="/about" class="<?php echo $page === 'about' ? 'text-primary-500' : 'text-gray-700 dark:text-gray-300'; ?> px-3 py-2 rounded-md text-sm font-medium hover:text-primary-500">About</a>
    <a href="/executives" class="<?php echo $page === 'executives' ? 'text-primary-500' : 'text-gray-700 dark:text-gray-300'; ?> px-3 py-2 rounded-md text-sm font-medium hover:text-primary-500">Executives</a>
    <a href="/members" class="<?php echo $page === 'members' ? 'text-primary-500' : 'text-gray-700 dark:text-gray-300'; ?> px-3 py-2 rounded-md text-sm font-medium hover:text-primary-500">Members</a>
    <a href="/activities" class="<?php echo $page === 'activities' ? 'text-primary-500' : 'text-gray-700 dark:text-gray-300'; ?> px-3 py-2 rounded-md text-sm font-medium hover:text-primary-500">Activities</a>
    <a href="/projects" class="<?php echo in_array($page, ['projects', 'project']) ? 'text-primary-500' : 'text-gray-700 dark:text-gray-300'; ?> px-3 py-2 rounded-md text-sm font-medium hover:text-primary-500">Projects</a>

    <!-- Segments dropdown -->
    <div class="relative" id="segmentsDropdownWrapper">
        <button id="segmentsDropdownBtn" aria-expanded="false" class="flex items-center px-3 py-2 rounded-md text-sm font-medium <?php echo in_array($page, ['classes', 'class', 'departments', 'department']) ? 'bg-primary-100 text-primary-600 dark:bg-primary-900 dark:text-primary-200' : 'text-gray-700 dark:text-gray-300'; ?> hover:text-primary-500 focus:outline-none">
            Segments
            <svg class="ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div id="segmentsDropdown" class="absolute left-0 top-full w-56 bg-white dark:bg-gray-700 rounded-md shadow-lg border border-gray-100 dark:border-gray-600 opacity-0 invisible pointer-events-none transition duration-200 transform -translate-y-1 p-2 z-50">
            <div class="grid grid-cols-1 gap-2">
                <div class="px-2 py-1 border-b border-gray-100 dark:border-gray-600">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide mb-1">Intermediate Classes</div>
                    <?php foreach (getClassOptions() as $c): ?>
                        <a href="/class/<?php echo htmlspecialchars(slugifyText($c)); ?>" class="block px-2 py-1 rounded text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600"><?php echo htmlspecialchars($c); ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="px-2 py-1">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide mb-1">Departments</div>
                    <?php $deptIcons = getDepartmentMetaMap(); foreach (getDepartmentOptions() as $d): ?>
                        <a href="/department/<?php echo htmlspecialchars(slugifyText($d)); ?>" class="block px-2 py-1 rounded text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600"><span class="mr-2"><?php echo htmlspecialchars($deptIcons[$d] ?? '🔬'); ?></span><?php echo htmlspecialchars($d); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Join Us button -->
    <a href="/join" class="px-4 py-2 rounded-md text-sm font-semibold bg-purple-600 hover:bg-purple-700 text-white text-center shadow-2xl transform hover:-translate-y-0.5 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-300" style="box-shadow: 0 6px 18px rgba(124, 58, 237, 0.28);">
        Join Us
    </a>

</nav>

<script>
    // JS to show/hide the dropdown on hover
    const btn = document.getElementById('segmentsDropdownBtn');
    const dropdown = document.getElementById('segmentsDropdown');

    btn.addEventListener('mouseenter', () => {
        dropdown.classList.remove('opacity-0','invisible','-translate-y-1','pointer-events-none');
        dropdown.classList.add('opacity-100','visible','translate-y-0','pointer-events-auto');
    });
    btn.addEventListener('mouseleave', () => {
        dropdown.classList.remove('opacity-100','visible','translate-y-0','pointer-events-auto');
        dropdown.classList.add('opacity-0','invisible','-translate-y-1','pointer-events-none');
    });

    // Keep dropdown visible when hovering over it
    dropdown.addEventListener('mouseenter', () => {
        dropdown.classList.remove('opacity-0','invisible','-translate-y-1','pointer-events-none');
        dropdown.classList.add('opacity-100','visible','translate-y-0','pointer-events-auto');
    });
    dropdown.addEventListener('mouseleave', () => {
        dropdown.classList.remove('opacity-100','visible','translate-y-0','pointer-events-auto');
        dropdown.classList.add('opacity-0','invisible','-translate-y-1','pointer-events-none');
    });
</script>


            
            <!-- Mobile menu button - NO STYLES, JUST THE BUTTON -->
            <div class="md:hidden">
                <button id="mobileMenuBtn" name="mobileMenu" type="button" class="p-2">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile menu - SIMPLE AND DIRECT -->
        <div id="mobileMenu" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 50; background: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div class="p-4">
                <div class="space-y-1">
                    <a href="/" class="<?php echo !isset($page) || $page === 'home' ? 'bg-primary-500 text-white hover:bg-primary-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?> block px-3 py-2 rounded-md text-base font-medium">Home</a>
                    <a href="/about" class="<?php echo $page === 'about' ? 'bg-primary-500 text-white hover:bg-primary-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?> block px-3 py-2 rounded-md text-base font-medium">About</a>
                    <a href="/executives" class="<?php echo $page === 'executives' ? 'bg-primary-500 text-white hover:bg-primary-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?> block px-3 py-2 rounded-md text-base font-medium">Executives</a>
                    <a href="/members" class="<?php echo $page === 'members' ? 'bg-primary-500 text-white hover:bg-primary-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?> block px-3 py-2 rounded-md text-base font-medium">Members</a>
                    <a href="/activities" class="<?php echo $page === 'activities' ? 'bg-primary-500 text-white hover:bg-primary-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?> block px-3 py-2 rounded-md text-base font-medium">Activities</a>
                    <a href="/projects" class="<?php echo in_array($page, ['projects', 'project']) ? 'bg-primary-500 text-white hover:bg-primary-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?> block px-3 py-2 rounded-md text-base font-medium">Projects</a>
                    
                    <div class="border-t border-gray-200 dark:border-gray-700 my-2"></div>
                    
                    <a href="/classes" class="<?php echo in_array($page, ['classes', 'class']) ? 'bg-primary-500 text-white hover:bg-primary-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?> block px-3 py-2 rounded-md text-base font-medium">Classes</a>
                    <a href="/departments" class="<?php echo in_array($page, ['departments', 'department']) ? 'bg-primary-500 text-white hover:bg-primary-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?> block px-3 py-2 rounded-md text-base font-medium">Departments</a>
                    
                    <a href="/join" class="block px-3 py-2 rounded-md text-base font-semibold bg-purple-600 hover:bg-purple-700 text-white text-center">Join Us</a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- MINIMAL, FOOLPROOF JAVASCRIPT -->
<script>
// This is the SIMPLEST possible mobile menu script
// It will work in ALL browsers including Chrome mobile

// Run immediately - don't wait for DOMContentLoaded
(function() {
    console.log('Mobile menu script loading...');
    
    // Give the browser a tiny moment to render the button
    setTimeout(function() {
        console.log('Setting up mobile menu...');
        
        // Get elements
        var menuBtn = document.getElementById('mobileMenuBtn');
        var mobileMenu = document.getElementById('mobileMenu');
        
        // Check if elements exist
        if (!menuBtn) {
            console.error('Menu button not found! Looking for #mobileMenuBtn');
            // Try alternative ID
            menuBtn = document.getElementById('mobileMenuButton');
            console.log('Tried alternative ID:', menuBtn);
        }
        
        if (!mobileMenu) {
            console.error('Mobile menu not found! Looking for #mobileMenu');
        }
        
        if (!menuBtn || !mobileMenu) {
            console.log('Cannot set up mobile menu - elements missing');
            return;
        }
        
        console.log('Elements found, setting up click handler');
        
        // Make absolutely sure the button is visible
        menuBtn.style.display = 'block';
        menuBtn.style.visibility = 'visible';
        menuBtn.style.opacity = '1';
        
        // Add a visible border for debugging (remove later)
       // menuBtn.style.border = '2px solid red';
        
        // Simple toggle function
        function toggleMenu() {
            console.log('Toggle menu called');
            if (mobileMenu.style.display === 'none' || !mobileMenu.style.display) {
                console.log('Showing menu');
                mobileMenu.style.display = 'block';
            } else {
                console.log('Hiding menu');
                mobileMenu.style.display = 'none';
            }
        }
        
        // Add click event
        menuBtn.addEventListener('click', function(e) {
            console.log('Menu button clicked!');
            e.preventDefault();
            e.stopPropagation();
            toggleMenu();
        });
        
        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (mobileMenu.style.display === 'block') {
                if (!mobileMenu.contains(e.target) && !menuBtn.contains(e.target)) {
                    console.log('Click outside, closing menu');
                    mobileMenu.style.display = 'none';
                }
            }
        });
        
        // Close when link is clicked
        var links = mobileMenu.querySelectorAll('a');
        for (var i = 0; i < links.length; i++) {
            links[i].addEventListener('click', function() {
                console.log('Link clicked, closing menu');
                mobileMenu.style.display = 'none';
            });
        }
        
        // Hide on desktop
        function checkScreenSize() {
            if (window.innerWidth >= 768) {
                mobileMenu.style.display = 'none';
            }
        }
        
        window.addEventListener('resize', checkScreenSize);
        checkScreenSize();
        
        console.log('Mobile menu setup complete!');
        
    }, 100); // Small delay to ensure DOM is ready
})();
</script>

<style>
/* MINIMAL CSS - NO TAILWIND, NO COMPLEXITY */

/* Show button on mobile */
@media (max-width: 767px) {
    #mobileMenuBtn {
        display: block !important;
        visibility: visible !important;
    }
}

/* Hide button and menu on desktop */
@media (min-width: 768px) {
    #mobileMenuBtn {
        display: none !important;
    }
    #mobileMenu {
        display: none !important;
    }
}

/* Dark mode support */
.dark #mobileMenu {
    background: #1f2937 !important;
}

.dark #mobileMenu a {
    color: #d1d5db;
}

.dark #mobileMenu a.bg-primary-500 {
    background-color: #3b82f6;
    color: white;
}
</style>