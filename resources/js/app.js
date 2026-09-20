import '../css/app.css';
import './bootstrap';
import ApexCharts from 'apexcharts';
import './login-curtain';

window.ApexCharts = ApexCharts;

if ('serviceWorker' in navigator && !window.__mtcSwRegistrationAttached) {
    window.__mtcSwRegistrationAttached = true;
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((error) => {
            console.warn('Service worker registration failed:', error);
        });
    }, { once: true });
}

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

if (!window.__mtcResizeListenerAttached) {
    window.__mtcResizeListenerAttached = true;
    window.addEventListener('resize', triggerRefresh);
}


/**
 * ──────────────────────────────────────────────────────────
 * GLOBAL EVENT LISTENERS
 * ──────────────────────────────────────────────────────────
 */


// Print Page
if (!window.__mtcPrintPageListenerAttached) {
    window.__mtcPrintPageListenerAttached = true;
    window.addEventListener('print-page', () => {
        window.print();
    });
}

import thermalBluetoothPrinter, { thermalWiredPrinter, getConnectedThermalPrinter } from './thermal-bluetooth';
window.thermalBluetoothPrinter = thermalBluetoothPrinter;
window.thermalWiredPrinter = thermalWiredPrinter;
window.getConnectedThermalPrinter = getConnectedThermalPrinter;
window.dispatchEvent(new CustomEvent('thermal-bt-client-ready'));

document.addEventListener('livewire:navigated', () => {
    window.dispatchEvent(new CustomEvent('thermal-printer-status-changed'));
});

// Bluetooth pairing must happen from a real user click (browser security
// requirement) — trigger this from a "Connect Printer" button in Settings.
if (!window.__mtcBluetoothListenerAttached) {
    window.__mtcBluetoothListenerAttached = true;
    window.addEventListener('connect-thermal-bluetooth', async () => {
    try {
        const name = await thermalBluetoothPrinter.connect();
        window.dispatchEvent(new CustomEvent('thermal-bt-connected', { detail: { name } }));
    } catch (error) {
        console.error('❌ Bluetooth connection failed:', error.message);
        window.dispatchEvent(new CustomEvent('thermal-bt-error', { detail: { message: error.message } }));
    }
    });
}

// Thermal Receipt Printing (Automatic - no user interaction)
if (!window.__thermalPrintListenerAttached) {
    window.__thermalPrintListenerAttached = true;
    window.addEventListener('send-thermal-print', async (e) => {
    const orderId = e.detail?.order_id || e.detail?.orderId || e.detail;
    const receiptType = e.detail?.receipt_type || 'all';

    if (!orderId) return;

    // ── 1. Bluetooth printer (browser-paired) ─────────────────────────────
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
            console.error('❌ Bluetooth print failed, trying wired:', error.message);
        }
    }

    // ── 2. Wired printer (Print Bridge + Windows Print Spooler) ───────────
    const wiredReady = window.thermalWiredPrinter?.printerName;

    if (wiredReady) {
        if (window.pendingThermalReceiptWindow && !window.pendingThermalReceiptWindow.closed) {
            try { window.pendingThermalReceiptWindow.close(); } catch (_) {}
            window.pendingThermalReceiptWindow = null;
        }

        try {
            const res = await fetch(`/pos/orders/${orderId}/receipt-data`);
            if (!res.ok) throw new Error('Could not load receipt data.');
            const data = await res.json();
            await window.thermalWiredPrinter.printReceipt(data.order, data.settings, data.receipts || []);
            console.log('✅ Receipt printed via Wired bridge', { orderId, receiptType });
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { type: 'success', message: 'Receipt printed via wired printer.' }
            }));
            return;
        } catch (error) {
            console.error('❌ Wired print failed, falling back to popup:', error.message);
        }
    }

    // ── 3. Popup fallback (browser print dialog) ──────────────────────────
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





