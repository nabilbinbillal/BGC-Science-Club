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
<script>
// Update HTML attributes when language changes
document.addEventListener('DOMContentLoaded', function() {
    // Function to update HTML attributes based on language
    function updateHtmlAttributes(lang) {
        const html = document.documentElement;
        if (lang === 'bn' || lang === 'bn-BD') {
            html.setAttribute('dir', 'rtl');
            html.setAttribute('lang', 'bn-BD');
            document.body.classList.add('rtl', 'bn');
        } else {
            html.setAttribute('dir', 'ltr');
            html.setAttribute('lang', 'en');
            document.body.classList.remove('rtl', 'bn');
        }
    }

    // Initial check
    if (window.gtranslateSettings) {
        updateHtmlAttributes(window.gtranslateSettings.default_language);
    }

    // Listen for language changes
    document.addEventListener('gt_logo_click', function() {
        // Small delay to ensure the language has changed
        setTimeout(function() {
            const currentLang = document.querySelector('.gflag .gflag_selected')?.getAttribute('data-gt-lang') || 'en';
            updateHtmlAttributes(currentLang);
        }, 100);
    });

    // Also check for URL changes (in case of page reload with language parameter)
    const urlLang = new URLSearchParams(window.location.search).get('hl');
    if (urlLang) {
        updateHtmlAttributes(urlLang);
    }
});
</script>

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


            
            <!-- Mobile menu button -->
            <div class="md:hidden flex items-center">
                <button id="mobileMenuButton" class="text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 p-2 rounded-md focus:outline-none" aria-label="Toggle mobile menu" aria-expanded="false" aria-controls="mobileMenu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div id="mobileMenu" class="hidden md:hidden bg-white shadow-lg">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="/" class="<?php echo !isset($page) || $page === 'home' ? 'bg-primary-500 text-white' : 'text-gray-700'; ?> block px-3 py-2 rounded-md text-base font-medium hover:bg-primary-500 hover:text-white">Home</a>
                <a href="/about" class="<?php echo $page === 'about' ? 'bg-primary-500 text-white' : 'text-gray-700'; ?> block px-3 py-2 rounded-md text-base font-medium hover:bg-primary-500 hover:text-white">About</a>
                <a href="/executives" class="<?php echo $page === 'executives' ? 'bg-primary-500 text-white' : 'text-gray-700 dark:text-gray-300'; ?> block px-3 py-2 rounded-md text-base font-medium hover:bg-primary-500 hover:text-white">Executives</a>
                <a href="/members" class="<?php echo $page === 'members' ? 'bg-primary-500 text-white' : 'text-gray-700 dark:text-gray-300'; ?> block px-3 py-2 rounded-md text-base font-medium hover:bg-primary-500 hover:text-white">Members</a>
                <a href="/activities" class="<?php echo $page === 'activities' ? 'bg-primary-500 text-white' : 'text-gray-700 dark:text-gray-300'; ?> block px-3 py-2 rounded-md text-base font-medium hover:bg-primary-500 hover:text-white">Activities</a>
                <a href="/projects" class="<?php echo in_array($page, ['projects', 'project']) ? 'bg-primary-500 text-white' : 'text-gray-700 dark:text-gray-300'; ?> block px-3 py-2 rounded-md text-base font-medium hover:bg-primary-500 hover:text-white">Projects</a>
                <div class="border-t border-gray-200 dark:border-gray-700 my-2"></div>
                <a href="/classes" class="<?php echo in_array($page, ['classes', 'class']) ? 'bg-primary-500 text-white' : 'text-gray-700 dark:text-gray-300'; ?> block px-3 py-2 rounded-md text-base font-medium hover:bg-primary-500 hover:text-white">Classes</a>
                <a href="/departments" class="<?php echo in_array($page, ['departments', 'department']) ? 'bg-primary-500 text-white' : 'text-gray-700 dark:text-gray-300'; ?> block px-3 py-2 rounded-md text-base font-medium hover:bg-primary-500 hover:text-white">Departments</a>
                <a href="/join" class="block px-3 py-2 rounded-md text-base font-semibold bg-purple-600 hover:bg-purple-700 text-white text-center focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-300">Join Us</a>
            </div>
        </div>
    </div>
