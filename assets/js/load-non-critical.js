// Function to load non-critical CSS
function loadCSS(href, media = 'all') {
    return new Promise((resolve, reject) => {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        link.media = 'print';
        link.onload = () => {
            link.media = media;
            resolve(link);
        };
        link.onerror = reject;
        document.head.appendChild(link);
    });
}

// Function to load non-critical JavaScript
function loadJS(src, attrs = {}) {
    return new Promise((resolve, reject) => {
        // Skip if already loaded
        if (document.querySelector(`script[src="${src}"]`)) {
            resolve();
            return;
        }
        
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        
        // Add any additional attributes
        Object.entries(attrs).forEach(([key, value]) => {
            script.setAttribute(key, value);
        });
        
        script.onload = () => {
            console.log(`Successfully loaded script: ${src}`);
            resolve();
        };
        
        script.onerror = (error) => {
            console.warn(`Failed to load script: ${src}`, error);
            // Don't reject to prevent Promise.all from failing completely
            resolve();
        };
        
        document.body.appendChild(script);
    });
}

// Function to check if a resource exists
async function resourceExists(url) {
    try {
        const response = await fetch(url, { method: 'HEAD' });
        return response.ok;
    } catch (error) {
        console.warn(`Resource check failed for ${url}:`, error);
        return false;
    }
}

// Load non-critical resources when the page has finished loading
window.addEventListener('load', async function() {
    // Load non-critical CSS
    const cssFiles = [
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css',
        'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css',
        '/assets/css/styles.css'
    ];
    
    // Load non-critical JavaScript
    const jsFiles = [
        'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
        '/assets/js/scripts.js'
    ];
    
    // Load CSS files with existence check
    for (const href of cssFiles) {
        try {
            if (href.startsWith('http') || await resourceExists(href)) {
                await loadCSS(href);
            } else {
                console.warn(`CSS file not found, skipping: ${href}`);
            }
        } catch (error) {
            console.warn(`Failed to load CSS: ${href}`, error);
        }
    }
    
    // Load JavaScript files with existence check
    for (const src of jsFiles) {
        try {
            if (src.startsWith('http') || await resourceExists(src)) {
                await loadJS(src);
            } else {
                console.warn(`JS file not found, skipping: ${src}`);
            }
        } catch (error) {
            console.warn(`Failed to load JS: ${src}`, error);
        }
    }
    
    // Load fonts
    try {
        const fontLink = document.createElement('link');
        fontLink.href = 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap';
        fontLink.rel = 'stylesheet';
        document.head.appendChild(fontLink);
    } catch (error) {
        console.warn('Failed to load fonts:', error);
    }
    
    // Add class to body when non-critical resources are loaded
    document.documentElement.classList.add('non-critical-loaded');
    
    // Register service worker if supported
    registerServiceWorker();
});

// Register service worker
async function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.register('/sw.js', {
                scope: '/',
                updateViaCache: 'none'
            });
            
            if (registration.installing) {
                console.log('Service worker installing');
            } else if (registration.waiting) {
                console.log('Service worker installed');
            } else if (registration.active) {
                console.log('Service worker active');
            }
            
            // Check for updates
            registration.addEventListener('updatefound', () => {
                console.log('New service worker found. Installing...');
                const newWorker = registration.installing;
                
                newWorker.addEventListener('statechange', () => {
                    console.log('Service worker state changed:', newWorker.state);
                });
            });
            
            // Check for updates every hour
            setInterval(() => {
                registration.update().catch(err => 
                    console.log('Service worker update check failed:', err)
                );
            }, 60 * 60 * 1000);
            
        } catch (error) {
            console.error('Service worker registration failed:', error);
        }
    }
}

// Fallback for browsers that don't support JavaScript
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js').then(registration => {
            console.log('ServiceWorker registration successful');
        }).catch(err => {
            console.error('ServiceWorker registration failed: ', err);
        });
    });
}
