document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    const mobileThemeToggle = document.getElementById('mobileThemeToggle');
    const htmlElement = document.documentElement;

    // Theme toggle functionality
    function setTheme(theme, updateStorage = true) {
        if (theme === 'dark') {
            htmlElement.classList.add('dark');
            if (updateStorage) {
                localStorage.setItem('theme', 'dark');
                document.cookie = "theme=dark; path=/; max-age=31536000; SameSite=Lax";
            }
        } else {
            htmlElement.classList.remove('dark');
            if (updateStorage) {
                localStorage.setItem('theme', 'light');
                document.cookie = "theme=light; path=/; max-age=31536000; SameSite=Lax";
            }
        }
        // Dispatch event for other scripts that might need to react to theme changes
        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
    }

    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    // Initialize theme
    function initializeTheme() {
        const savedTheme = localStorage.getItem('theme') || getCookie('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme) {
            setTheme(savedTheme, false);
        } else if (prefersDark) {
            setTheme('dark', false);
        }

        // Add transition after initial theme is set
        setTimeout(() => {
            document.documentElement.classList.add('transition-colors', 'duration-200');
        }, 100);
    }

    // Theme toggle event listeners
    if (themeToggle) {
        themeToggle.addEventListener('click', () => 
            setTheme(htmlElement.classList.contains('dark') ? 'light' : 'dark')
        );
    }

    if (mobileThemeToggle) {
        mobileThemeToggle.addEventListener('click', () => 
            setTheme(htmlElement.classList.contains('dark') ? 'light' : 'dark')
        );
    }

    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (!localStorage.getItem('theme')) {
            setTheme(e.matches ? 'dark' : 'light', false);
        }
    });

    // Initialize theme
    initializeTheme();

    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobileMenuButton');
    const mobileMenu = document.getElementById('mobileMenu');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });

    // Segments dropdown: add click toggle fallback for touch devices and accessibility
    document.querySelectorAll('.nav-segments-button').forEach(button => {
        const container = button.closest('.group') || button.parentElement;
        const menu = container ? container.querySelector('.nav-segments-dropdown') : null;
        if (!menu) return;

        // Toggle on click
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const isOpen = menu.classList.contains('visible');
            if (isOpen) {
                menu.classList.remove('visible', 'opacity-100', 'pointer-events-auto');
                menu.classList.add('invisible', 'opacity-0', 'pointer-events-none');
                button.setAttribute('aria-expanded', 'false');
            } else {
                menu.classList.remove('invisible', 'opacity-0', 'pointer-events-none');
                menu.classList.add('visible', 'opacity-100', 'pointer-events-auto');
                button.setAttribute('aria-expanded', 'true');
            }
        });

        // Close when clicking outside
        document.addEventListener('click', (ev) => {
            if (!container.contains(ev.target)) {
                menu.classList.remove('visible', 'opacity-100', 'pointer-events-auto');
                menu.classList.add('invisible', 'opacity-0', 'pointer-events-none');
                button.setAttribute('aria-expanded', 'false');
            }
        });

        // Close on escape
        document.addEventListener('keydown', (ev) => {
            if (ev.key === 'Escape') {
                menu.classList.remove('visible', 'opacity-100', 'pointer-events-auto');
                menu.classList.add('invisible', 'opacity-0', 'pointer-events-none');
                button.setAttribute('aria-expanded', 'false');
            }
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
        threshold: 0.1
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

    // Class / department selector linkage
    const trackSelect = document.getElementById('track_option');
    const groupFieldWrapper = document.getElementById('groupFieldWrapper');
    const groupSelect = document.getElementById('group_name');
    const classGroupMap = window.classGroupMap || {};

    const renderGroupOptions = (className, selectedValue = '') => {
        const groups = classGroupMap[className] || [];
        groupSelect.innerHTML = '<option value="">Select group</option>';
        groups.forEach(group => {
            const option = document.createElement('option');
            option.value = group;
            option.textContent = group;
            if (group === selectedValue) {
                option.selected = true;
            }
            groupSelect.appendChild(option);
        });
    };

    const toggleGroupField = () => {
        if (!groupSelect) return;
        const value = trackSelect ? trackSelect.value : '';
        if (!value || value.indexOf('|') === -1) {
            if (groupFieldWrapper) {
                groupFieldWrapper.classList.add('hidden');
            }
            groupSelect.disabled = true;
            groupSelect.removeAttribute('required');
            groupSelect.innerHTML = '<option value="">Group not required</option>';
            groupSelect.value = '';
            return;
        }
        const [type, label] = value.split('|');
        if (type === 'class' && classGroupMap[label] && classGroupMap[label].length > 0) {
            if (groupFieldWrapper) {
                groupFieldWrapper.classList.remove('hidden');
            }
            groupSelect.disabled = false;
            groupSelect.setAttribute('required', 'required');
            renderGroupOptions(label, groupSelect.dataset.selected || '');
        } else {
            if (groupFieldWrapper) {
                groupFieldWrapper.classList.add('hidden');
            }
            groupSelect.disabled = true;
            groupSelect.removeAttribute('required');
            groupSelect.innerHTML = '<option value="">Group not required</option>';
            groupSelect.value = '';
        }
    };

    if (trackSelect && groupSelect) {
        trackSelect.addEventListener('change', () => {
            groupSelect.dataset.selected = '';
            toggleGroupField();
        });
        toggleGroupField();
    }

    // Home recent projects load more
    const loadMoreButton = document.getElementById('homeProjectsLoadMore');
    const projectCards = document.querySelectorAll('[data-home-project-card]');
    if (loadMoreButton && projectCards.length > 0) {
        const showBatch = 3;
        const revealNextBatch = () => {
            let revealed = 0;
            projectCards.forEach(card => {
                if (card.classList.contains('hidden') && revealed < showBatch) {
                    card.classList.remove('hidden');
                    revealed += 1;
                }
            });
            if (![...projectCards].some(card => card.classList.contains('hidden'))) {
                loadMoreButton.classList.add('hidden');
            }
        };

        // Hide button if not needed
        if (![...projectCards].some(card => card.classList.contains('hidden'))) {
            loadMoreButton.classList.add('hidden');
        }

        loadMoreButton.addEventListener('click', revealNextBatch);
    }
});