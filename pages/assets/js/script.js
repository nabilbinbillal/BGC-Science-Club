document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobileMenuButton');
    const mobileMenu = document.getElementById('mobileMenu');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function(e) {
            e.stopPropagation();
            mobileMenu.classList.toggle('hidden');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!mobileMenu.contains(event.target) && !mobileMenuButton.contains(event.target)) {
                mobileMenu.classList.add('hidden');
            }
        });

        // Close mobile menu when clicking a link
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
            });
        });
    }

    // Animation for elements when they come into view
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '50px'
    });

    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });

    // Initialize any form validations
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();

                // Highlight invalid fields
                const invalidFields = form.querySelectorAll(':invalid');
                invalidFields.forEach(field => {
                    field.classList.add('border-error-500');

                    // Add error message
                    const errorElement = document.createElement('p');
                    errorElement.classList.add('text-error-500', 'text-sm', 'mt-1');
                    errorElement.textContent = field.validationMessage;

                    // Remove any existing error messages
                    const existingError = field.nextElementSibling;
                    if (existingError && existingError.classList.contains('text-error-500')) {
                        existingError.remove();
                    }

                    field.parentNode.insertBefore(errorElement, field.nextSibling);
                });
            }
        }, false);

        // Remove error styling on input
        form.querySelectorAll('input, textarea, select').forEach(field => {
            field.addEventListener('input', function() {
                if (this.checkValidity()) {
                    this.classList.remove('border-error-500');
                    const errorMessage = this.nextElementSibling;
                    if (errorMessage && errorMessage.classList.contains('text-error-500')) {
                        errorMessage.remove();
                    }
                }
            });
        });
    });

    // Activity gallery lightbox (if exists)
    const galleryImages = document.querySelectorAll('.gallery-image');
    if (galleryImages.length > 0) {
        galleryImages.forEach(image => {
            image.addEventListener('click', function() {
                const lightbox = document.createElement('div');
                lightbox.id = 'lightbox';
                lightbox.classList.add('fixed', 'inset-0', 'bg-black', 'bg-opacity-75', 'flex', 'items-center', 'justify-center', 'z-50');

                const img = document.createElement('img');
                img.src = this.src;
                img.classList.add('max-h-screen', 'max-w-screen-lg', 'p-4');

                lightbox.appendChild(img);
                document.body.appendChild(lightbox);

                lightbox.addEventListener('click', function() {
                    document.body.removeChild(lightbox);
                });

                // Close on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && document.getElementById('lightbox')) {
                        document.body.removeChild(lightbox);
                    }
                });
            });
        });
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();

            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

                // Close mobile menu if open
                if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
                    mobileMenu.classList.add('hidden');
                }
            }
        });
    });

    // Member form toggle
    const showAddMemberForm = document.getElementById('showAddMemberForm');
    const memberFormContainer = document.getElementById('memberFormContainer');
    const cancelAddMember = document.getElementById('cancelAddMember');

    if (showAddMemberForm && memberFormContainer) {
        showAddMemberForm.addEventListener('click', function() {
            memberFormContainer.classList.remove('hidden');
        });

        if (cancelAddMember) {
            cancelAddMember.addEventListener('click', function() {
                memberFormContainer.classList.add('hidden');
            });
        }
    }
});
