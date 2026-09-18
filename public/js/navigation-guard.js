/**
 * Mister Takoyaki Cafe - Navigation Guard & "Last Click Wins" Controller (v2.5)
 * 
 * Guarantees:
 * 1. "Last Click Wins": Clicking a new link immediately aborts any previous in-flight
 *    navigation HTTP request on the wire (saving server resources) and completely destroys
 *    its DOM swap callback so it never executes or flashes on screen.
 * 2. Pure Programmatic Alpine Navigation: Bypasses Livewire 3's mousedown prefetch zombie-cache,
 *    ensuring fresh fetches and allowing instant cancellation without stale closure locks.
 * 3. Double-Click Deduplication: Rapid clicks on the same link are cleanly ignored.
 * 4. Error Suppression: Cleanly filters benign AbortError and Alpine cancellation tokens from DevTools.
 */
(() => {
    'use strict';

    if (window.__mtcNavGuardLoaded) return;
    window.__mtcNavGuardLoaded = true;

    // ─── 1. Navigation State Tracking ─────────────────────────────────
    let currentNavSeq = 0;
    let currentNavTarget = '';
    let isNavigating = false;
    let activeNavAbortController = null;

    function getNormalizedPath(url) {
        try {
            const parsed = new URL(url, window.location.origin);
            return parsed.pathname + parsed.search;
        } catch (_) {
            return String(url || '');
        }
    }

    // ─── 2. Intercept window.fetch for Navigation Requests ────────────
    const originalFetch = window.fetch;

    window.fetch = function (input, init) {
        // Only intercept Livewire SPA navigation requests (X-Livewire-Navigate header)
        const isNavFetch = init && init.headers && (
            (typeof init.headers.get === 'function' && init.headers.get('X-Livewire-Navigate') !== null) ||
            ('X-Livewire-Navigate' in init.headers) ||
            (init.headers['X-Livewire-Navigate'] !== undefined)
        );

        if (!isNavFetch) {
            return originalFetch.apply(this, arguments);
        }

        const thisSeq = currentNavSeq;
        const controller = activeNavAbortController || new AbortController();
        activeNavAbortController = controller;

        const mergedInit = { ...init, signal: controller.signal };

        return originalFetch.call(this, input, mergedInit)
            .then((response) => {
                // If a newer navigation superseded this request while in-flight,
                // return an unsettled promise so Livewire's callback NEVER runs,
                // NEVER swaps the DOM, and NEVER updates browser history!
                if (thisSeq !== currentNavSeq) {
                    return new Promise(() => {});
                }
                isNavigating = false;
                if (activeNavAbortController === controller) {
                    activeNavAbortController = null;
                }
                return response;
            })
            .catch((error) => {
                // Suppress aborts cleanly for superseded navigations
                if (thisSeq !== currentNavSeq || error.name === 'AbortError' || error === 'superseded') {
                    return new Promise(() => {});
                }
                isNavigating = false;
                if (activeNavAbortController === controller) {
                    activeNavAbortController = null;
                }
                throw error;
            });
    };

    // ─── 3. Bypass Mousedown Prefetch Zombie Cache ────────────────────
    // Livewire 3 attaches mousedown listeners that prefetch HTML into a local dictionary.
    // If an aborted fetch leaves that dictionary incomplete, subsequent clicks freeze.
    // Intercepting mousedown in the capture phase prevents this buggy prefetch store.
    document.addEventListener('mousedown', (e) => {
        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const link = e.target.closest('a[wire\\:navigate], a[wire\\:navigate\\.hover]');
        if (link) {
            e.stopImmediatePropagation();
        }
    }, true);

    // ─── 4. Click Interceptor: "Last Click Wins" ──────────────────────
    document.addEventListener('click', (e) => {
        // Allow modified clicks (Ctrl+click, Cmd+click, middle click) to open in new tab
        if (e.which > 1 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
            return;
        }

        const link = e.target.closest('a[wire\\:navigate], a[wire\\:navigate\\.hover]');
        if (!link) return;

        // Skip non-navigational links
        if (link.hasAttribute('download') || link.getAttribute('target') === '_blank') {
            return;
        }

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
            return;
        }

        let linkUrl;
        try {
            linkUrl = new URL(link.href, window.location.origin);
        } catch (_) {
            return;
        }

        // External links navigate normally
        if (linkUrl.origin !== window.location.origin) {
            return;
        }

        const targetPath = linkUrl.pathname + linkUrl.search;
        const currentPath = window.location.pathname + window.location.search;

        // Clicking the currently active page: prevent redundant reload
        if (targetPath === currentPath && !isNavigating) {
            e.preventDefault();
            return;
        }

        // Rapid duplicate click on the exact same destination currently loading: ignore
        if (isNavigating && targetPath === currentNavTarget) {
            e.preventDefault();
            e.stopPropagation();
            return;
        }

        // ── LAST CLICK WINS: DESTROY RECENT LINK PROGRESS ──
        e.preventDefault();
        e.stopPropagation();

        // 1. Abort previous in-flight request immediately
        if (activeNavAbortController) {
            activeNavAbortController.abort('superseded');
            activeNavAbortController = null;
        }

        // 2. Increment sequence generation so any in-flight response is rejected
        const thisSeq = ++currentNavSeq;
        currentNavTarget = targetPath;
        isNavigating = true;

        // 3. Create fresh AbortController for the winning click
        activeNavAbortController = new AbortController();

        // 4. Visually snap the progress bar back to 15% to indicate new navigation started
        const activeBar = document.querySelector('#nprogress .bar');
        if (activeBar) {
            activeBar.style.transition = 'none';
            activeBar.style.transform = 'translate3d(-85%, 0px, 0px)';
            void activeBar.offsetHeight;
            activeBar.style.transition = 'all 200ms ease';
        }

        // 5. Safety watchdog timeout (10s)
        setTimeout(() => {
            if (isNavigating && currentNavSeq === thisSeq) {
                isNavigating = false;
                currentNavTarget = '';
                activeNavAbortController = null;
            }
        }, 10000);

        // 6. Trigger clean navigation via Alpine.navigate
        if (window.Alpine && typeof window.Alpine.navigate === 'function') {
            window.Alpine.navigate(link.href);
        } else {
            window.location.href = link.href;
        }
    }, true);

    // ─── 5. Navigation Lifecycle Listeners ─────────────────────────────
    document.addEventListener('livewire:navigated', () => {
        isNavigating = false;
        currentNavTarget = '';
        activeNavAbortController = null;
    });

    // ─── 6. Global Error & Unhandled Rejection Suppression ─────────────
    window.addEventListener('unhandledrejection', (event) => {
        if (event.reason) {
            if (
                event.reason.isFromCancelledTransition === true ||
                event.reason.name === 'AbortError' ||
                event.reason === 'superseded' ||
                (typeof event.reason === 'string' && event.reason.includes('superseded')) ||
                (typeof event.reason.message === 'string' && (
                    event.reason.message.includes('superseded') ||
                    event.reason.message.includes("Cannot set properties of undefined (setting 'html')")
                ))
            ) {
                event.preventDefault();
            }
        }
    });

    window.addEventListener('error', (event) => {
        if (event.message && event.message.includes("Cannot set properties of undefined (setting 'html')")) {
            event.preventDefault();
            return true;
        }
    });
})();
