// Enhanced Lazy Loading for Images
document.addEventListener('DOMContentLoaded', function() {
    // Options for the Intersection Observer
    const options = {
        root: null, // Use the viewport as the root
        rootMargin: '200px', // Load images 200px before they enter the viewport
        threshold: 0.01 // Trigger when 1% of the image is visible
    };

    // Function to handle image loading
    const loadImage = (image) => {
        // If the image is already loaded, skip
        if (image.classList.contains('lazyloaded')) {
            return;
        }

        // Get the data-src attribute (or src if data-src doesn't exist)
        const src = image.getAttribute('data-src') || image.getAttribute('src');
        
        // If there's no src, skip
        if (!src) {
            return;
        }

        // Create a new image to preload
        const img = new Image();
        
        // When the image loads
        img.onload = function() {
            // Add a small delay for better UX
            setTimeout(() => {
                // Set the actual src
                image.src = src;
                
                // Add a class to mark as loaded
                image.classList.add('lazyloaded');
                
                // Remove the data-src attribute to prevent duplicate loading
                image.removeAttribute('data-src');
                
                // Dispatch an event that the image has been loaded
                image.dispatchEvent(new Event('lazyloaded'));
            }, 100);
        };
        
        // If there's an error loading the image
        img.onerror = function() {
            // If there's a fallback image, use it
            const fallback = image.getAttribute('data-fallback');
            if (fallback) {
                image.src = fallback;
                image.classList.add('lazyloaded');
            }
            
            // Add error class for styling
            image.classList.add('lazyload-error');
            
            // Dispatch an error event
            image.dispatchEvent(new Event('lazyload-error'));
        };
        
        // Start loading the image
        img.src = src;
    };

    // Create an Intersection Observer instance
    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                loadImage(entry.target);
                // Stop observing once the image is loaded
                observer.unobserve(entry.target);
            }
        });
    }, options);

    // Find all images that should be lazy loaded
    const lazyImages = document.querySelectorAll('img[loading="lazy"], img[data-src]');
    
    // Start observing each image
    lazyImages.forEach(image => {
        // Skip if already loaded
        if (image.classList.contains('lazyloaded')) {
            return;
        }
        
        // Add a class for styling
        image.classList.add('lazyload');
        
        // If there's a placeholder, use it
        const placeholder = image.getAttribute('data-placeholder');
        if (placeholder && !image.src) {
            image.src = placeholder;
        }
        
        // Start observing the image
        observer.observe(image);
    });

    // Optional: Load all images if the user has disabled JavaScript
    // This is a fallback for users without JavaScript
    document.documentElement.classList.add('js-enabled');
});

// Fallback for browsers that don't support Intersection Observer
if (!('IntersectionObserver' in window)) {
    document.addEventListener('DOMContentLoaded', function() {
        const lazyImages = document.querySelectorAll('img[loading="lazy"], img[data-src]');
        lazyImages.forEach(img => {
            const src = img.getAttribute('data-src') || img.getAttribute('src');
            if (src) {
                img.src = src;
            }
        });
    });
}
