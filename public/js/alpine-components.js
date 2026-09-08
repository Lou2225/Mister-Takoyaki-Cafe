(function() {
    // Sliding Tabs Logic
    const slidingTabsLogic = (initialValue, propertyName = 'tab') => {
        const props = Array.isArray(propertyName) ? propertyName : [propertyName];
        const state = {};

        props.forEach(p => {
            const isObject = initialValue !== null && typeof initialValue === 'object';
            state[p] = (isObject && p in initialValue) ? initialValue[p] : initialValue;
            state[`${p}Width`] = 0;
            state[`${p}Left`] = 0;
        });

        return {
            ...state,
            init() {
                setTimeout(() => props.forEach(p => this.updateIndicator(p)), 50);
                setTimeout(() => props.forEach(p => this.updateIndicator(p)), 300);

                props.forEach(p => {
                    this.$watch(p, (val) => {
                        this.updateIndicator(p);
                        this.syncToLivewire(p, val);
                    });
                });

                const onRefresh = () => props.forEach(p => this.updateIndicator(p));
                window.addEventListener('resize', onRefresh);
                window.addEventListener('app:refresh-ui', onRefresh);
                document.addEventListener('livewire:navigated', onRefresh);

                this.$el.addEventListener('alpine:destroy', () => {
                    window.removeEventListener('resize', onRefresh);
                    window.removeEventListener('app:refresh-ui', onRefresh);
                    document.removeEventListener('livewire:navigated', onRefresh);
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

    window.slidingTabs = slidingTabsLogic;

    // Modal Component
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
                const closeForNavigation = () => {
                    this.isModalOpen = false;
                    document.body.classList.remove('overflow-y-hidden');
                };
                document.addEventListener('livewire:navigating', closeForNavigation);
                this.$el.addEventListener('alpine:destroy', () => {
                    document.removeEventListener('livewire:navigating', closeForNavigation);
                }, { once: true });
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

    // Side Panel Component
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

    const registerAlpineFactories = () => {
        if (!window.Alpine) return;

        window.Alpine.data('slidingTabs', slidingTabsLogic);
        window.Alpine.data('modal', ({ name, show }) => window.modal({ name, show }));
        window.Alpine.data('sidePanel', ({ name, show }) => window.sidePanel({ name, show }));
    };

    document.addEventListener('alpine:init', registerAlpineFactories);
    document.addEventListener('livewire:navigated', registerAlpineFactories);
    registerAlpineFactories();
})();
