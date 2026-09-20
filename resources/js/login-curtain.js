const COLOR = '#0c0a09';
const CURTAIN_IN_DELAY = 120;  // start fading in almost immediately
const CURTAIN_IN_MS = 220;     // fully opaque at about 340ms
const SPLIT_MS = 350;          // navigate the instant the curtain is opaque
const MIN_HOLD_MS = 0;         // no forced black hold
const MAX_WAIT_MS = 3000;      // watchdog
const FADE_OUT_MS = 260;
const QUIET_MS = 60;           // brief settle check before revealing
const PREFETCH_MAX_MS = 10000;
const NAVIGATE_MAX_MS = 10000;

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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
        let prefetchFailed = false;
        await Promise.race([
            fetch(url, {
                headers: { 'X-Livewire-Navigate': '' },
                credentials: 'same-origin',
            }).then((r) => r.text()).catch(() => { prefetchFailed = true; }),
            sleep(PREFETCH_MAX_MS),
        ]);

        if (prefetchFailed) {
            // Network error: hand control back to the login screen
            window.dispatchEvent(new CustomEvent('login-transition-failed'));
            return;
        }
        await sleep(50); // let the navigation guard finish writing its cache entry
        log('dashboard prefetched');

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

        // Hold black until the dashboard data has populated
        await Promise.race([
            Promise.all([sleep(MIN_HOLD_MS), waitForQuiet(), waitForPageSignal()]),
            sleep(MAX_WAIT_MS),
        ]);
        log('fading out');
        await fadeOutAndRemove(curtain);
    } finally {
        if (curtain) curtain.remove();
        running = false;
    }
}

window.mtcLoginCurtain = { run };