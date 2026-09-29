/**
 * Motion layer for the public site.
 *
 * Everything here is additive: if this file fails to load, or the visitor
 * prefers reduced motion, or the browser lacks the API being used, the page is
 * simply static and fully readable. The preloader is the one element that has to
 * survive on its own, which the CSS failsafe handles.
 *
 * Scroll work is funnelled through a single rAF loop rather than one listener
 * per effect, so the whole system costs one measurement pass per frame.
 */

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
const finePointer = window.matchMedia('(pointer: fine)');

const prefersReduced = () => reduced.matches;
const hasObserver = 'IntersectionObserver' in window;

/* ------------------------------------------------------------------ helpers */

const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

/**
 * One shared scroll loop. Callbacks get the current scrollY each frame, and the
 * loop only runs while something is actually subscribed and the page is moving.
 */
function createScrollLoop() {
    const callbacks = new Set();

    let lastY = window.scrollY;
    let queued = false;

    const frame = () => {
        const y = window.scrollY;

        lastY = y;
        queued = false;

        callbacks.forEach((callback) => callback(y));
    };

    const request = () => {
        if (queued) return;

        queued = true;
        window.requestAnimationFrame(frame);
    };

    return {
        subscribe(callback) {
            callbacks.add(callback);
            window.addEventListener('scroll', request, { passive: true });
            window.addEventListener('resize', request, { passive: true });
        },
        request,
    };
}

/* ---------------------------------------------------------------- preloader */

/**
 * The curtain is server rendered, so it has to be able to retire without this
 * file. The stylesheet hides it on its own after a few seconds, and the scroll
 * lock below has the same failsafe, so a failure here costs a visitor a slightly
 * longer pause and nothing else.
 */
function initPreloader() {
    const preloader = document.querySelector('[data-preloader]');

    if (!preloader) return;

    // Take ownership of the digits and the bar; the stylesheet's own failsafe is
    // left armed as a backstop.
    preloader.classList.add('is-scripted');

    const count = preloader.querySelector('[data-preloader-count]');
    const bar = preloader.querySelector('[data-preloader-bar]');

    const DURATION = 1400;
    const start = performance.now();

    // Hold the page still behind the curtain, and guarantee the hold ends even
    // if this function throws part way through.
    document.body.classList.add('is-loading');
    window.setTimeout(() => document.body.classList.remove('is-loading'), 3200);

    const dismiss = () => {
        preloader.classList.add('is-done');
        document.body.classList.remove('is-loading');

        window.setTimeout(() => {
            preloader.classList.add('is-gone');
            preloader.remove();
        }, 800);
    };

    if (prefersReduced()) {
        dismiss();
        return;
    }

    const tick = (now) => {
        const elapsed = Math.min((now - start) / DURATION, 1);
        // Ease out, so the last digits settle rather than ticking linearly.
        const eased = 1 - (1 - elapsed) ** 3;

        if (count) count.textContent = String(Math.round(eased * 100)).padStart(3, '0');
        if (bar) bar.style.transform = `scaleX(${eased})`;

        if (elapsed < 1) {
            window.requestAnimationFrame(tick);
            return;
        }

        dismiss();
    };

    window.requestAnimationFrame(tick);
}

/* ------------------------------------------------------------ hero headline */

/**
 * The headline lines are already separate elements in the template, so all that
 * is left is to number them and let the class that reveals them do the rest.
 */
function initHeroLines() {
    const lines = document.querySelectorAll('.hero__title .mask-line__inner');

    lines.forEach((line, index) => {
        line.style.setProperty('--line', String(index));
    });

    // Deferred a frame so the first paint shows the masked state and the
    // transition actually runs, rather than being skipped as a no-change.
    window.requestAnimationFrame(() => {
        document.documentElement.classList.add('is-ready');
    });
}

/* ------------------------------------------------------------ word reveals */

/**
 * Section headings arrive word by word. Splitting happens here rather than in
 * the markup because the headings come from the CMS and their line breaks are
 * not known until the browser has the final text and font.
 */
