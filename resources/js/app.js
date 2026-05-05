import './bootstrap';
import Alpine from 'alpinejs';

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

// Shared Tab Logic Factory (Globalized for Blade template access)
window.createTabComponent = function (initialValues = {}, tabProperties = []) {
    const props = Array.isArray(tabProperties) ? tabProperties : [tabProperties];
    const state = { indicatorWidth: 0, indicatorLeft: 0 };

    props.forEach(p => {
        state[p] = initialValues[p] || (typeof initialValues === 'string' ? initialValues : 'active');
        state[`${p}Width`] = 0;
        state[`${p}Left`] = 0;
    });

    return {
        ...state,
        init() {
            // Initial render of indicators
            this.$nextTick(() => props.forEach(p => this.updateIndicator(p)));

            // Watch for changes and sync to Livewire
            props.forEach(p => {
                this.$watch(p, (val) => {
                    this.updateIndicator(p);
                    this.syncToLivewire(p, val);
                });
            });

            // Deduplicate listeners if Livewire re-initializes the component
            if (this.$el && this.$el._hasTabListener) return;
            if (this.$el) this.$el._hasTabListener = true;

            // Handle global refreshes (resize/navigation)
            const onRefresh = () => {
                // Self-cleanup for inline x-data if $cleanup isn't available
                if (this.$el && !document.contains(this.$el)) {
                    window.removeEventListener(UI_REFRESH_EVENT, onRefresh);
                    return;
                }
                props.forEach(p => this.updateIndicator(p));
            };
            window.addEventListener(UI_REFRESH_EVENT, onRefresh);

            // Cleanup to prevent memory leaks via Alpine magic if available
            if (this.$cleanup) this.$cleanup(() => window.removeEventListener(UI_REFRESH_EVENT, onRefresh));
        },

        syncToLivewire(prop, value) {
            const el = this.$el ? this.$el.closest('[wire\\:id]') : null;
            if (!window.Livewire || !el) return;
            const component = window.Livewire.find(el.getAttribute('wire:id'));
            if (component) {
                try {
                    if (component.get(prop) !== value) {
                        component.set(prop, value);
                    }
                } catch (e) {
                    // Prop might not exist on component, safely ignore
                }
            }
        },

        updateIndicator(p) {
            const active = this[p];
            if (!active) return;

            this.$nextTick(() => {
                const list = this.$refs[`${p}List`] || this.$refs.tabList;
                if (!list) return;
                const el = list.querySelector(`[data-tab='${active}'], [data-panel='${active}']`);

                if (el) {
                    this[`${p}Width`] = el.offsetWidth;
                    this[`${p}Left`] = el.offsetLeft;
                    if (p === props[0]) {
                        this.indicatorWidth = el.offsetWidth;
                        this.indicatorLeft = el.offsetLeft;
                    }
                }
            });
        }
    };
};

// Component Registrations
window.slidingTabs = window.createTabComponent;
Alpine.data('slidingTabs', (val, props) => window.createTabComponent(val, props));

// Start Alpine
if (!window.AlpineStarted) {
    window.Alpine = Alpine;
    Alpine.start();
    window.AlpineStarted = true;
}