</header>

<style>
/* Mobile menu styles */
#mobileMenu {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 50;
    background: white !important; /* Force white background */
    color: #374151 !important; /* Force dark text color */
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    max-height: calc(100vh - 4rem);
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

/* Ensure all text in mobile menu is dark */
#mobileMenu {
    color: #374151 !important;
}

/* Force links to be visible but exclude special buttons */
#mobileMenu a:not(.bg-primary-500):not(.bg-purple-600) {
    color: #374151 !important;
}

/* Make sure Join Us button text stays white */
#mobileMenu a.bg-purple-600,
#mobileMenu a.bg-purple-600 * {
    color: white !important;
}

/* Hover states */
#mobileMenu a:hover:not(.bg-primary-500) {
    color: #1e40af !important;
}

#mobileMenu[aria-hidden="false"] {
    display: block;
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Ensure mobile menu is hidden on larger screens */
@media (min-width: 768px) {
    #mobileMenu {
        display: none !important;
    }
    
    #mobileMenuButton {
        display: none !important;
    }
}
</style>

<script>
// Simple and reliable mobile menu script
document.addEventListener('DOMContentLoaded', function() {
    const menuButton = document.getElementById('mobileMenuButton');
    const mobileMenu = document.getElementById('mobileMenu');
    
    if (!menuButton || !mobileMenu) return;
    
    // Toggle menu function
    function toggleMenu(isOpen) {
        const shouldOpen = isOpen !== undefined ? isOpen : mobileMenu.getAttribute('aria-hidden') === 'true';
        
        // Toggle aria attributes
        menuButton.setAttribute('aria-expanded', shouldOpen);
        mobileMenu.setAttribute('aria-hidden', !shouldOpen);
        
        // Toggle body scroll
        document.body.style.overflow = shouldOpen ? 'hidden' : '';
    }
    
    // Initialize
    mobileMenu.setAttribute('aria-hidden', 'true');
    
    // Toggle menu on button click
    menuButton.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
        toggleMenu(!isOpen);
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!mobileMenu.contains(e.target) && !menuButton.contains(e.target)) {
            toggleMenu(false);
        }
    });
    
    // Close menu when a link is clicked
    mobileMenu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function() {
            toggleMenu(false);
        });
    });
    
    // Close menu on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            toggleMenu(false);
        }
    });
    
    // Handle window resize
    function handleResize() {
        if (window.innerWidth >= 768) {
            // Reset mobile menu state on desktop
            toggleMenu(false);
        }
    }
    
    window.addEventListener('resize', handleResize);
    
    // Clean up on page unload
    window.addEventListener('unload', function() {
        document.body.style.overflow = '';
    });
});

// Segments dropdown script
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('segmentsDropdownBtn');
    const dropdown = document.getElementById('segmentsDropdown');
    
    if (btn && dropdown) {
        btn.addEventListener('mouseenter', () => {
            dropdown.classList.remove('opacity-0','invisible','-translate-y-1','pointer-events-none');
            dropdown.classList.add('opacity-100','visible','translate-y-0','pointer-events-auto');
        });
        btn.addEventListener('mouseleave', () => {
            dropdown.classList.remove('opacity-100','visible','translate-y-0','pointer-events-auto');
            dropdown.classList.add('opacity-0','invisible','-translate-y-1','pointer-events-none');
        });
        dropdown.addEventListener('mouseenter', () => {
            dropdown.classList.remove('opacity-0','invisible','-translate-y-1','pointer-events-none');
            dropdown.classList.add('opacity-100','visible','translate-y-0','pointer-events-auto');
        });
        dropdown.addEventListener('mouseleave', () => {
            dropdown.classList.remove('opacity-100','visible','translate-y-0','pointer-events-auto');
            dropdown.classList.add('opacity-0','invisible','-translate-y-1','pointer-events-none');
        });
    }
});
</script>