function initWordReveals() {
    const headings = document.querySelectorAll('[data-reveal-words]');

    if (!headings.length) return;

    // Nothing to animate into, so the headings are simply left as they are.
    if (prefersReduced() || !hasObserver) return;

    headings.forEach((heading) => {
        // A heading that has already been split would nest masks.
        if (heading.dataset.wordsReady) return;
        heading.dataset.wordsReady = 'true';

        const words = heading.textContent.trim().split(/\s+/);

        heading.textContent = '';

        words.forEach((word, index) => {
            const outer = document.createElement('span');
            const inner = document.createElement('span');

            outer.className = 'word';
            inner.className = 'word__inner';
            inner.style.setProperty('--i', String(index));
            inner.textContent = word;

            outer.appendChild(inner);
            heading.appendChild(outer);

            // Keep the spaces the split removed, or the words run together.
            if (index < words.length - 1) {
                heading.appendChild(document.createTextNode(' '));
            }
        });
    });

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;

                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        },
        { threshold: 0.2, rootMargin: '0px 0px -6% 0px' },
    );

    headings.forEach((heading) => observer.observe(heading));
}

/* ----------------------------------------------------------- media reveals */

function initMediaReveals() {
    const media = document.querySelectorAll('[data-media-reveal]');

    if (!media.length) return;

    if (!hasObserver || prefersReduced()) {
        media.forEach((element) => element.classList.add('is-visible'));
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
        { threshold: 0.15, rootMargin: '0px 0px -5% 0px' },
    );

    media.forEach((element) => observer.observe(element));
}

/* ------------------------------------------------------------ slide reveals */

/**
 * A tile that holds more than one shot keeps the extra shots stacked in the
 * same frame, so they are cycled through while the pointer rests on the tile
 * rather than laid out below it.
 *
 * The timer only exists while a tile is actually hovered, and a tile with a
 * single shot never gets one. Without a pointer there is no hover to react to,
 * so the cover simply stays put.
 */
function initWorkGalleries() {
    const galleries = document.querySelectorAll('[data-work-gallery]');

    if (!galleries.length) return;

    if (prefersReduced() || !window.matchMedia('(hover: hover)').matches) return;

    const HOLD_MS = 1600;

    galleries.forEach((gallery) => {
        const shots = [...gallery.querySelectorAll('.latest-work-card__shot')];

        if (shots.length < 2) return;

        let position = 0;
        let timer = null;

        const show = (next) => {
            shots[position].classList.remove('is-current');
            position = (next + shots.length) % shots.length;
            shots[position].classList.add('is-current');
        };

        const start = () => {
            stop();
            timer = window.setInterval(() => show(position + 1), HOLD_MS);
        };

        const stop = () => {
            if (timer === null) return;

            window.clearInterval(timer);
            timer = null;
        };

        const tile = gallery.closest('[data-work-card]');

        tile?.addEventListener('pointerenter', start);
        tile?.addEventListener('pointerleave', () => {
            stop();
            show(0);
        });
    });
}

/**
 * The pill the pointer drags across a latest-work tile, naming the tile it is
 * over so the destination is clear before the click.
 *
 * Only built where there is a real pointer to drag. On a touch screen the pill
 * would never be seen and its markup would just be dead weight, so nothing is
 * created at all.
 */
function initWorkCursor() {
    const cursor = document.querySelector('[data-work-cursor]');
    const cards = document.querySelectorAll('[data-work-card]');

    if (!cursor || !cards.length) return;

    if (prefersReduced() || !window.matchMedia('(hover: hover)').matches) return;

    let pointerX = 0;
    let pointerY = 0;
    let frame = null;

    /*
     * Only the position is written, into two custom properties the stylesheet
     * folds into its own transform. Writing a full transform here instead would
     * overwrite the scale and fade the class is meant to animate, because an
     * inline style outranks a stylesheet rule.
     */
    const place = () => {
        frame = null;
        cursor.style.setProperty('--x', `${pointerX}px`);
        cursor.style.setProperty('--y', `${pointerY}px`);
    };

    document.addEventListener(
        'pointermove',
        (event) => {
            if (event.pointerType !== 'mouse') return;

            pointerX = event.clientX;
            pointerY = event.clientY;

            if (frame === null) frame = window.requestAnimationFrame(place);
        },
        { passive: true },
    );

    // Shown only while a tile is actually under the cursor.
    cards.forEach((card) => {
        card.addEventListener('pointerenter', () => {
            cursor.textContent = card.dataset.workLabel || '';
            cursor.classList.add('is-on');
        });

        card.addEventListener('pointerleave', () => cursor.classList.remove('is-on'));
    });
}

