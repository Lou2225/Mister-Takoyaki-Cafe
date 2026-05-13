import '../css/app.css';
import './bootstrap';

/**
 * ──────────────────────────────────────────────────────────
 * GLOBAL BOOTSTRAP
 * ──────────────────────────────────────────────────────────
 */

// Global Event Bus for UI refreshes
const UI_REFRESH_EVENT = 'app:refresh-ui';
let refreshTimeout = null;

const triggerRefresh = () => {
    if (!refreshTimeout) {
        refreshTimeout = requestAnimationFrame(() => {
            window.dispatchEvent(new CustomEvent(UI_REFRESH_EVENT));
            refreshTimeout = null;
        });
    }
};

window.addEventListener('resize', triggerRefresh);

/**
 * ──────────────────────────────────────────────────────────
 * GLOBAL EVENT LISTENERS
 * ──────────────────────────────────────────────────────────
 */

// Scroll to error
window.addEventListener('scroll-to-error', () => {
    setTimeout(() => {
        const errorEl = document.querySelector('p.text-sm.text-red-600:not(:empty), .text-red-500:not(:empty)');
        if (errorEl) {
            const container = document.querySelector('main.overflow-y-auto') || document.documentElement;
            const top = errorEl.getBoundingClientRect().top - (container.getBoundingClientRect().top || 0) + container.scrollTop - 120;
            container.scrollTo({ top, behavior: 'smooth' });
        }
    }, 150);
});

// Print Page
window.addEventListener('print-page', () => {
    window.print();
});

// Receipt Pop-up
window.addEventListener('open-receipt', (e) => {
    const url = e.detail?.url || e.detail;
    if (url) window.open(url, '_blank', 'width=450,height=650');
});

/**
 * ──────────────────────────────────────────────────────────
 * ALPINE COMPONENTS & FACTORIES
 * ──────────────────────────────────────────────────────────
 */

// Consolidated Sliding Tabs Logic
const slidingTabsLogic = (initialValue, propertyName = 'tab') => {
    const props = Array.isArray(propertyName) ? propertyName : [propertyName];
    const state = {};

    props.forEach(p => {
        // If initialValue is an object containing the property, use it. 
        // Otherwise, use the initialValue itself (handles both static values and entangled proxies).
        const isObject = initialValue !== null && typeof initialValue === 'object';
        state[p] = (isObject && p in initialValue) ? initialValue[p] : initialValue;
        state[`${p}Width`] = 0;
        state[`${p}Left`] = 0;
    });

    return {
        ...state,
        init() {
            // Initial sync with multiple attempts for layout stability
            setTimeout(() => props.forEach(p => this.updateIndicator(p)), 50);
            setTimeout(() => props.forEach(p => this.updateIndicator(p)), 300);

            // Watchers for automatic updates
            props.forEach(p => {
                this.$watch(p, (val) => {
                    this.updateIndicator(p);
                    this.syncToLivewire(p, val);
                });
            });

            // Layout & Lifecycle listeners
            const onRefresh = () => props.forEach(p => this.updateIndicator(p));
            window.addEventListener('resize', onRefresh);
            window.addEventListener('app:refresh-ui', onRefresh);
            document.addEventListener('livewire:navigated', onRefresh);
            document.addEventListener('livewire:update', onRefresh);

            this.$el.addEventListener('alpine:destroy', () => {
                window.removeEventListener('resize', onRefresh);
                window.removeEventListener('app:refresh-ui', onRefresh);
                document.removeEventListener('livewire:navigated', onRefresh);
                document.removeEventListener('livewire:update', onRefresh);
            });
        },

        syncToLivewire(prop, value) {
            const el = this.$el.closest('[wire\\:id]');
            if (!window.Livewire || !el) return;
            const component = window.Livewire.find(el.getAttribute('wire:id'));
            if (component && component.get(prop) !== value) {
                try { component.set(prop, value); } catch (e) { }
            }
        },

        updateIndicator(p) {
            const active = this[p];
            if (!active) return;

            requestAnimationFrame(() => {
                const list = this.$refs[`${p}List`] || this.$refs.tabList || this.$el.querySelector(`[x-ref="${p}List"], [x-ref="tabList"]`);
                if (!list) return;

                const el = list.querySelector(`[data-tab='${active}'], [data-panel='${active}'], [value='${active}']`);

                if (el && el.offsetWidth > 0) {
                    this[`${p}Width`] = el.offsetWidth;
                    this[`${p}Left`] = el.offsetLeft;
                } else if (el) {
                    // Retry once if element is found but has no width (e.g. during transitions)
                    setTimeout(() => {
                        if (el.offsetWidth > 0) {
                            this[`${p}Width`] = el.offsetWidth;
                            this[`${p}Left`] = el.offsetLeft;
                        }
                    }, 150);
                }
            });
        }
    };
};

// ── Modal Component (global factory for x-data="modal(...)") ──
window.modal = function({ name, show }) {
    return {
        isModalOpen: show,
        name: name,
        focusables() {
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])';
            return [...this.$el.querySelectorAll(selector)].filter(el => !el.hasAttribute('disabled'));
        },
        firstFocusable()     { return this.focusables()[0]; },
        lastFocusable()      { return this.focusables().slice(-1)[0]; },
        nextFocusable()      { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable(); },
        prevFocusable()      { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable(); },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1); },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) - 1; },
        init() {
            this.$watch('isModalOpen', value => {
                if (value) {
                    document.body.classList.add('overflow-y-hidden');
                    if (this.$el.hasAttribute('focusable')) {
                        setTimeout(() => this.firstFocusable()?.focus(), 100);
                    }
                } else {
                    document.body.classList.remove('overflow-y-hidden');
                }
            });
        },
        open(detail) {
            const target = typeof detail === 'string' ? detail : (detail?.name || detail?.[0]?.name || detail?.[0]);
            if (target === this.name) this.isModalOpen = true;
        },
        close(detail) {
            const target = typeof detail === 'string' ? detail : (detail?.name || detail?.[0]?.name || detail?.[0]);
            if (target === this.name) this.isModalOpen = false;
        }
    };
};

// ── Side Panel Component (global factory for x-data="sidePanel(...)") ──
window.sidePanel = function({ name, show }) {
    return {
        isPanelOpen: show,
        name: name,
        init() {
            this.$watch('isPanelOpen', value => {
                document.body.classList.toggle('overflow-y-hidden', value);
            });
            document.addEventListener('livewire:navigating', () => {
                document.body.classList.remove('overflow-y-hidden');
                this.isPanelOpen = false;
            });
        },
        open(detail) {
            const target = typeof detail === 'string' ? detail : (detail?.name || detail?.[0]?.name || detail?.[0]);
            if (target === this.name) this.isPanelOpen = true;
        },
        close(detail) {
            const target = typeof detail === 'string' ? detail : (detail?.name || detail?.[0]?.name || detail?.[0]);
            if (target === this.name) this.isPanelOpen = false;
        },
        closePanel() {
            this.isPanelOpen = false;
        }
    };
};

document.addEventListener('alpine:init', () => {
    window.Alpine?.data('slidingTabs', slidingTabsLogic);
    // Also register modal/sidePanel via Alpine.data for completeness
    if (window.Alpine) {
        window.Alpine.data('modal', ({ name, show }) => window.modal({ name, show }));
        window.Alpine.data('sidePanel', ({ name, show }) => window.sidePanel({ name, show }));
    }
});

// Global alias for compatibility
window.slidingTabs = slidingTabsLogic;
