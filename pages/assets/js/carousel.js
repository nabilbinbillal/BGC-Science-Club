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
        
        // Initialize
        this.init();
    }
    
    init() {
        // Add event listeners
        this.carousel.addEventListener('mousedown', this.touchStart.bind(this));
        this.carousel.addEventListener('touchstart', this.touchStart.bind(this));
        this.carousel.addEventListener('mouseup', this.touchEnd.bind(this));
        this.carousel.addEventListener('touchend', this.touchEnd.bind(this));
        this.carousel.addEventListener('mousemove', this.touchMove.bind(this));
        this.carousel.addEventListener('touchmove', this.touchMove.bind(this));
        this.carousel.addEventListener('mouseleave', this.touchEnd.bind(this));
        
        // Prevent context menu
        window.oncontextmenu = function(event) {
            if (event.target.closest('.carousel')) {
                event.preventDefault();
                event.stopPropagation();
                return false;
            }
        }
        
        // Set initial position
        this.setPositionByIndex();
    }
    
    getPositionX(event) {
        return event.type.includes('mouse') ? event.pageX : event.touches[0].clientX;
    }
    
    touchStart(event) {
        this.isDragging = true;
        this.startPos = this.getPositionX(event);
        this.animationID = requestAnimationFrame(this.animation.bind(this));
        this.carousel.style.cursor = 'grabbing';
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
                this.currentSlide = Math.min(this.currentSlide + 1, this.slides.length - 1);
            } else {
                this.currentSlide = Math.max(this.currentSlide - 1, 0);
            }
        }
        
        this.setPositionByIndex();
    }
    
    animation() {
        this.setSliderPosition();
        if (this.isDragging) requestAnimationFrame(this.animation.bind(this));
    }
    
    setPositionByIndex() {
        const slideWidth = this.slides[0].offsetWidth;
        this.currentTranslate = this.currentSlide * -slideWidth;
        this.prevTranslate = this.currentTranslate;
        this.setSliderPosition();
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