/**
 * Section headings, images and project content slide into place together as
 * each block reaches the viewport. The reveal is one-shot: an element that has
 * arrived stays put, so scrolling back up never replays it.
 */
function initSlideReveals() {
    const slides = document.querySelectorAll('[data-slide]');

    if (!slides.length) return;

    if (!hasObserver || prefersReduced()) {
        slides.forEach((element) => element.classList.add('is-visible'));
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
        { threshold: 0.12, rootMargin: '0px 0px -4% 0px' },
    );

    slides.forEach((element) => observer.observe(element));
}

/* -------------------------------------------------------------- count-ups */

function initCountUps() {
    const numbers = document.querySelectorAll('[data-count]');

    if (!numbers.length) return;

    if (!hasObserver || prefersReduced()) return;

    const run = (element) => {
        const target = Number.parseInt(element.dataset.count, 10);

        if (Number.isNaN(target)) return;

        const DURATION = 1200;
        const start = performance.now();

        const tick = (now) => {
            const elapsed = Math.min((now - start) / DURATION, 1);
            const eased = 1 - (1 - elapsed) ** 4;

            element.textContent = String(Math.round(eased * target));

            if (elapsed < 1) window.requestAnimationFrame(tick);
        };

        window.requestAnimationFrame(tick);
    };

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;

                run(entry.target);
                observer.unobserve(entry.target);
            });
        },
        { threshold: 0.6 },
    );

    numbers.forEach((element) => observer.observe(element));
}

/* ------------------------------------------------------------------ tilt */

function initTilt() {
    if (!finePointer.matches) return;

    const targets = document.querySelectorAll('[data-tilt]');

    targets.forEach((target) => {
        if (prefersReduced()) return;

        target.addEventListener('pointermove', (event) => {
            if (event.pointerType !== 'mouse') return;

            const box = target.getBoundingClientRect();
            const x = (event.clientX - box.left) / box.width - 0.5;
            const y = (event.clientY - box.top) / box.height - 0.5;

            target.style.transform =
                `perspective(1200px) rotateY(${x * 6}deg) rotateX(${-y * 6}deg)`;
        });

        target.addEventListener('pointerleave', () => {
            target.style.transform = '';
        });
    });
}

/* -------------------------------------------------------------- magnetics */

function initMagnetics() {
    if (!finePointer.matches) return;

    document.querySelectorAll('[data-magnetic]').forEach((element) => {
        if (prefersReduced()) return;

        // The pull is a fraction of the pointer's offset, so the target trails
        // the cursor instead of snapping to it.
        const STRENGTH = 0.28;

        element.addEventListener('pointermove', (event) => {
            if (event.pointerType !== 'mouse') return;

            const box = element.getBoundingClientRect();
            const x = (event.clientX - box.left - box.width / 2) * STRENGTH;
            const y = (event.clientY - box.top - box.height / 2) * STRENGTH;

            element.style.transform = `translate3d(${x}px, ${y}px, 0)`;
        });

        element.addEventListener('pointerleave', () => {
            element.style.transform = '';
        });
    });
}

/* -------------------------------------------------------------- marquee */

/**
 * The strip's movement is a pure CSS animation; nothing here runs per frame.
 * The script's only job is to make sure the track is long enough to loop
 * without ever showing empty space.
 *
 * The template renders two identical groups, which is plenty on a phone. On a
 * wide screen one group can be narrower than the viewport, and travelling a
 * single group would then expose a gap at the wrap point. So groups are cloned
 * in until there is a full group's width of slack beyond the right edge, and the
 * measured group width replaces the stylesheet's -50% fallback.
 */
