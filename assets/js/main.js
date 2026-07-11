/**
 * ============================================================
 * Safe Haven — Theme Engine (main.js)
 * ============================================================
 *
 * Architecture:
 *  - CSS variables on body[data-role] drive ALL colors.
 *  - PHP session writes the authoritative data-role on <body>.
 *  - JS reads & writes localStorage for pre-login page persistence.
 *  - Priority:  PHP Session role > localStorage > :root defaults
 * ============================================================
 */

const THEME_KEY = 'safehaven_role';
const VALID_ROLES = ['client', 'therapist', 'volunteer'];

/**
 * handleThemeSwitch(role)
 * ─────────────────────────────────────────────────────────────
 * - Sets body[data-role] attribute (triggers CSS variable swap)
 * - Removes old theme-* classes, adds the new one
 * - Persists the choice to localStorage
 *
 * @param {string} role - 'client' | 'therapist' | 'volunteer'
 */
function handleThemeSwitch(role) {
    if (!role || !VALID_ROLES.includes(role.toLowerCase())) {
        role = 'client';
    }
    role = role.toLowerCase();

    const body = document.body;

    // 1. Set data-role attribute (CSS variable blocks keyed on this)
    body.setAttribute('data-role', role);

    // 2. Swap theme class (alternative selector support)
    VALID_ROLES.forEach(r => body.classList.remove('theme-' + r));
    body.classList.add('theme-' + role);

    // 3. Persist to localStorage for cross-page continuity
    try {
        localStorage.setItem(THEME_KEY, role);
    } catch (e) {
        // localStorage may be blocked in private browsing — silently ignore
    }

    // 4. Dispatch a custom event so other scripts can react
    document.dispatchEvent(new CustomEvent('themechange', { detail: { role } }));
}

/**
 * initTheme()
 * ─────────────────────────────────────────────────────────────
 * Priority:
 *  1. If NOT logged in  → always Beige (client). Period.
 *     The login/register FORM CARDS have their own PHP role styling.
 *     The navbar + footer + background must stay Beige for all guests.
 *  2. If logged in      → PHP session role is authoritative.
 *     Sync it to localStorage so next pre-login visit shows right role.
 */
function initTheme() {
    const body = document.body;
    const isLoggedIn = body.getAttribute('data-logged-in') === 'true';

    if (!isLoggedIn) {
        // ── Guest / not logged in: ALWAYS Beige, no exceptions ──
        handleThemeSwitch('client');
        return;
    }

    // ── Logged-in: PHP session is authoritative ──
    const serverRole = (body.getAttribute('data-role') || 'client').toLowerCase();
    const resolvedRole = VALID_ROLES.includes(serverRole) ? serverRole : 'client';

    // Sync to localStorage for any edge-case cross-page persistence
    try { localStorage.setItem(THEME_KEY, resolvedRole); } catch(e) {}

    handleThemeSwitch(resolvedRole);
}

/**
 * detectRoleFromURL()
 * ─────────────────────────────────────────────────────────────
 * For pre-login pages (login.php?role=therapist, register.php?role=volunteer)
 * the URL parameter reflects the chosen role. We detect it here as
 * a fallback in case PHP didn't write it to data-role.
 *
 * @returns {string|null}
 */
function detectRoleFromURL() {
    try {
        const params = new URLSearchParams(window.location.search);
        const urlRole = (params.get('role') || '').toLowerCase();
        return VALID_ROLES.includes(urlRole) ? urlRole : null;
    } catch(e) {
        return null;
    }
}

