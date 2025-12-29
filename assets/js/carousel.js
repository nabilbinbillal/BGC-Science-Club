// Carousel functionality
class Carousel {
    constructor(element) {
        this.carousel = element;
        this.slides = Array.from(this.carousel.children);
        this.currentSlide = 0;
        this.isDragging = false;
        this.startPos = 0;
        this.currentTranslate = 0;
        this.prevTranslate = 0;
        this.animationID = 0;
        this.autoplayInterval = null;
        this.autoplayDelay = 5000; // 5 seconds between slides
        this.isHovered = false;
        
        // Initialize
        this.init();
    }
    
    init() {
        // Setup slides
        this.slides.forEach((slide, index) => {
            slide.style.transition = 'transform 0.3s ease-out';
        });
        
        // Add event listeners
        this.carousel.addEventListener('mousedown', this.touchStart.bind(this));
        this.carousel.addEventListener('touchstart', this.touchStart.bind(this));
        this.carousel.addEventListener('mouseup', this.touchEnd.bind(this));
        this.carousel.addEventListener('touchend', this.touchEnd.bind(this));
        this.carousel.addEventListener('mousemove', this.touchMove.bind(this));
        this.carousel.addEventListener('touchmove', this.touchMove.bind(this));
        this.carousel.addEventListener('mouseleave', this.touchEnd.bind(this));
        
        // Add hover listeners to pause autoplay
        this.carousel.addEventListener('mouseenter', () => {
            this.isHovered = true;
            this.pauseAutoplay();
        });
        this.carousel.addEventListener('mouseleave', () => {
            this.isHovered = false;
            this.startAutoplay();
        });
        
        // Prevent context menu
        window.oncontextmenu = function(event) {
            if (event.target.closest('.carousel')) {
                event.preventDefault();
                event.stopPropagation();
                return false;
            }
        }
        
        // Set initial position and start autoplay
        this.setPositionByIndex();
        this.startAutoplay();
    }
    
    startAutoplay() {
        if (this.autoplayInterval) clearInterval(this.autoplayInterval);
        this.autoplayInterval = setInterval(() => {
            if (!this.isHovered && !this.isDragging) {
                this.nextSlide();
            }
        }, this.autoplayDelay);
    }
    
    pauseAutoplay() {
        if (this.autoplayInterval) {
            clearInterval(this.autoplayInterval);
            this.autoplayInterval = null;
        }
    }
    
    nextSlide() {
        this.currentSlide = (this.currentSlide + 1) % this.slides.length;
        this.setPositionByIndex(true);
    }
    
    previousSlide() {
        this.currentSlide = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
        this.setPositionByIndex(true);
    }
    
    getPositionX(event) {
        return event.type.includes('mouse') ? event.pageX : event.touches[0].clientX;
    }
    
    touchStart(event) {
        this.isDragging = true;
        this.startPos = this.getPositionX(event);
        this.animationID = requestAnimationFrame(this.animation.bind(this));
        this.carousel.style.cursor = 'grabbing';
        this.pauseAutoplay();
    }
    
    touchMove(event) {
        if (!this.isDragging) return;
        
        const currentPosition = this.getPositionX(event);
        this.currentTranslate = this.prevTranslate + currentPosition - this.startPos;
    }
    
    touchEnd() {
        this.isDragging = false;
        cancelAnimationFrame(this.animationID);
        this.carousel.style.cursor = 'grab';
        
        const movedBy = this.currentTranslate - this.prevTranslate;
        
        // Determine if slide should advance
        if (Math.abs(movedBy) > 100) {
            if (movedBy < 0) {
                this.nextSlide();
            } else {
                this.previousSlide();
            }
        } else {
            // Return to current slide if not moved enough
            this.setPositionByIndex(true);
        }
        
        if (!this.isHovered) {
            this.startAutoplay();
        }
    }
    
    animation() {
        this.setSliderPosition();
        if (this.isDragging) requestAnimationFrame(this.animation.bind(this));
    }
    
    setPositionByIndex(animate = false) {
        const slideWidth = this.slides[0].offsetWidth;
        this.currentTranslate = this.currentSlide * -slideWidth;
        this.prevTranslate = this.currentTranslate;
        
        if (animate) {
            this.carousel.style.transition = 'transform 0.3s ease-out';
        } else {
            this.carousel.style.transition = 'none';
        }
        
        this.setSliderPosition();
        
        if (animate) {
            setTimeout(() => {
                this.carousel.style.transition = 'none';
            }, 300);
        }
    }
    
    setSliderPosition() {
        this.carousel.style.transform = `translateX(${this.currentTranslate}px)`;
    }
}

// Initialize all carousels on the page
document.addEventListener('DOMContentLoaded', () => {
    const carousels = document.querySelectorAll('.carousel');
    carousels.forEach(carousel => new Carousel(carousel));
}); 