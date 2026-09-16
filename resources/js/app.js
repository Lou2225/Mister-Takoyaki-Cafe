import '../css/app.css';
import './bootstrap';
import ApexCharts from 'apexcharts';

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

// Scroll to error
if (!window.__mtcScrollErrorListenerAttached) {
    window.__mtcScrollErrorListenerAttached = true;
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
}

// Print Page
if (!window.__mtcPrintPageListenerAttached) {
    window.__mtcPrintPageListenerAttached = true;
    window.addEventListener('print-page', () => {
        window.print();
    });
}

import thermalBluetoothPrinter from './thermal-bluetooth';
window.thermalBluetoothPrinter = thermalBluetoothPrinter;
window.dispatchEvent(new CustomEvent('thermal-bt-client-ready'));

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

// Thermal Tear-off Modal component
if (!window.thermalTearOffModal) {
    window.thermalTearOffModal = function() {
        return {
            show: false,
            sectionType: '',
            secondsLeft: 0,
            countdownTimer: null,
            get label() {
                return this.sectionType === 'barista' ? 'Barista Slip' : 'Kitchen Slip';
            },
            startCountdown(timeoutMs) {
                this.secondsLeft = Math.ceil((timeoutMs || 15000) / 1000);
                this.secondsLeft = Math.ceil((timeoutMs || 5000) / 1000);
                if (this.countdownTimer) clearInterval(this.countdownTimer);
                this.countdownTimer = setInterval(() => {
                    this.secondsLeft = Math.max(0, this.secondsLeft - 1);
                    if (this.secondsLeft <= 0 && this.countdownTimer) {
                        clearInterval(this.countdownTimer);
                        this.countdownTimer = null;
                    }
                }, 1000);
            },
            init() {
                const onPrintWaiting = (e) => {
                    this.sectionType = (e && e.detail && e.detail.sectionType) ? e.detail.sectionType : '';
                    this.startCountdown(e && e.detail ? e.detail.timeoutMs : 15000);
                    this.startCountdown(e && e.detail && e.detail.timeoutMs ? e.detail.timeoutMs : 5000);
                    this.show = true;
                };
                const onPrintResumed = () => {
                    this.show = false;
                    if (this.countdownTimer) {
                        clearInterval(this.countdownTimer);
                        this.countdownTimer = null;
                    }
                };
                window.addEventListener('thermal-print-waiting', onPrintWaiting);
                window.addEventListener('thermal-print-resumed', onPrintResumed);
                if (typeof this.$cleanup === 'function') {
                    this.$cleanup(() => {
                        window.removeEventListener('thermal-print-waiting', onPrintWaiting);
                        window.removeEventListener('thermal-print-resumed', onPrintResumed);
                        if (this.countdownTimer) {
                            clearInterval(this.countdownTimer);
                            this.countdownTimer = null;
                        }
                    });
                }
            }
        };
    };

    const registerTearOff = () => {
        if (window.Alpine) {
            window.Alpine.data('thermalTearOffModal', () => window.thermalTearOffModal());
        }
    };
    document.addEventListener('alpine:init', registerTearOff);
    registerTearOff();
}



