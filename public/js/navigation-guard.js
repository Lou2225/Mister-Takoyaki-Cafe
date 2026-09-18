/**
 * Mister Takoyaki Cafe - Navigation Guard (v3.2)
 *
 * True "last click wins": the instant a new wire:navigate request starts,
 * abort whichever wire:navigate request was previously in flight, so a
 * slow earlier click can never physically land after a faster later one.
 *
 * This only touches requests carrying Livewire's own X-Livewire-Navigate
 * header, and only ever aborts the ONE request that just got superseded —
 * every other fetch, and every successful response, passes through
 * completely untouched. A landing-check on livewire:navigated is kept as
 * a safety net in case some navigation path doesn't go through fetch().
 */
(() => {
    'use strict';

    if (window.__mtcNavGuardLoaded) return;
    window.__mtcNavGuardLoaded = true;

    let latestTarget = window.location.pathname + window.location.search;
    let currentController = null;

    function normalizePath(url) {
        try {
            const u = new URL(url, window.location.origin);
            return u.pathname + u.search;
        } catch (_) {
            return String(url || '');
        }
    }

    function snapProgressBar() {
        const bar = document.querySelector('#nprogress .bar');
        if (!bar) return;
        bar.style.transition = 'none';
        bar.style.transform = 'translate3d(-85%, 0px, 0px)';
        void bar.offsetHeight;
        bar.style.transition = 'all 200ms ease';
    }

    // ── Cancel the previous in-flight navigation the instant a new one starts ──
    const originalFetch = window.fetch;
    window.fetch = function (input, init) {
        const isNavFetch = init && init.headers && (
            (typeof init.headers.get === 'function' && init.headers.get('X-Livewire-Navigate') !== null) ||
            ('X-Livewire-Navigate' in init.headers) ||
            (init.headers['X-Livewire-Navigate'] !== undefined)
        );

        if (!isNavFetch) {
            return originalFetch.apply(this, arguments);
        }

        // A newer navigation request has started — the previous one (if any)
        // is now stale. Abort it so it can never resolve and win the race.
        if (currentController) {
            currentController.abort();
        }
        const controller = new AbortController();
        currentController = controller;

        return originalFetch.call(this, input, { ...init, signal: controller.signal })
            .catch((err) => {
                // This exact request was the one we just aborted for being
                // superseded — swallow only that, so Livewire never runs
                // its success handler for a stale response. Any other kind
                // of failure (real network error) is rethrown untouched.
                if (err && err.name === 'AbortError' && controller.signal.aborted) {
                    return new Promise(() => {});
                }
                throw err;
            });
    };

    // Fires the instant a navigation starts (before its fetch goes out).
    document.addEventListener('livewire:navigate', (e) => {
        latestTarget = normalizePath(e.detail.url);
        snapProgressBar();
    });

    document.addEventListener('livewire:navigated', () => {
        currentController = null;

        // Safety net: if something outside our fetch wrapper still let a
        // stale page land, correct it immediately.
        const landedPath = window.location.pathname + window.location.search;
        if (landedPath !== latestTarget) {
            if (window.Alpine && typeof window.Alpine.navigate === 'function') {
                window.Alpine.navigate(latestTarget);
            } else {
                window.location.href = latestTarget;
            }
        }
    });
})();