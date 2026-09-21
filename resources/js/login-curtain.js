const COLOR = '#0c0a09';

// Logo choreography (ms, measured from the moment the columns start splitting)
const LOGO_IN_AT = 300;        // logo starts appearing as the columns open
const LOGO_IN_MS = 380;        // fade in + settle
const RUSH_AT = 800;           // columns are gone, short beat, then the rush
const RUSH_MS = 750;           // logo flies past the camera
const RUSH_SCALE = 10;         // how far it zooms
const CURTAIN_IN_AT = 450;     // ms into the rush when black starts fading in
const CURTAIN_IN_MS = 300;     // fully black right as the rush ends

const HOLD_MAX_MS = 250;       // longest we stay black waiting for data (0 = don't wait)
const FADE_OUT_MS = 250;
const QUIET_MS = 60;           // no Livewire requests for this long = "settled"
const PREFETCH_MAX_MS = 10000; // spinner phase caps
const ASSETS_MAX_MS = 6000;
const NAVIGATE_MAX_MS = 10000;

const SOUND_ENABLED = true;    // set false to mute everything
const SOUND_VOLUME = 0.25;     // 0 to 1

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const twoFrames = () =>
    Promise.race([
        new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r))),
        sleep(80),
    ]);

// ── Sound (synthesized with Web Audio, no audio files needed) ──
let audioCtx = null;

function unlockAudio() {
    if (!SOUND_ENABLED) return;
    try {
        audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
        if (audioCtx.state === 'suspended') audioCtx.resume();
    } catch (_) {}
}

function audioReady() {
    return SOUND_ENABLED && audioCtx && audioCtx.state === 'running';
}

function noiseSource(seconds) {
    const len = Math.max(1, Math.floor(audioCtx.sampleRate * seconds));
    const buf = audioCtx.createBuffer(1, len, audioCtx.sampleRate);
    const data = buf.getChannelData(0);
    for (let i = 0; i < len; i++) data[i] = Math.random() * 2 - 1;
    const src = audioCtx.createBufferSource();
    src.buffer = buf;
    return src;
}

const sfx = {
    // Rising air sweep while the logo rushes toward you
    whoosh(ms) {
        if (!audioReady()) return;
        try {
            const t = audioCtx.currentTime;
            const d = ms / 1000;
            const src = noiseSource(d + 0.1);
            const filter = audioCtx.createBiquadFilter();
            filter.type = 'bandpass';
            filter.Q.value = 1.2;
            filter.frequency.setValueAtTime(250, t);
            filter.frequency.exponentialRampToValueAtTime(4200, t + d);
            const gain = audioCtx.createGain();
            gain.gain.setValueAtTime(0.0001, t);
            gain.gain.exponentialRampToValueAtTime(SOUND_VOLUME, t + d * 0.9);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + d + 0.06);
            src.connect(filter);
            filter.connect(gain);
            gain.connect(audioCtx.destination);
            src.start(t);
            src.stop(t + d + 0.1);
        } catch (_) {}
    },

    // Low thump the moment the screen hits black
    impact() {
        if (!audioReady()) return;
        try {
            const t = audioCtx.currentTime;
            const osc = audioCtx.createOscillator();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(90, t);
            osc.frequency.exponentialRampToValueAtTime(38, t + 0.28);
            const gain = audioCtx.createGain();
            gain.gain.setValueAtTime(SOUND_VOLUME * 1.2, t);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.35);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start(t);
            osc.stop(t + 0.4);
        } catch (_) {}
    },

    // Soft descending air sweep plus a light ping as the dashboard is revealed
    reveal() {
        if (!audioReady()) return;
        try {
            const t = audioCtx.currentTime;

            const src = noiseSource(0.5);
            const filter = audioCtx.createBiquadFilter();
            filter.type = 'bandpass';
            filter.Q.value = 0.9;
            filter.frequency.setValueAtTime(3200, t);
            filter.frequency.exponentialRampToValueAtTime(500, t + 0.45);
            const air = audioCtx.createGain();
            air.gain.setValueAtTime(0.0001, t);
            air.gain.exponentialRampToValueAtTime(SOUND_VOLUME * 0.6, t + 0.06);
            air.gain.exponentialRampToValueAtTime(0.0001, t + 0.48);
            src.connect(filter);
            filter.connect(air);
            air.connect(audioCtx.destination);
            src.start(t);
            src.stop(t + 0.5);

            const osc = audioCtx.createOscillator();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(1175, t + 0.1);
            const ping = audioCtx.createGain();
            ping.gain.setValueAtTime(0.0001, t + 0.1);
            ping.gain.exponentialRampToValueAtTime(SOUND_VOLUME * 0.5, t + 0.13);
            ping.gain.exponentialRampToValueAtTime(0.0001, t + 0.6);
            osc.connect(ping);
            ping.connect(audioCtx.destination);
            osc.start(t + 0.1);
            osc.stop(t + 0.65);
        } catch (_) {}
    },
};

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
            setTimeout(tick, 30);
        };
        tick();
    });
}

