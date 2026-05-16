import './bootstrap';

import * as Turbo from '@hotwired/turbo';
import Alpine from 'alpinejs';

window.Turbo = Turbo;
window.Alpine = Alpine;

Alpine.start();

const prefetchedUrls = new Set();

function setNavigatingState(isNavigating) {
    document.documentElement.classList.toggle('is-navigating', isNavigating);
    document.body?.setAttribute('aria-busy', isNavigating ? 'true' : 'false');
}

function initTurboAwareAlpine() {
    document.addEventListener('turbo:visit', () => {
        setNavigatingState(true);
    });

    document.addEventListener('turbo:before-render', () => {
        window.Alpine?.destroyTree?.(document.body);
        setNavigatingState(true);
    });

    document.addEventListener('turbo:render', () => {
        window.Alpine?.initTree?.(document.body);
        setNavigatingState(false);
    });

    document.addEventListener('turbo:load', () => {
        setNavigatingState(false);
        warmNavigationLinks();
    });
}

function prefetchUrl(href) {
    if (!href || prefetchedUrls.has(href)) {
        return;
    }

    const url = new URL(href, window.location.origin);
    if (url.origin !== window.location.origin) {
        return;
    }

    prefetchedUrls.add(href);

    fetch(url.toString(), {
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-Sec-Purpose': 'prefetch',
        },
    }).catch(() => {
        prefetchedUrls.delete(href);
    });
}

function warmNavigationLinks() {
    const links = Array.from(document.querySelectorAll('[data-nav-prefetch]'));

    links.forEach((link) => {
        if (link.dataset.prefetchBound === 'true') {
            return;
        }

        link.dataset.prefetchBound = 'true';
        const href = link.getAttribute('href');
        if (!href) {
            return;
        }

        const warm = () => prefetchUrl(href);
        link.addEventListener('mouseenter', warm, { passive: true });
        link.addEventListener('focus', warm, { passive: true });
        link.addEventListener('touchstart', warm, { passive: true });
    });

    const scheduleIdleWarm = window.requestIdleCallback
        ? window.requestIdleCallback.bind(window)
        : (callback) => window.setTimeout(callback, 350);

    scheduleIdleWarm(() => {
        links.slice(0, 6).forEach((link, index) => {
            const href = link.getAttribute('href');
            window.setTimeout(() => prefetchUrl(href), index * 120);
        });
    });
}

initTurboAwareAlpine();
