/**
 * Front-end behaviour for the public site.
 *
 * Everything here is progressive: if IntersectionObserver is missing, or the
 * visitor prefers reduced motion, elements are simply shown rather than animated.
 */

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const isCoarsePointer = window.matchMedia('(pointer: coarse)').matches;

/* ------------------------------------------------------------------ header */

function initHeader() {
    const header = document.querySelector('[data-header]');

    if (!header) return;

    const update = () => header.classList.toggle('is-scrolled', window.scrollY > 12);

    update();
    window.addEventListener('scroll', update, { passive: true });
}

/* -------------------------------------------------------------- mobile nav */

function initMobileNav() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const menu = document.querySelector('[data-mobile-menu]');

    if (!toggle || !menu) return;

    const setOpen = (open) => {
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute(
            'aria-label',
            open ? 'Close menu' : 'Open menu',
        );
        menu.classList.toggle('is-open', open);
        document.body.classList.toggle('no-scroll', open);
    };

    toggle.addEventListener('click', () => {
        setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });

    menu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            setOpen(false);
            toggle.focus();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 900) setOpen(false);
    });
}

/* ----------------------------------------------------------- scroll reveal */

function initReveal() {
    const elements = document.querySelectorAll('.reveal');

    if (!elements.length) return;

    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        elements.forEach((element) => element.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;

                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -8% 0px' },
    );

    elements.forEach((element) => observer.observe(element));
}

/* --------------------------------------------------------- active nav link */

/**
 * The site is a single page, so the active navigation entry is decided by which
 * section is currently in view rather than by the URL. Sections are observed
 * with a band across the upper third of the viewport; the last section to
 * intersect it wins, which is what a visitor expects when scrolling down.
 */
function initSectionSpy() {
    const links = [...document.querySelectorAll('[data-nav-anchor]')];

    if (!links.length || !('IntersectionObserver' in window)) return;

    const sections = links
        .map((link) => document.getElementById(link.dataset.navAnchor))
        .filter(Boolean);

    if (!sections.length) return;

    const visible = new Set();

    const paint = () => {
        let active = sections[0];

        sections.forEach((section) => {
            if (visible.has(section.id)) active = section;
        });

        links.forEach((link) => {
            const isActive = link.dataset.navAnchor === active.id;

            link.classList.toggle('is-active', isActive);

            if (isActive) {
                link.setAttribute('aria-current', 'true');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    };

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    visible.add(entry.target.id);
                } else {
                    visible.delete(entry.target.id);
                }
            });

            paint();
        },
        { rootMargin: '-20% 0px -60% 0px', threshold: 0 },
    );

    sections.forEach((section) => observer.observe(section));

    paint();
}

/* ----------------------------------------------------------------- parallax */

function initParallax() {
    if (prefersReducedMotion || isCoarsePointer) return;

    const frame = document.querySelector('[data-parallax-frame]');
    const glow = document.querySelector('.hero__glow');

    if (!frame && !glow) return;

    let ticking = false;

    window.addEventListener(
        'mousemove',
        (event) => {
            if (ticking) return;

            ticking = true;

            window.requestAnimationFrame(() => {
                const x = event.clientX / window.innerWidth - 0.5;
                const y = event.clientY / window.innerHeight - 0.5;

                if (frame) {
                    frame.style.transform = `perspective(1000px) rotateY(${x * 5}deg) rotateX(${-y * 5}deg)`;
                }

                if (glow) {
                    glow.style.transform = `translate3d(${x * 26}px, ${y * 18}px, 0)`;
                }

                ticking = false;
            });
        },
        { passive: true },
    );
}

/* ------------------------------------------------------------- contact form */

function initContactForm() {
    const form = document.querySelector('form[action$="/contact"]');

    if (!form) return;

    const message = form.querySelector('#message');
    const counter = form.querySelector('[data-char-count]');

    if (message && counter) {
        const max = message.getAttribute('maxlength') || 4000;

        const update = () => {
            counter.textContent = `${message.value.length} / ${max}`;
        };

        message.addEventListener('input', update);
        update();
    }

    form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"]');

        if (!button || button.disabled) return;

        // Stop accidental double submits; the form is full-page POST.
        button.disabled = true;
        button.dataset.originalLabel = button.textContent;
        button.textContent = 'Sending…';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initHeader();
    initMobileNav();
    initReveal();
    initSectionSpy();
    initParallax();
    initContactForm();
});
