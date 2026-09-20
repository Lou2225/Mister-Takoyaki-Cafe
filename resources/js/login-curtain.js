const COLOR = '#0c0a09';
const CURTAIN_IN_DELAY = 150;  // ms after the column slide starts
const CURTAIN_IN_MS = 250;     // fade to black (fully opaque at ~400ms)
const SPLIT_MS = 400;          // navigate the moment the curtain is opaque
const HOLD_MAX_MS = 250;       // longest we stay black waiting for data (0 = don't wait)
const FADE_OUT_MS = 250;
const QUIET_MS = 60;           // no Livewire requests for this long = "settled"
const PREFETCH_MAX_MS = 10000; // spinner phase caps
const ASSETS_MAX_MS = 6000;
const NAVIGATE_MAX_MS = 10000;

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const twoFrames = () =>
    Promise.race([
        new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r))),
        sleep(80),
    ]);

let pending = 0;
let lastActivity = 0;
let pageSignal = false;
let running = false;
let hooked = false;

// Track in-flight Livewire requests so we know when the dashboard has settled
function hookLivewire() {
    if (hooked || !window.Livewire || typeof window.Livewire.hook !== 'function') return;
    hooked = true;
    window.Livewire.hook('commit', ({ succeed, fail }) => {
        pending++;
        lastActivity = performance.now();
        const done = () => {
            pending = Math.max(0, pending - 1);
            lastActivity = performance.now();
        };
        succeed(done);
        fail(done);
    });
}
if (window.Livewire) hookLivewire();
else document.addEventListener('livewire:init', hookLivewire);

// Optional: a page can dispatch this when its own heavy widgets (map, charts) are ready
window.addEventListener('mtc-page-ready', () => { pageSignal = true; });

function waitForQuiet() {
    return new Promise((resolve) => {
        lastActivity = performance.now();
        const tick = () => {
            if (pending === 0 && performance.now() - lastActivity >= QUIET_MS) return resolve();
            setTimeout(tick, 50);
        };
        requestAnimationFrame(() => requestAnimationFrame(tick));
    });
}

function waitForPageSignal() {
    // Only waits if the new page opts in with data-await-page-ready
    if (!document.querySelector('[data-await-page-ready]')) return Promise.resolve();
    return new Promise((resolve) => {
        const check = () => (pageSignal ? resolve() : setTimeout(check, 50));
        check();
    });
}

// Download the dashboard's extra head assets now, so the swap needs no network
function warmHeadAssets(html) {
    let doc;
    try {
        doc = new DOMParser().parseFromString(html, 'text/html');
    } catch (_) {
        return Promise.resolve();
    }

    const known = new Set(
        Array.from(document.querySelectorAll('script[src], link[href]')).map((n) => n.src || n.href)
    );
    const jobs = [];

    doc.head.querySelectorAll('script[src], link[rel="stylesheet"][href]').forEach((el) => {
        const isScript = el.tagName === 'SCRIPT';
        const url = new URL(el.getAttribute(isScript ? 'src' : 'href'), location.href).href;
        if (known.has(url)) return;
        known.add(url);

        jobs.push(new Promise((resolve) => {
            const l = document.createElement('link');
            if (isScript && el.type === 'module') {
                l.rel = 'modulepreload';
            } else {
                l.rel = 'preload';
                l.as = isScript ? 'script' : 'style';
            }
            l.href = url;
            if (el.crossOrigin) l.crossOrigin = el.crossOrigin;
            if (el.integrity) l.integrity = el.integrity;
            l.onload = l.onerror = () => resolve();
            document.head.appendChild(l);
        }));
    });

    return Promise.race([Promise.all(jobs), sleep(ASSETS_MAX_MS)]);
}

function fadeOutAndRemove(el) {
    return new Promise((resolve) => {
        const cleanup = () => { el.remove(); resolve(); };
        if (reduceMotion()) return cleanup();
        el.style.transition = `opacity ${FADE_OUT_MS}ms cubic-bezier(0.4, 0, 0.2, 1)`;
        el.style.pointerEvents = 'none';
        void el.offsetHeight;
        el.style.opacity = '0';
        el.addEventListener('transitionend', cleanup, { once: true });
        setTimeout(cleanup, FADE_OUT_MS + 150);
    });
}

async function run(url) {
    if (running) return;
    running = true;
    pageSignal = false;

    const t0 = performance.now();
    const log = (msg) => console.info(`[curtain] ${msg} @ ${Math.round(performance.now() - t0)}ms`);
    let curtain = null;

    try {
        // PHASE 1: login page stays visible, the button spinner covers this wait
        let html = null;
        let prefetchFailed = false;
        await Promise.race([
            fetch(url, {
                headers: { 'X-Livewire-Navigate': '' },
                credentials: 'same-origin',
            })
                .then((r) => r.text())
                .then((t) => { html = t; })
                .catch(() => { prefetchFailed = true; }),
            sleep(PREFETCH_MAX_MS),
        ]);

        if (prefetchFailed) {
            // Network error: hand control back to the login screen
            window.dispatchEvent(new CustomEvent('login-transition-failed'));
            return;
        }
        log('dashboard html ready');

        if (html) await warmHeadAssets(html);
        await sleep(50); // let the navigation guard finish writing its cache entry
        log('assets warmed');

        // PHASE 2: split the columns and fade to black
        // Attached to <html>, NOT <body>, so it survives Livewire's body swap
        curtain = document.createElement('div');
        curtain.id = 'mtc-login-curtain';
        curtain.setAttribute('aria-hidden', 'true');
        curtain.style.cssText =
            `position:fixed;inset:0;z-index:2147483000;background:${COLOR};opacity:0;` +
            `pointer-events:all;transition:opacity ${CURTAIN_IN_MS}ms ease-out;`;
        document.documentElement.appendChild(curtain);
        void curtain.offsetHeight;

        window.dispatchEvent(new CustomEvent('auth-split'));
        setTimeout(() => { curtain.style.opacity = '1'; }, CURTAIN_IN_DELAY);
        await sleep(SPLIT_MS);

        // PHASE 3: swap in the dashboard (cache hit, so near-instant)
        const navigated = new Promise((resolve) =>
            document.addEventListener('livewire:navigated', resolve, { once: true })
        );
        log('navigate() called');
        window.Livewire.navigate(url);

        const ok = await Promise.race([
            navigated.then(() => true),
            sleep(NAVIGATE_MAX_MS).then(() => false),
        ]);
        log(ok ? 'livewire:navigated' : 'navigation timed out');

        if (!ok) {
            window.dispatchEvent(new CustomEvent('login-transition-failed'));
            await fadeOutAndRemove(curtain);
            return;
        }

        // Brief, capped hold so the dashboard can paint, then reveal
        await twoFrames();
        if (HOLD_MAX_MS > 0) {
            await Promise.race([
                Promise.all([waitForQuiet(), waitForPageSignal()]),
                sleep(HOLD_MAX_MS),
            ]);
        }
        log('fading out');
        await fadeOutAndRemove(curtain);
    } finally {
        if (curtain) curtain.remove();
        running = false;
    }
}

window.mtcLoginCurtain = { run };