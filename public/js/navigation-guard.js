/**
 * Mister Takoyaki Cafe - Navigation Guard & True SPA Engine (v4.0)
 *
 * 1. True Instant SPA (In-Memory SWR Page Cache):
 *    - Visited pages are stored in RAM.
 *    - Subsequent navigations swap in 0ms without server round-trip or progress bar.
 *    - Silent background revalidation keeps the cache fresh without interrupting the user.
 * 2. Smart Idle Pre-Warming:
 *    - Pre-caches primary navigation links during idle moments so even the
 *      first click to a page feels instant.
 * 3. Automatic Cache Invalidation:
 *    - Cleared on Livewire state mutations (POST updates, order placement, settings save).
 *    - Cleared on branch switch or role change.
 * 4. "Last Click Wins" Concurrency:
 *    - In-flight navigations for superseded destinations are aborted cleanly.
 * 5. Smooth Visual Progress:
 *    - Zero-progress-bar for 0ms cache hits.
 *    - Sleek glide-to-100% progress bar for uncached first loads.
 * 6. 100% Pure SPA (strictly zero window.location.href reloads).
 */
(() => {
    'use strict';

    if (window.__mtcNavGuardLoaded) return;
    window.__mtcNavGuardLoaded = true;

    // ── In-Memory Page Cache Store ──
    const pageCache = new Map();
    const CACHE_TTL = 3 * 60 * 1000; // 3 minutes fresh TTL

    let currentNavSeq = 0;
    let activeNavTarget = null;
    let activeNavAbortController = null;
    let navWatchdogTimer = null;

    function normalizePath(url) {
        try {
            const u = new URL(url, window.location.origin);
            return u.pathname + u.search;
        } catch (_) {
            return String(url || '');
        }
    }

    // ── Prime Cache with Initial Document ──
    try {
        const currentPath = normalizePath(window.location.href);
        pageCache.set(currentPath, {
            html: document.documentElement.outerHTML,
            url: window.location.href,
            timestamp: Date.now()
        });
    } catch (_) {}

    // ── Visual Progress Bar Helpers ──
    function restartProgressBar() {
        let nprogress = document.getElementById('nprogress');
        if (!nprogress) {
            nprogress = document.createElement('div');
            nprogress.id = 'nprogress';
            nprogress.innerHTML = '<div class="bar" role="bar" style="transform: translate3d(-85%, 0px, 0px); transition: transform 200ms ease 0s;"><div class="peg"></div></div>';
            document.body.appendChild(nprogress);
        } else {
            const bar = nprogress.querySelector('.bar');
            if (bar) {
                bar.style.transition = 'none';
                bar.style.transform = 'translate3d(-85%, 0px, 0px)';
                void bar.offsetHeight;
                bar.style.transition = 'transform 200ms ease';
            }
        }
    }

    function completeProgressBar() {
        const bar = document.querySelector('#nprogress .bar');
        if (bar) {
            bar.style.transition = 'transform 120ms ease';
            bar.style.transform = 'translate3d(0%, 0px, 0px)';
            setTimeout(() => {
                clearProgress();
            }, 180);
        } else {
            clearProgress();
        }
    }

    function clearProgress() {
        const nprogress = document.getElementById('nprogress');
        if (nprogress) {
            nprogress.remove();
        }
    }

    // ── Background Revalidation Helper (SWR) ──
    function backgroundRevalidate(urlPath, fullUrl) {
        setTimeout(() => {
            originalFetch(fullUrl, {
                headers: { 'X-Livewire-Navigate': '' }
            }).then((res) => {
                if (res.ok) {
                    return res.text().then((freshHtml) => {
                        pageCache.set(urlPath, {
                            html: freshHtml,
                            url: res.url || fullUrl,
                            timestamp: Date.now()
                        });
                    });
                }
            }).catch(() => {});
        }, 100);
    }

    // ── Intercept window.fetch for SWR & Last Click Wins ──
    const originalFetch = window.fetch;
    window.fetch = function (input, init) {
        // Invalidate page cache when mutations occur (POST / PUT / DELETE)
        if (init && init.method && init.method.toUpperCase() !== 'GET') {
            pageCache.clear();
            return originalFetch.apply(this, arguments);
        }

        const isNavFetch = init && init.headers && (
            (typeof init.headers.get === 'function' && init.headers.get('X-Livewire-Navigate') !== null) ||
            ('X-Livewire-Navigate' in init.headers) ||
            (init.headers['X-Livewire-Navigate'] !== undefined)
        );

        if (!isNavFetch) {
            return originalFetch.apply(this, arguments);
        }

        const targetUrl = normalizePath(typeof input === 'string' ? input : (input && input.url ? input.url : ''));
        const fullUrl = new URL(targetUrl, window.location.origin).href;

        // ── CACHE HIT (0ms Instant SPA Swap) ──
        const cached = pageCache.get(targetUrl);
        if (cached && (Date.now() - cached.timestamp < CACHE_TTL)) {
            // Trigger silent background revalidation to keep cache fresh
            backgroundRevalidate(targetUrl, fullUrl);

            // Construct synthetic 200 response with correct destination URL
            const res = new Response(cached.html, {
                status: 200,
                statusText: 'OK',
                headers: new Headers({
                    'Content-Type': 'text/html; charset=UTF-8',
                    'X-MTC-SPA-Cache': 'HIT'
                })
            });
            Object.defineProperty(res, 'url', { value: fullUrl });

            return Promise.resolve(res);
        }

        // ── CACHE MISS: Live Network Fetch with "Last Click Wins" ──
        // Abort previous in-flight navigation if user clicked a DIFFERENT page
        if (activeNavAbortController && activeNavTarget && activeNavTarget !== targetUrl) {
            try {
                activeNavAbortController.abort();
            } catch (_) {}
            activeNavAbortController = null;
        }

        currentNavSeq++;
        const thisSeq = currentNavSeq;
        activeNavTarget = targetUrl;

        const controller = new AbortController();
        activeNavAbortController = controller;

        const mergedInit = { ...init, signal: controller.signal };

        return originalFetch.call(this, input, mergedInit)
            .then((response) => {
                if (thisSeq !== currentNavSeq) {
                    throw new DOMException('Navigation superseded by newer click', 'AbortError');
                }

                // Cache the newly fetched page for future 0ms instant swaps
                if (response.ok) {
                    const cloned = response.clone();
                    cloned.text().then((html) => {
                        pageCache.set(targetUrl, {
                            html: html,
                            url: response.url || fullUrl,
                            timestamp: Date.now()
                        });
                    }).catch(() => {});
                }

                return response;
            })
            .catch((error) => {
                if (error.name === 'AbortError' || thisSeq !== currentNavSeq) {
                    throw error;
                }
                clearProgress();
                throw error;
            });
    };

    // ── Navigation Lifecycle Listeners ──
    document.addEventListener('livewire:navigate', (e) => {
        const target = normalizePath(e.detail.url);
        const hasCache = pageCache.has(target) && (Date.now() - (pageCache.get(target).timestamp || 0) < CACHE_TTL);

        if (!hasCache) {
            // Uncached: show visual progress bar
            restartProgressBar();

            clearTimeout(navWatchdogTimer);
            navWatchdogTimer = setTimeout(() => {
                clearProgress();
                activeNavAbortController = null;
                activeNavTarget = null;
            }, 5000);
        } else {
            // Cached: suppress progress bar entirely for 0ms transition
            clearProgress();
        }
    });

    document.addEventListener('livewire:navigated', () => {
        clearTimeout(navWatchdogTimer);
        completeProgressBar();
        activeNavAbortController = null;
        activeNavTarget = null;

        // Pre-warm other links in the background during idle moments
        scheduleIdlePrewarm();
    });

    // ── Idle Pre-Warming (Pre-fetch adjacent links in background) ──
    let idlePrewarmTimer = null;
    function scheduleIdlePrewarm() {
        clearTimeout(idlePrewarmTimer);
        idlePrewarmTimer = setTimeout(() => {
            if ('requestIdleCallback' in window) {
                window.requestIdleCallback(prewarmSidebarLinks);
            } else {
                setTimeout(prewarmSidebarLinks, 1000);
            }
        }, 1200);
    }

    function prewarmSidebarLinks() {
        const links = Array.from(document.querySelectorAll('#main-sidebar a[wire\\:navigate], a[wire\\:navigate]'));
        let queue = links.filter((link) => {
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return false;
            try {
                const u = new URL(link.href, window.location.origin);
                if (u.origin !== window.location.origin) return false;
                const path = u.pathname + u.search;
                return !pageCache.has(path);
            } catch (_) {
                return false;
            }
        });

        // Warm up at most 3 primary links per idle cycle
        queue = queue.slice(0, 3);

        queue.forEach((link, idx) => {
            setTimeout(() => {
                const url = link.href;
                const targetPath = normalizePath(url);
                if (pageCache.has(targetPath)) return;

                originalFetch(url, {
                    headers: { 'X-Livewire-Navigate': '' }
                }).then((res) => {
                    if (res.ok) {
                        return res.text().then((html) => {
                            pageCache.set(targetPath, {
                                html: html,
                                url: res.url || url,
                                timestamp: Date.now()
                            });
                        });
                    }
                }).catch(() => {});
            }, idx * 300);
        });
    }

    // ── Context Invalidation Handlers ──
    window.addEventListener('branch-switched', () => pageCache.clear());
    window.addEventListener('branchContextUpdated', () => pageCache.clear());
    window.addEventListener('accessibility-config-updated', () => pageCache.clear());

    // ── Clean Error & Abort Suppression ──
    window.addEventListener('unhandledrejection', (event) => {
        const reason = event.reason;
        const msg = (reason && (reason.message || reason.stack || String(reason))) || '';
        const isAbort = (reason && reason.name === 'AbortError') || msg.includes('AbortError') || msg.includes('superseded');
        const isPrefetchBug = msg.includes("setting 'html'") || msg.includes('storeThePrefetchedHtmlForWhenALinkIsClicked');

        if (isAbort || isPrefetchBug) {
            event.preventDefault();
            if (isPrefetchBug) {
                clearProgress();
            }
        }
    });
})();