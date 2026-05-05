(function () {
    'use strict';

    // Prevent multiple initializations
    if (window.FormFiltersInitialized) return;
    window.FormFiltersInitialized = true;

    // ── Character filter sets ──────────────────────────────────────
    const FILTERS = {
        name: {
            pattern: /^[\p{L}\s\-\.']+$/u,
            allowedKeys: ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', 'Tab', 'Enter', 'Shift', 'Control', 'Alt', 'Meta', 'CapsLock'],
            sanitizePaste: (text) => text.replace(/[^\p{L}\s\-\.']/gu, '').replace(/\s+/g, ' '),
            extraValidate: (e, el) => {
                const pos = el.selectionStart;
                if (pos === 0 && (e.key === ' ' || e.key === '-' || e.key === '.')) { e.preventDefault(); return false; }
                if ((e.key === ' ' && el.value[pos - 1] === ' ') || (e.key === '-' && el.value[pos - 1] === '-')) { e.preventDefault(); return false; }
                return true;
            }
        },
        nameStrict: {
            pattern: /^[\p{L}\s\-\.']+$/u,
            allowedKeys: ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', 'Tab', 'Enter', 'Shift', 'Control', 'Alt', 'Meta', 'CapsLock'],
            sanitizePaste: (text) => text.replace(/[^\p{L}\s\-\.']/gu, '').replace(/\s+/g, ' '),
            extraValidate: (e, el) => {
                const pos = el.selectionStart;
                if (pos === 0 && (e.key === ' ' || e.key === '-' || e.key === '.')) { e.preventDefault(); return false; }
                // Prevents 4+ repeating characters
                if (e.key && e.key.length === 1 && !FILTERS.name.allowedKeys.includes(e.key)) {
                    const val = el.value;
                    if (pos >= 3 && val[pos-1] === e.key && val[pos-2] === e.key && val[pos-3] === e.key) {
                        e.preventDefault();
                        return false;
                    }
                }
                return true;
            }
        },
        number: {
            pattern: /^[0-9]+$/,
            allowedKeys: ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'],
            sanitizePaste: (text) => text.replace(/[^0-9]/g, ''),
        },
        price: {
            pattern: /^[0-9.]+$/,
            allowedKeys: ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'],
            sanitizePaste: (text) => {
                const parts = text.replace(/[^0-9.]/g, '').split('.');
                return parts.length > 2 ? parts[0] + '.' + parts.slice(1).join('') : parts.join('.');
            },
            extraValidate: (e, el) => {
                if (e.key === '.' && el.value.includes('.')) { e.preventDefault(); return false; }
                if (el.value.includes('.')) {
                    const dec = el.value.split('.')[1] || '';
                    if (el.selectionStart > el.value.indexOf('.') && dec.length >= 2 && /[0-9]/.test(e.key)) { e.preventDefault(); return false; }
                }
                return true;
            }
        },
        phone: {
            pattern: /^[+0-9]+$/,
            allowedKeys: ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'],
            sanitizePaste: (text) => text.replace(/[^+0-9]/g, ''),
            extraValidate: (e, el) => { if (e.key === '+' && el.selectionStart !== 0) { e.preventDefault(); return false; } return true; }
        },
        email: {
            pattern: /^[a-zA-Z0-9._\-@]+$/,
            allowedKeys: ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'],
            sanitizePaste: (text) => text.replace(/[^a-zA-Z0-9._\-@]/g, '').toLowerCase(),
        },
        date: {
            pattern: /^[0-9\-]+$/,
            allowedKeys: ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'],
            sanitizePaste: (text) => text.replace(/[^0-9\-]/g, ''),
        }
    };

    const flashError = (el) => {
        if (!el) return;
        el.style.transition = 'border-color 0.1s';
        el.style.borderColor = '#ef4444';
        setTimeout(() => el.style.borderColor = '', 300);
    };

    const handleKeydown = (e, type) => {
        const f = FILTERS[type];
        if (!f) return;
        if (!e.key || f.allowedKeys.includes(e.key) || e.ctrlKey || e.metaKey) return;
        if ((f.extraValidate && !f.extraValidate(e, e.target)) || (e.key.length === 1 && !f.pattern.test(e.key))) {
            e.preventDefault();
            flashError(e.target);
        }
    };

    const handlePaste = (e, type) => {
        const f = FILTERS[type];
        if (!f) return;
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text');
        const sanitized = f.sanitizePaste(text);
        if (sanitized) {
            const el = e.target;
            const start = el.selectionStart;
            el.value = el.value.slice(0, start) + sanitized + el.value.slice(el.selectionEnd);
            el.selectionStart = el.selectionEnd = start + sanitized.length;
            el.dispatchEvent(new Event('input', { bubbles: true }));
        }
    };

    // ── Global FormFilters Object (Matches Implementation Guide) ──
    window.FormFilters = {
        nameKeydown: (e) => handleKeydown(e, 'name'),
        namePaste: (e) => handlePaste(e, 'name'),
        nameStrictKeydown: (e) => handleKeydown(e, 'nameStrict'),
        nameStrictPaste: (e) => handlePaste(e, 'nameStrict'),
        numberKeydown: (e) => handleKeydown(e, 'number'),
        numberPaste: (e) => handlePaste(e, 'number'),
        priceKeydown: (e) => handleKeydown(e, 'price'),
        pricePaste: (e) => handlePaste(e, 'price'),
        phoneKeydown: (e) => handleKeydown(e, 'phone'),
        phonePaste: (e) => handlePaste(e, 'phone'),
        emailKeydown: (e) => handleKeydown(e, 'email'),
        emailPaste: (e) => handlePaste(e, 'email'),
        dateKeydown: (e) => handleKeydown(e, 'date'),
        datePaste: (e) => handlePaste(e, 'date'),
    };

    const applyFilter = (el, key) => {
        const f = FILTERS[key];
        if (!f || el.dataset.filterInit) return;
        el.dataset.filterInit = 'true';

        el.addEventListener('keydown', (e) => handleKeydown(e, key));
        el.addEventListener('paste', (e) => handlePaste(e, key));
    };

    const init = () => document.querySelectorAll('[data-filter]').forEach(el => applyFilter(el, el.getAttribute('data-filter')));

    // Global APIs
    window.restrictInput = (e, key) => {
        const f = FILTERS[key];
        if (!f) return;
        const sanitized = f.sanitizePaste(e.target.value);
        if (e.target.value !== sanitized) {
            e.target.value = sanitized;
            e.target.dispatchEvent(new Event('input', { bubbles: true }));
        }
    };

    // Observers & Events
    init();
    const observer = new MutationObserver((mutations) => {
        if (mutations.some(m => m.addedNodes.length > 0)) init();
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });

    
    // Safety check for Livewire
    const setupLivewire = () => {
        if (window.Livewire) {
            Livewire.hook('message.processed', init);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupLivewire);
    } else {
        setupLivewire();
    }

    window.addEventListener('scroll-to-error', () => {
        setTimeout(() => {
            const err = document.querySelector('.text-red-500, .text-red-600, [aria-invalid="true"], .border-red-500');
            if (err) err.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    });
})();