// ─────────────────────────────────────────────────────────────
// Scroll-aware header — directional hide/reveal
// Hides header on scroll down, reveals instantly on scroll up
// ─────────────────────────────────────────────────────────────
function initHeaderScrollBehavior() {
    const header = document.getElementById('main-header');
    if (!header) return;

    let lastScrollY = window.pageYOffset || document.documentElement.scrollTop;
    let ticking = false;
    const threshold = 8; // Small pixel buffer to avoid flickering on minor shakes

    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                const currentScrollY = window.pageYOffset || document.documentElement.scrollTop;
                
                // Keep header visible if mobile overlay menu is open
                if (document.body.classList.contains('mobile-menu-open')) {
                    header.style.transform = 'translate3d(0, 0, 0)';
                    lastScrollY = currentScrollY;
                    ticking = false;
                    return;
                }

                const diff = currentScrollY - lastScrollY;

                if (currentScrollY <= 50) {
                    // Always show header at the very top of page
                    header.style.transform = 'translate3d(0, 0, 0)';
                } else if (diff > threshold) {
                    // Scrolling down: hide header
                    header.style.transform = 'translate3d(0, -100%, 0)';
                } else if (diff < -threshold) {
                    // Scrolling up: reveal header immediately
                    header.style.transform = 'translate3d(0, 0, 0)';
                }

                lastScrollY = Math.max(0, currentScrollY);
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });
}

// ─────────────────────────────────────────────────────────────
// Staggered Grid Card Animations
// ─────────────────────────────────────────────────────────────
function initStaggerReveal() {
    // Select grid container elements
    const containers = document.querySelectorAll(
        '.kpi-grid, .stats-grid, .resource-grid, .questions-list, .services-grid, .faq-container, .grid-container, .features-grid, .therapist-list, .therapist-grid, .dashboard-grid, .session-list, .chat-rooms-list'
    );
    
    containers.forEach(container => {
        // Find immediate child components
        const children = container.querySelectorAll(
            ':scope > div, :scope > a, :scope > li, :scope > .therapist-card, :scope > .faq-item, :scope > .resource-card, :scope > .session-item-row'
        );
        
        children.forEach((child, index) => {
            if (!child.style.transitionDelay) {
                // Stagger delay maxing out at 500ms (10 items stagger depth)
                const delay = Math.min(index * 50, 500);
                child.style.transitionDelay = `${delay}ms`;
            }
            child.classList.add('premium-reveal');
        });
    });

    // Upgrade existing .animate-up elements to the premium transition engine
    document.querySelectorAll('.animate-up').forEach(el => {
        el.classList.add('premium-reveal');
    });
}

// ─────────────────────────────────────────────────────────────
// Intersection Observer for Reveal Animations (with GSAP acceleration)
// ─────────────────────────────────────────────────────────────
function initScrollAnimations() {
    const containers = document.querySelectorAll(
        '.kpi-grid, .stats-grid, .resource-grid, .questions-list, .services-grid, .faq-container, .grid-container, .features-grid, .therapist-list, .therapist-grid, .dashboard-grid, .session-list, .chat-rooms-list'
    );

    if (window.gsap) {
        // 1. GSAP-based scroll orchestration for grids
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const children = entry.target.querySelectorAll('.premium-reveal');
                    if (children.length > 0) {
                        gsap.fromTo(children, 
                            { opacity: 0, y: 30 }, 
                            { 
                                opacity: 1, 
                                y: 0, 
                                duration: 0.8, 
                                stagger: 0.05, 
                                ease: "power3.out",
                                clearProps: "transform" // clear transform so hover transforms are not broken!
                            }
                        );
                    }
                    observer.unobserve(entry.target);
                }
            });
        }, {
            root: null,
            rootMargin: '0px 0px -50px 0px',
            threshold: 0.05
        });

        containers.forEach(container => {
            const children = container.querySelectorAll(
                ':scope > div, :scope > a, :scope > li, :scope > .therapist-card, :scope > .faq-item, :scope > .resource-card, :scope > .session-item-row'
            );
            children.forEach(child => child.classList.add('premium-reveal'));
            observer.observe(container);
        });

        // 2. Observer for standalone .premium-reveal elements
        const standaloneReveals = document.querySelectorAll('.premium-reveal');
        const standaloneObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    if (entry.target.style.opacity !== "1") {
                        gsap.fromTo(entry.target, 
                            { opacity: 0, y: 30 }, 
                            { 
                                opacity: 1, 
                                y: 0, 
                                duration: 0.8, 
                                ease: "power3.out",
                                clearProps: "transform"
                            }
                        );
                    }
                    standaloneObserver.unobserve(entry.target);
                }
            });
        }, {
            root: null,
            rootMargin: '0px 0px -40px 0px',
            threshold: 0.05
        });

        standaloneReveals.forEach(el => {
            const hasObservedParent = el.closest(
                '.kpi-grid, .stats-grid, .resource-grid, .questions-list, .services-grid, .faq-container, .grid-container, .features-grid, .therapist-list, .therapist-grid, .dashboard-grid, .session-list, .chat-rooms-list'
            );
            if (!hasObservedParent) {
                standaloneObserver.observe(el);
            }
        });
    } else {
        // CSS Fallback path
        initStaggerReveal();
        const revealElements = document.querySelectorAll('.premium-reveal');
        if (!revealElements.length) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            root: null,
            rootMargin: '0px 0px -40px 0px',
            threshold: 0.05
        });

        revealElements.forEach(el => observer.observe(el));
    }
}

