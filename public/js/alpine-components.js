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

    // Option Library Management Component
    window.optionLibraryManagement = function($wire, allIngredients) {
        return {
            panel: $wire.entangle('panel').live,
            mode: $wire.entangle('mode').live,
            tableView: $wire.entangle('view').live,
            editTemplateId: $wire.entangle('editTemplateId'),
            templateName: $wire.entangle('name'),
            priceMode: $wire.entangle('priceMode'),
            isRequired: $wire.entangle('isRequired'),
            noRecipeRequired: $wire.entangle('noRecipeRequired'),
            templateItems: $wire.entangle('templateItems'),
            deleteTargetId: $wire.entangle('deleteTargetId'),
            deleteTargetName: $wire.entangle('deleteTargetName'),
            priceModeFilter: '',
            searchQuery: '',
            currentPage: 1,
            perPage: 5,
            templatesList: [],
            ingredientsList: allIngredients || [],
            formErrors: {},
            formSubmitted: false,
            isSaving: false,

            get filteredTemplateIds() {
                const query = (this.searchQuery || '').toLowerCase().trim();
                const filter = this.priceModeFilter;
                return (this.templatesList || [])
                    .filter(t => {
                        const matchesSearch = !query || 
                            (t.name || '').toLowerCase().includes(query) || 
                            (t.items && t.items.some(item => (item.name || '').toLowerCase().includes(query)));
                        const matchesMode = !filter || t.price_mode === filter;
                        return matchesSearch && matchesMode;
                    })
                    .map(t => t.id);
            },
            get paginatedTemplateIds() {
                const start = (this.currentPage - 1) * this.perPage;
                return this.filteredTemplateIds.slice(start, start + this.perPage);
            },
            get pageNumbers() {
                const totalPages = Math.ceil(this.filteredTemplateIds.length / this.perPage) || 1;
                const start = Math.max(1, this.currentPage - 1);
                const end = Math.min(totalPages, this.currentPage + 1);
                const pages = [];
                for (let i = start; i <= end; i++) {
                    pages.push(i);
                }
                return pages;
            },
            isItemVisible(id) {
                return this.paginatedTemplateIds.includes(id);
            },
            updateTemplatesList(newList) {
                this.templatesList = newList || [];
            },

            // ── Instant 0ms Actions ──
            createTemplate() {
                this.editTemplateId = null;
                this.templateName = '';
                this.priceMode = 'additive';
                this.isRequired = false;
                this.noRecipeRequired = false;
                this.templateItems = [
                    { id: null, name: '', price: '', is_default: true, ingredients: [] }
                ];
                this.formErrors = {};
                this.formSubmitted = false;
                this.panel = 'form';
                this.mode = 'create';
                this.$wire.showCreate();
            },
            editTemplate(id) {
                const tmpl = (this.templatesList || []).find(t => Number(t.id) === Number(id));
                if (tmpl) {
                    this.editTemplateId = tmpl.id;
                    this.templateName = tmpl.name;
                    this.priceMode = tmpl.price_mode;
                    this.isRequired = tmpl.is_required;
                    this.noRecipeRequired = tmpl.no_recipe_required;
                    this.templateItems = JSON.parse(JSON.stringify(tmpl.items || []));
                    this.formErrors = {};
                    this.formSubmitted = false;
                    this.panel = 'form';
                    this.mode = 'edit';
                }
                this.$wire.showEdit(id);
            },
            backToList() {
                this.panel = 'list';
                this.mode = 'list';
                this.formErrors = {};
                this.formSubmitted = false;
                this.$wire.backToList();
            },
            confirmDelete(id, name) {
                this.deleteTargetId = id;
                this.deleteTargetName = name;
                this.$wire.deleteTargetId = id;
                this.$wire.deleteTargetName = name;
                this.$dispatch('open-modal', 'delete-template');
            },
            addOption() {
                if (!this.templateItems) this.templateItems = [];
                this.templateItems.push({
                    id: null,
                    name: '',
                    price: '',
                    is_default: this.templateItems.length === 0,
                    ingredients: []
                });
                if (this.formErrors) delete this.formErrors.templateItems;
            },
            removeOption(idx) {
                if (this.templateItems) {
                    this.templateItems.splice(idx, 1);
                }
            },
            toggleDefault(idx) {
                if (!this.templateItems || !this.templateItems[idx]) return;
                const isCurrent = !!this.templateItems[idx].is_default;
                this.templateItems.forEach((item, i) => {
                    item.is_default = (i === idx && !isCurrent);
                });
            },

            // ── Validation & Save Actions ──
            validateAndPromptSave() {
                this.formErrors = {};
                this.formSubmitted = true;
                const trimmedName = (this.templateName || '').trim();

                if (!trimmedName) {
                    this.formErrors.name = 'Template name is required.';
                } else {
                    const dup = (this.templatesList || []).find(t => 
                        (t.name || '').trim().toLowerCase() === trimmedName.toLowerCase() && 
                        Number(t.id) !== Number(this.editTemplateId)
                    );
                    if (dup) {
                        this.formErrors.name = `A template named "${trimmedName}" already exists in the library.`;
                    }
                }

                if (!this.templateItems || this.templateItems.length === 0) {
                    this.formErrors.templateItems = 'At least one variation option is required.';
                } else {
                    const emptyItem = this.templateItems.some(item => !(item.name || '').trim());
                    if (emptyItem) {
                        this.formErrors.itemNames = 'Every option must have a name.';
                    }
                }

                if (Object.keys(this.formErrors).length > 0) {
                    return; // DO NOT OPEN MODAL!
                }

                this.$dispatch('open-modal', 'confirm-save-template');
            },

            async confirmSave() {
                this.$dispatch('close-modal', 'confirm-save-template');
                this.isSaving = true;
                try {
                    const payload = {
                        id: this.editTemplateId,
                        name: (this.templateName || '').trim(),
                        priceMode: this.priceMode || 'additive',
                        isRequired: !!this.isRequired,
                        noRecipeRequired: !!this.noRecipeRequired,
                        templateItems: JSON.parse(JSON.stringify(this.templateItems || []))
                    };
                    await this.$wire.saveTemplate(payload);
                } catch (err) {
                    console.error('Error saving template:', err);
                    this.$dispatch('notify', { type: 'error', message: 'Failed to save template.' });
                } finally {
                    this.isSaving = false;
                }
            },

            init() {
                this.$watch('searchQuery', () => { this.currentPage = 1; });
                this.$watch('priceModeFilter', () => { this.currentPage = 1; });
                this.$watch('perPage', () => { this.currentPage = 1; });
            }
        };
    };

    // Stock Management Component (0ms Instant Operations)
    window.stockManagement = function($wire) {
        const tabState = (typeof slidingTabsLogic === 'function') 
            ? slidingTabsLogic($wire.entangle('panel').live, 'panel')
            : { init() {} };
            
        return {
            ...tabState,
            panel: $wire.entangle('panel').live,
            mode: 'list',
            editIngredientId: $wire.entangle('editIngredientId'),
            ingredientName: $wire.entangle('ingredientName'),
            ingredientCategoryId: $wire.entangle('ingredientCategoryId'),
            ingredientUnit: $wire.entangle('ingredientUnit'),
            ingredientMinStock: $wire.entangle('ingredientMinStock'),
            ingredientCost: $wire.entangle('ingredientCost'),
            conversionRows: $wire.entangle('conversionRows'),
            deleteTargetId: $wire.entangle('deleteTargetId'),
            deleteTargetName: $wire.entangle('deleteTargetName'),
            wasteTargetId: $wire.entangle('wasteTargetId'),
            wasteTargetName: $wire.entangle('wasteTargetName'),
            wasteTargetQty: $wire.entangle('wasteTargetQty'),
            wasteTargetUnit: $wire.entangle('wasteTargetUnit'),

            formErrors: {},
            formSubmitted: false,

            // Client-side search and pagination
            stockSearch: '',
            stockStatusFilter: '',
            stockCategoryFilter: '',
            stockCurrentPage: 1,
            stockPerPage: 5,
            ingredientsList: [],

            expirySearchText: '',
            expiryStatus: 'all',
            expiryCurrentPage: 1,
            expiryPerPage: 5,
            batchesList: [],

            get filteredIngredientIds() {
                const query = (this.stockSearch || '').toLowerCase().trim();
                const status = this.stockStatusFilter;
                const category = this.stockCategoryFilter;
                return (this.ingredientsList || [])
                    .filter(i => {
                        const matchesSearch = !query || (i.name || '').toLowerCase().includes(query);
                        const matchesStatus = !status || i.status === status;
                        const matchesCategory = !category || String(i.category_id) === String(category);
                        return matchesSearch && matchesStatus && matchesCategory;
                    })
                    .map(i => i.id);
            },
            get paginatedIngredientIds() {
                const start = (this.stockCurrentPage - 1) * this.stockPerPage;
                return this.filteredIngredientIds.slice(start, start + this.stockPerPage);
            },
            get stockPageNumbers() {
                const totalPages = Math.ceil(this.filteredIngredientIds.length / this.stockPerPage) || 1;
                const start = Math.max(1, this.stockCurrentPage - 1);
                const end = Math.min(totalPages, this.stockCurrentPage + 1);
                const pages = [];
                for (let i = start; i <= end; i++) {
                    pages.push(i);
                }
                return pages;
            },
            isIngredientVisible(id) {
                return this.paginatedIngredientIds.includes(id);
            },
            updateIngredientsList(newList) {
                this.ingredientsList = newList || [];
            },

            get filteredBatchIds() {
                const query = (this.expirySearchText || '').toLowerCase().trim();
                const status = this.expiryStatus;
                return (this.batchesList || [])
                    .filter(b => {
                        const matchesSearch = !query || (b.ingredient_name || '').toLowerCase().includes(query);
                        const matchesStatus = status === 'all' || b.status === status;
                        return matchesSearch && matchesStatus;
                    })
                    .map(b => b.id);
            },
            get paginatedBatchIds() {
                const start = (this.expiryCurrentPage - 1) * this.expiryPerPage;
                return this.filteredBatchIds.slice(start, start + this.expiryPerPage);
            },
            get expiryPageNumbers() {
                const totalPages = Math.ceil(this.filteredBatchIds.length / this.expiryPerPage) || 1;
                const start = Math.max(1, this.expiryCurrentPage - 1);
                const end = Math.min(totalPages, this.expiryCurrentPage + 1);
                const pages = [];
                for (let i = start; i <= end; i++) {
                    pages.push(i);
                }
                return pages;
            },
            isBatchVisible(id) {
                return this.paginatedBatchIds.includes(id);
            },
            updateBatchesList(newList) {
                this.batchesList = newList || [];
            },

            isLoadingData: false,

            // ── Instant 0ms Actions ──
            showCreate() {
                this.panel = 'form';
                this.mode = 'create';
                this.editIngredientId = null;
                this.isLoadingData = false;
                this.ingredientName = '';
                this.ingredientCategoryId = '';
                this.ingredientUnit = 'pcs';
                this.ingredientMinStock = '';
                this.conversionRows = [];
                this.formErrors = {};
                this.formSubmitted = false;
                this.$wire.showCreate();
            },
            showEdit(id) {
                this.panel = 'form';
                this.mode = 'edit';
                this.editIngredientId = id;
                this.isLoadingData = true;
                this.formErrors = {};
                this.formSubmitted = false;
                const p = this.$wire.showEdit(id);
                if (p && typeof p.then === 'function') {
                    p.then(() => { this.isLoadingData = false; }).catch(() => { this.isLoadingData = false; });
                } else {
                    setTimeout(() => { this.isLoadingData = false; }, 300);
                }
            },
            backToList() {
                this.panel = 'list';
                this.mode = 'list';
                this.isLoadingData = false;
                this.formErrors = {};
                this.formSubmitted = false;
                this.$wire.backToList();
            },
            confirmDelete(id, name) {
                this.deleteTargetId = id;
                this.deleteTargetName = name;
                this.$wire.deleteTargetId = id;
                this.$wire.deleteTargetName = name;
                this.$dispatch('open-modal', 'confirm-delete-ingredient');
            },
            confirmWaste(id, name, qty, unit) {
                this.wasteTargetId = id;
                this.wasteTargetName = name;
                this.wasteTargetQty = qty;
                this.wasteTargetUnit = unit;
                this.$wire.wasteTargetId = id;
                this.$wire.wasteTargetName = name;
                this.$wire.wasteTargetQty = qty;
                this.$wire.wasteTargetUnit = unit;
                this.$dispatch('open-modal', 'confirm-waste-batch');
            },
            saveIngredient() {
                this.formErrors = {};
                this.formSubmitted = true;
                let hasErrors = false;

                const name = (this.ingredientName || '').trim();
                if (!name) {
                    this.formErrors.name = 'Ingredient name is required.';
                    hasErrors = true;
                }
                const minStock = parseFloat(this.ingredientMinStock);
                if (this.ingredientMinStock === '' || this.ingredientMinStock === null || isNaN(minStock) || minStock < 0) {
                    this.formErrors.minStock = 'Minimum stock must be a non-negative number.';
                    hasErrors = true;
                }
                if (Array.isArray(this.conversionRows)) {
                    for (let i = 0; i < this.conversionRows.length; i++) {
                        const r = this.conversionRows[i];
                        if (!r.unit_name) {
                            this.formErrors.conversionRows = 'Packaging unit is required for packaging tier ' + (i + 1) + '.';
                            hasErrors = true;
                            break;
                        }
                        if (!r.chain_multiplier || parseFloat(r.chain_multiplier) <= 0) {
                            this.formErrors.conversionRows = 'Please specify a valid pack size for packaging tier ' + (i + 1) + '.';
                            hasErrors = true;
                            break;
                        }
                    }
                }
                if (hasErrors) {
                    return;
                }
                this.$dispatch('open-modal', 'confirm-save-ingredient');
            },

            // ── Bulk Packaging (0ms) ──
            addConversionRow() {
                if (this.formErrors && this.formErrors.conversionRows) delete this.formErrors.conversionRows;
                if (!Array.isArray(this.conversionRows)) this.conversionRows = [];
                this.conversionRows.push({
                    unit_name: '',
                    qty_in_base: '',
                    price_per_unit: 0,
                    sort_order: this.conversionRows.length,
                    chain_multiplier: '',
                    chain_from_index: 'base'
                });
            },
            removeConversionRow(idx) {
                if (!Array.isArray(this.conversionRows)) return;
                this.conversionRows.splice(idx, 1);
                this.conversionRows.forEach((r, i) => {
                    r.sort_order = i;
                    if (r.chain_from_index !== 'base' && r.chain_from_index !== null && r.chain_from_index !== undefined) {
                        const fi = Number(r.chain_from_index);
                        if (fi === idx) {
                            r.chain_from_index = 'base';
                        } else if (fi > idx) {
                            r.chain_from_index = fi - 1;
                        }
                    }
                });
                this.cascadeRecompute(0);
            },
            setConversionLink(rowIndex, fromIndex) {
                if (!Array.isArray(this.conversionRows) || !this.conversionRows[rowIndex]) return;
                this.conversionRows[rowIndex].chain_from_index = fromIndex === 'base' ? 'base' : Number(fromIndex);
                this.computeQtyInBase(rowIndex);
                this.cascadeRecompute(rowIndex + 1);
            },
            updateConversionMultiplier(rowIndex) {
                if (!Array.isArray(this.conversionRows) || !this.conversionRows[rowIndex]) return;
                const row = this.conversionRows[rowIndex];
                const mult = parseFloat(row.chain_multiplier);
                if (!mult || mult <= 0) {
                    row.qty_in_base = '';
                    this.cascadeRecompute(rowIndex + 1);
                    return;
                }
                if (row.chain_from_index === null || row.chain_from_index === undefined || row.chain_from_index === '') {
                    row.chain_from_index = 'base';
                }
                this.computeQtyInBase(rowIndex);
                this.cascadeRecompute(rowIndex + 1);
            },
            computeQtyInBase(index) {
                const row = this.conversionRows[index];
                if (!row) return;
                const mult = parseFloat(row.chain_multiplier) || 0;
                const fromIndex = row.chain_from_index;
                if (mult <= 0) {
                    row.qty_in_base = '';
                    return;
                }
                if (fromIndex === 'base' || fromIndex === null || fromIndex === undefined || fromIndex === '') {
                    row.qty_in_base = mult;
                } else {
                    const prevRow = this.conversionRows[Number(fromIndex)];
                    if (prevRow && parseFloat(prevRow.qty_in_base) > 0) {
                        row.qty_in_base = mult * parseFloat(prevRow.qty_in_base);
                    } else {
                        row.qty_in_base = '';
                    }
                }
            },
            cascadeRecompute(startIndex) {
                if (!Array.isArray(this.conversionRows)) return;
                for (let i = startIndex; i < this.conversionRows.length; i++) {
                    const fromIndex = this.conversionRows[i].chain_from_index;
                    if (fromIndex !== null && fromIndex !== undefined && fromIndex !== '') {
                        this.computeQtyInBase(i);
                    }
                }
            },
            getChainDisplay(row) {
                const fromIdx = row.chain_from_index;
                if (fromIdx === 'base' || fromIdx === '' || fromIdx === null || fromIdx === undefined) {
                    return '1 ' + (this.ingredientUnit || 'PCS').toUpperCase();
                }
                const cRow = this.conversionRows[Number(fromIdx)];
                if (cRow && cRow.unit_name) {
                    const prevQty = (parseFloat(cRow.qty_in_base) || 0).toFixed(2);
                    return (cRow.unit_name).toUpperCase() + ' (' + prevQty + ' ' + (this.ingredientUnit || 'PCS').toUpperCase() + ')';
                }
                return '1 ' + (this.ingredientUnit || 'PCS').toUpperCase();
            },
            hasChainingOptions(rowIndex) {
                if (!Array.isArray(this.conversionRows) || rowIndex <= 0) return false;
                for (let j = 0; j < rowIndex; j++) {
                    if (this.conversionRows[j] && this.conversionRows[j].unit_name) return true;
                }
                return false;
            },
            getBulkUnitsForBase(baseUnit) {
                const all = {
                    'box': 'Box',
                    'sack': 'Sack',
                    'bottle': 'Bottle',
                    'can': 'Can',
                    'pack': 'Pack',
                    'bundle': 'Bundle',
                    'tray': 'Tray',
                    'pcs': 'Pieces (pcs)',
                    'kg': 'Kilograms (kg)',
                    'l': 'Liters (L)'
                };
                if (baseUnit === 'ml') {
                    return Object.entries(all)
                        .filter(([k]) => ['l', 'bottle', 'box', 'can', 'pack', 'bundle', 'tray'].includes(k))
                        .map(([k, v]) => ({ key: k, label: v }));
                }
                if (baseUnit === 'g') {
                    return Object.entries(all)
                        .filter(([k]) => ['kg', 'sack', 'pack', 'box', 'bundle', 'can'].includes(k))
                        .map(([k, v]) => ({ key: k, label: v }));
                }
                if (baseUnit === 'pcs') {
                    return Object.entries(all)
                        .filter(([k]) => ['box', 'pack', 'bundle', 'tray', 'sack'].includes(k))
                        .map(([k, v]) => ({ key: k, label: v }));
                }
                return Object.entries(all).map(([k, v]) => ({ key: k, label: v }));
            },
            get optimalPackaging() {
                let bestCost = null;
                let bestUnit = '';
                if (Array.isArray(this.conversionRows) && this.conversionRows.length > 0) {
                    this.conversionRows.forEach(r => {
                        const qty = parseFloat(r.qty_in_base) || 0;
                        const price = parseFloat(r.price_per_unit) || 0;
                        if (qty > 0 && price > 0) {
                            const c = price / qty;
                            if (bestCost === null || c < bestCost) {
                                bestCost = c;
                                bestUnit = r.unit_name || '';
                            }
                        }
                    });
                }
                return { bestCost, bestUnit };
            },

            init() {
                if (tabState.init) tabState.init.call(this);

                this.$watch('stockSearch', () => { this.stockCurrentPage = 1; });
                this.$watch('stockStatusFilter', () => { this.stockCurrentPage = 1; });
                this.$watch('stockCategoryFilter', () => { this.stockCurrentPage = 1; });
                this.$watch('expirySearchText', () => { this.expiryCurrentPage = 1; });
                this.$watch('expiryStatus', () => { this.expiryCurrentPage = 1; });
                this.$watch('ingredientName', () => { if (this.formErrors && this.formErrors.name) delete this.formErrors.name; });
                this.$watch('ingredientMinStock', () => { if (this.formErrors && this.formErrors.minStock) delete this.formErrors.minStock; });
                this.$watch('panel', () => {
                    this.stockSearch = '';
                    this.stockStatusFilter = '';
                    this.stockCategoryFilter = '';
                    this.stockCurrentPage = 1;
                    this.expirySearchText = '';
                    this.expiryStatus = 'all';
                    this.expiryCurrentPage = 1;
                    this.formErrors = {};
                    this.formSubmitted = false;
                });
            }
        };
    };

    const registerAlpineFactories = () => {
        if (!window.Alpine) return;

        window.Alpine.data('slidingTabs', slidingTabsLogic);
        window.Alpine.data('modal', ({ name, show }) => window.modal({ name, show }));
        window.Alpine.data('sidePanel', ({ name, show }) => window.sidePanel({ name, show }));
        window.Alpine.data('optionLibraryManagement', ($wire, allIngredients) => window.optionLibraryManagement($wire, allIngredients));
        window.Alpine.data('stockManagement', ($wire) => window.stockManagement($wire));
    };

    document.addEventListener('alpine:init', registerAlpineFactories);
    document.addEventListener('livewire:navigated', registerAlpineFactories);
    registerAlpineFactories();
})();
