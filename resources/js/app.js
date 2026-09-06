import '../css/app.css';
import './bootstrap';
import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

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

import thermalBluetoothPrinter from './thermal-bluetooth';
window.thermalBluetoothPrinter = thermalBluetoothPrinter;
window.dispatchEvent(new CustomEvent('thermal-bt-client-ready'));

// Bluetooth pairing must happen from a real user click (browser security
// requirement) — trigger this from a "Connect Printer" button in Settings.
window.addEventListener('connect-thermal-bluetooth', async () => {
    try {
        const name = await thermalBluetoothPrinter.connect();
        window.dispatchEvent(new CustomEvent('thermal-bt-connected', { detail: { name } }));
    } catch (error) {
        console.error('❌ Bluetooth connection failed:', error.message);
        window.dispatchEvent(new CustomEvent('thermal-bt-error', { detail: { message: error.message } }));
    }
});

// Thermal Receipt Printing (Automatic - no user interaction)
if (!window.__thermalPrintListenerAttached) {
    window.__thermalPrintListenerAttached = true;
    window.addEventListener('send-thermal-print', async (e) => {
    const orderId = e.detail?.order_id || e.detail?.orderId || e.detail;
    const receiptType = e.detail?.receipt_type || 'all';

    if (!orderId) return;

    // Prefer the browser-side Bluetooth printer when one is connected
    const btConnected = window.thermalBluetoothPrinter && window.thermalBluetoothPrinter.characteristic;

    if (btConnected) {
        // If a pending popup window was prepared on POS, close it since BT is handling it
        if (window.pendingThermalReceiptWindow && !window.pendingThermalReceiptWindow.closed) {
            try {
                window.pendingThermalReceiptWindow.close();
            } catch (_) {}
            window.pendingThermalReceiptWindow = null;
        }

        try {
            const res = await fetch(`/pos/orders/${orderId}/receipt-data`);
            if (!res.ok) throw new Error('Could not load receipt data.');
            const data = await res.json();
            await window.thermalBluetoothPrinter.printReceipt(data.order, data.settings, data.receipts || []);
            console.log('✅ Receipt printed via Bluetooth', { orderId, receiptType });
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { type: 'success', message: 'Receipt printed via Bluetooth.' }
            }));
            return;
        } catch (error) {
            console.error('❌ Bluetooth print failed, falling back to popup:', error.message);
        }
    }

    // Fallback: Open browser print window
    try {
        const receiptUrl = `/receipts/${orderId}/thermal?autoprint=1`;
        const pendingWindow = window.pendingThermalReceiptWindow;
        const printWindow = pendingWindow && !pendingWindow.closed
            ? pendingWindow
            : window.open(receiptUrl, 'thermal_receipt_' + orderId, 'width=450,height=650,menubar=no,toolbar=no,location=no,status=no');

        window.pendingThermalReceiptWindow = null;

        if (pendingWindow && printWindow) {
            printWindow.name = 'thermal_receipt_' + orderId;
            printWindow.location.replace(receiptUrl);
            printWindow.focus();
        }

        if (!printWindow || printWindow.closed || typeof printWindow.closed === 'undefined') {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { type: 'info', message: 'Order placed! Popup was blocked — please allow popups to auto-open receipt.' }
            }));
        }
    } catch (popupErr) {
        console.warn('Fallback popup could not be opened:', popupErr);
    }
    });
}

// Receipt Pop-up (Legacy - kept for manual receipt viewing)
window.addEventListener('open-receipt', (e) => {
    const url = e.detail?.url || e.detail;
    if (url) {
        // Small delay to allow Livewire to finish DOM morphing/clearing state
        setTimeout(() => {
            window.open(url, '_blank', 'width=450,height=650');
        }, 300);
    }
});