// ─────────────────────────────────────────────────────────────
// Page Deck Transitions (Slide-in / Slide-out with GSAP acceleration)
// ─────────────────────────────────────────────────────────────
function initPageTransitions() {
    const containers = document.querySelectorAll(
        'main, .dashboard-wrapper, .dashboard-main, .auth-card-container, .booking-container, .profile-container, .about-hero-sec, .breadcrumb-area, .services-hero, .therapist-details-container, .container-body'
    );
    
    let targets = Array.from(containers);
    
    if (targets.length === 0) {
        const header = document.getElementById('main-header');
        const footer = document.querySelector('footer');
        const announcement = document.querySelector('.top-announcement-bar');
        const overlay = document.getElementById('clientMobileMenuOverlay');
        const mobileNav = document.querySelector('.therapist-mobile-navbar');
        
        Array.from(document.body.children).forEach(child => {
            if (
                child !== header && 
                child !== footer && 
                child !== announcement && 
                child !== overlay && 
                child !== mobileNav &&
                child.tagName !== 'SCRIPT' &&
                child.tagName !== 'STYLE' &&
                !child.classList.contains('scroll-to-top-btn')
            ) {
                targets.push(child);
            }
        });
    }

    if (window.gsap) {
        // GSAP Slide-in sequence
        gsap.fromTo(targets, 
            { opacity: 0, x: 25 }, 
            { 
                opacity: 1, 
                x: 0, 
                duration: 0.6, 
                ease: "power2.out",
                stagger: 0.05,
                clearProps: "transform"
            }
        );
    } else {
        // CSS Fallback Slide-in
        targets.forEach(target => {
            target.classList.add('slide-entry');
            void target.offsetWidth;
            target.classList.add('active');
        });
    }

    // Intercept clicks on links for slide-exit transition
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link) return;

        const href = link.getAttribute('href');
        
        if (
            href && 
            !href.startsWith('#') && 
            !href.startsWith('javascript:') && 
            !href.startsWith('tel:') && 
            !href.startsWith('mailto:') &&
            !link.getAttribute('target') &&
            !link.classList.contains('no-transition') &&
            (link.hostname === window.location.hostname || !href.includes('://'))
        ) {
            e.preventDefault();
            
            if (window.gsap) {
                gsap.to(targets, {
                    opacity: 0,
                    x: -25,
                    duration: 0.4,
                    ease: "power2.in",
                    stagger: 0.02,
                    onComplete: () => {
                        window.location.href = href;
                    }
                });
            } else {
                targets.forEach(target => {
                    target.classList.add('slide-exit');
                });
                setTimeout(function() {
                    window.location.href = href;
                }, 400);
            }
        }
    });
}