function waitForPageSignal() {
    // Only waits if the new page opts in with data-await-page-ready
    if (!document.querySelector('[data-await-page-ready]')) return Promise.resolve();
    return new Promise((resolve) => {
        const check = () => (pageSignal ? resolve() : setTimeout(check, 30));
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

// If the transition fails, put the logo back to its hidden state
function resetLogo() {
    const logo = document.getElementById('auth-logo');
    if (logo) logo.getAnimations().forEach((a) => a.cancel());
}

async function run(url) {
    if (running) return;
    running = true;
    pageSignal = false;

    const t0 = performance.now();
    const log = (msg) => console.info(`[curtain] ${msg} @ ${Math.round(performance.now() - t0)}ms`);
    let curtain = null;

    try {
        // PHASE 1: login page stays visible, the button spinner covers all of this
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
            window.dispatchEvent(new CustomEvent('login-transition-failed'));
            return;
        }
        log('dashboard html ready');

        if (html) await warmHeadAssets(html);
        await sleep(50); // let the navigation guard finish writing its cache entry
        log('assets warmed');

        // PHASE 2: columns split, the logo appears, then rushes past you into black
        // Curtain is attached to <html>, NOT <body>, so it survives Livewire's body swap
        curtain = document.createElement('div');
        curtain.id = 'mtc-login-curtain';
        curtain.setAttribute('aria-hidden', 'true');
        curtain.style.cssText =
            `position:fixed;inset:0;z-index:2147483000;background:${COLOR};opacity:0;` +
            `pointer-events:all;transition:opacity ${CURTAIN_IN_MS}ms ease-out;`;
        document.documentElement.appendChild(curtain);
        void curtain.offsetHeight;

        window.dispatchEvent(new CustomEvent('auth-split')); // columns slide apart (0.75s)

        const logo = document.getElementById('auth-logo');
        const still = 'translate(-50%, -50%)';

        // Entrance: fade in and settle as the columns open
        if (logo && !reduceMotion()) {
            logo.animate(
                [
                    { opacity: 0, transform: `${still} scale(0.9)` },
                    { opacity: 1, transform: `${still} scale(1)` },
                ],
                { duration: LOGO_IN_MS, delay: LOGO_IN_AT, easing: 'cubic-bezier(0.2, 0.7, 0.2, 1)', fill: 'both' }
            );
        }
        await sleep(RUSH_AT);

        // Rush: the logo flies toward the camera and past you
        if (logo && !reduceMotion()) {
            logo.animate(
                [
                    { opacity: 1, transform: `${still} scale(1)` },
                    { opacity: 1, transform: `${still} scale(${RUSH_SCALE})` },
                ],
                { duration: RUSH_MS, easing: 'cubic-bezier(0.55, 0, 0.9, 0.3)', fill: 'forwards' }
            );
        }
        sfx.whoosh(RUSH_MS);
        setTimeout(() => { curtain.style.opacity = '1'; }, CURTAIN_IN_AT);
        await sleep(RUSH_MS);
        sfx.impact();

        // PHASE 3: swap in the dashboard (cache hit + warmed assets = near-instant)
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
            resetLogo();
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
        sfx.reveal();
        await fadeOutAndRemove(curtain);
    } finally {
        if (curtain) curtain.remove();
        running = false;
    }
}

window.mtcLoginCurtain = { run, unlockAudio };