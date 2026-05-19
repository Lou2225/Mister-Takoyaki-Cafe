import '../css/app.css';
import './bootstrap';
import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;/**
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
    if (url) {
        // Small delay to allow Livewire to finish DOM morphing/clearing state
        setTimeout(() => {
            window.open(url, '_blank', 'width=450,height=650');
        }, 300);
    }
});