// ─────────────────────────────────────────────────────────────
// Home Page Premium Hero Intro Animation
// ─────────────────────────────────────────────────────────────
function initHeroIntroAnimation() {
    if (!window.gsap) return;
    
    const heroTitle = document.querySelector('.hero-new__title');
    if (!heroTitle) return;

    // Check if the loader is present and visible
    const loader = document.getElementById('global-loader');
    const hasLoader = loader && loader.style.display !== 'none';

    // Set initial states
    gsap.set(['.hero-new__tag', '.hero-new__title', '.hero-new__subtitle', '.hero-new__actions', '.hero-new__image-wrapper', '.hero-new__floating-bar'], { opacity: 0 });

    const startAnimations = () => {
        const tl = gsap.timeline();
        tl.fromTo('.hero-new__tag', 
            { opacity: 0, y: 15 }, 
            { opacity: 1, y: 0, duration: 0.5, ease: "power2.out" }
        )
        .fromTo('.hero-new__title', 
            { opacity: 0, y: 25 }, 
            { opacity: 1, y: 0, duration: 0.8, ease: "power3.out" },
            "-=0.3"
        )
        .fromTo('.hero-new__subtitle', 
            { opacity: 0, y: 20 }, 
            { opacity: 1, y: 0, duration: 0.6, ease: "power2.out" },
            "-=0.4"
        )
        .fromTo('.hero-new__actions', 
            { opacity: 0, y: 15 }, 
            { opacity: 1, y: 0, duration: 0.5, ease: "power2.out" },
            "-=0.4"
        )
        .fromTo('.hero-new__image-wrapper', 
            { opacity: 0, scale: 0.97, x: 20 }, 
            { opacity: 1, scale: 1, x: 0, duration: 0.9, ease: "power2.out" },
            "-=0.7"
        )
        .fromTo('.hero-new__floating-bar',
            { opacity: 0, y: -20 },
            { opacity: 1, y: "-50%", duration: 0.6, ease: "power2.out" },
            "-=0.5"
        );
    };

    if (hasLoader) {
        // Wait for loader to fade out (after 2.5s display + 0.8s transition = ~3.3s total)
        setTimeout(startAnimations, 2700);
    } else {
        startAnimations();
    }
}

// ─────────────────────────────────────────────────────────────
// Floating Action Back-to-Top Widget
// ─────────────────────────────────────────────────────────────
function initScrollToTop() {
    const btn = document.getElementById('scrollToTopBtn');
    if (!btn) return;

    window.addEventListener('scroll', function() {
        const scrollY = window.pageYOffset || document.documentElement.scrollTop;
        if (scrollY > 400) {
            btn.classList.add('visible');
        } else {
            btn.classList.remove('visible');
        }
    }, { passive: true });

    btn.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// ─────────────────────────────────────────────────────────────
// Dropdown menus (supplemental helper)
// ─────────────────────────────────────────────────────────────
function initDropdowns() {
    const dropdowns = document.querySelectorAll('.nav-dropdown');
    if (!dropdowns.length) return;

    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        if (!toggle) return;
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropdowns.forEach(d => { if (d !== dropdown) d.classList.remove('active'); });
            dropdown.classList.toggle('active');
        });
    });

    document.addEventListener('click', () => {
        dropdowns.forEach(d => d.classList.remove('active'));
    });
}

// ─────────────────────────────────────────────────────────────
// DOMContentLoaded — bootstrap everything
// ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Theme setup
    initTheme();

    // Premium Interaction features
    initScrollAnimations();
    initPageTransitions();
    initHeaderScrollBehavior();
    initScrollToTop();
    initHeroIntroAnimation();

    // Dropdowns
    if (!window._headerDropdownsInit) {
        initDropdowns();
    }

    console.log('[Safe Haven] Premium GSAP Animation Engine initialized.');
});