function initMarquee() {
    const marquee = document.querySelector('.marquee');
    const track = marquee?.querySelector('.marquee__track');
    const group = track?.querySelector('.marquee__group');

    if (!marquee || !track || !group) return;

    // A ceiling so a pathological viewport cannot flood the DOM with copies.
    const MAX_COPIES = 12;

    const fill = () => {
        const groupWidth = group.getBoundingClientRect().width;

        // Nothing measurable yet, so leave the stylesheet's percentage in place.
        if (groupWidth <= 0) return;

        marquee.style.setProperty('--marquee-shift', `${groupWidth}px`);

        // The visible window is the container wide, and it slides a whole group,
        // so the track needs the container plus one group of content.
        const target = clamp(Math.ceil(marquee.clientWidth / groupWidth) + 1, 2, MAX_COPIES);

        while (track.children.length < target) {
            track.appendChild(group.cloneNode(true));
        }

        while (track.children.length > target) {
            track.lastElementChild.remove();
        }
    };

    fill();

    let frame = null;
    const schedule = () => {
        if (frame !== null) return;

        frame = window.requestAnimationFrame(() => {
            frame = null;
            fill();
        });
    };

    window.addEventListener('resize', schedule, { passive: true });

    // Group width depends on the loaded font, so the first measurement can land
    // on a fallback face and come up short.
    document.fonts?.ready.then(schedule);
}

/* --------------------------------------------------- scroll-driven effects */

/**
 * The scroll progress line, the drifting headings and the back-to-top button all
 * read from the same frame, so they can never disagree about where the page is.
 */
function initScrollEffects() {
    const progress = document.querySelector('[data-scroll-progress]');
    const drifts = [...document.querySelectorAll('[data-drift]')];
    const toTop = document.querySelector('[data-to-top]');

    toTop?.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: prefersReduced() ? 'auto' : 'smooth' });
    });

    if (prefersReduced()) {
        if (toTop) {
            toTop.hidden = false;
            toTop.classList.add('is-shown');
        }

        return;
    }

    let shown = false;

    const loop = createScrollLoop();

    loop.subscribe((y) => {
        const max = document.documentElement.scrollHeight - window.innerHeight;

        if (progress) {
            progress.style.setProperty('--scroll', String(max > 0 ? clamp(y / max, 0, 1) : 0));
        }

        if (drifts.length) {
            drifts.forEach((element) => {
                const box = element.getBoundingClientRect();

                // Only measure elements that are anywhere near the viewport.
                if (box.bottom < -200 || box.top > window.innerHeight + 200) return;

                const centre = box.top + box.height / 2;
                const offset = (centre - window.innerHeight / 2) / window.innerHeight;

                element.style.setProperty('--drift', `${(-offset * 46).toFixed(2)}px`);
            });
        }

        if (toTop) {
            const shouldShow = y > window.innerHeight * 0.9;

            if (shouldShow !== shown) {
                shown = shouldShow;
                toTop.hidden = false;
                toTop.classList.toggle('is-shown', shown);
            }
        }
    });

    loop.request();
}

/* ------------------------------------------------------------------- boot */

/**
 * Each effect is independent, so one that throws must not stop the rest from
 * setting up. Nothing here is load bearing; the page is complete without it.
 */
const effects = [
    ['preloader', initPreloader],
    ['headline', initHeroLines],
    ['words', initWordReveals],
    ['media', initMediaReveals],
    ['slides', initSlideReveals],
    ['counters', initCountUps],
    ['tilt', initTilt],
    ['magnetics', initMagnetics],
    ['marquee', initMarquee],
    ['work-cursor', initWorkCursor],
    ['work-galleries', initWorkGalleries],
    ['scroll', initScrollEffects],
];

document.addEventListener('DOMContentLoaded', () => {
    effects.forEach(([name, run]) => {
        try {
            run();
        } catch (error) {
            // Deliberately swallowed: a broken effect is a missing flourish, and
            // the failsafes in the stylesheet cover the parts that hide content.
            console.warn(`[motion] ${name} failed to start`, error);
        }
    });
});
