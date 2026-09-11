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

    // Branch Stock Ordering Component (0ms Instant Operations & Combobox)
    window.branchStockOrdering = function($wire, config) {
        config = config || {};
        const tabState = (typeof slidingTabsLogic === 'function') 
            ? slidingTabsLogic($wire.entangle('panel').live, 'panel')
            : { init() {} };

        return {
            ...tabState,
            panel: $wire.entangle('panel').live,

            // Data lists (frozen to prevent Alpine deep reactive proxy overhead)
            ingredientsList: Object.freeze(config.ingredients || []),
            branchStock: Object.freeze(config.branchStock || {}),
            mainStock: Object.freeze(config.mainStock || {}),
            logistics: Object.freeze(config.logistics || {}),
            restockSuggestions: config.restockSuggestions || [],

            // Combobox state
            comboboxOpen: false,
            comboboxSearch: '',
            comboboxDropUp: false,
            selectedIngredientId: config.initialIngredientId ? Number(config.initialIngredientId) : null,

            // Item Form state
            cartQty: '',
            cartUnit: '',
            cartPrice: 0,
            cartNotes: '',
            qtyErrorMessage: '',

            // Cart state
            cartItems: [],
            orderPriority: 'normal',
            orderNotes: '',
            isSubmitting: false,
            itemToRemoveIndex: null,
            itemToRemoveName: '',

            // Helper: Convert g/ml to higher unit (kg/L) when >= 1000
            formatStockQty(quantity, baseUnit) {
                if (quantity === null || quantity === undefined || isNaN(quantity)) return '0 ' + (baseUnit || '');
                const qty = parseFloat(quantity);
                const unit = (baseUnit || '').toLowerCase().trim();

                if (unit === 'g' || unit === 'grams' || unit === 'gram') {
                    if (Math.abs(qty) >= 1000) {
                        const val = qty / 1000;
                        return (val.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })) + ' kg';
                    }
                    return (qty.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 1 })) + ' g';
                }

                if (unit === 'kg') {
                    return (qty.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })) + ' kg';
                }

                if (unit === 'ml' || unit === 'milliliters' || unit === 'milliliter') {
                    if (Math.abs(qty) >= 1000) {
                        const val = qty / 1000;
                        return (val.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })) + ' L';
                    }
                    return (qty.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 1 })) + ' ml';
                }

                if (unit === 'l' || unit === 'liter' || unit === 'liters') {
                    return (qty.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })) + ' L';
                }

                return (qty.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })) + ' ' + (baseUnit || '');
            },

            // Getters
            get selectedIngredient() {
                if (!this.selectedIngredientId) return null;
                return this.ingredientsList.find(i => Number(i.id) === Number(this.selectedIngredientId)) || null;
            },

            get filteredIngredients() {
                const q = (this.comboboxSearch || '').toLowerCase().trim();
                if (!q) return this.ingredientsList;
                return this.ingredientsList.filter(i => (i.name || '').toLowerCase().includes(q));
            },

            get currentBranchStock() {
                if (!this.selectedIngredientId) return 0;
                return parseFloat(this.branchStock[this.selectedIngredientId] || 0);
            },

            get currentHqStock() {
                if (!this.selectedIngredientId) return 0;
                return parseFloat(this.mainStock[this.selectedIngredientId] || 0);
            },

            get qtyInBase() {
                const ing = this.selectedIngredient;
                const qty = parseFloat(this.cartQty) || 0;
                if (!ing || qty <= 0) return 0;
                const selectedUnit = this.cartUnit || ing.unit;
                if (selectedUnit === ing.unit) return qty;
                const conv = (ing.unit_conversions || []).find(c => c.unit_name === selectedUnit);
                if (conv && parseFloat(conv.qty_in_base) > 0) {
                    return qty * parseFloat(conv.qty_in_base);
                }
                return qty;
            },

            get isStockExceeded() {
                if (!this.selectedIngredientId || !this.cartQty) return false;
                return this.qtyInBase > this.currentHqStock;
            },

            get itemSubtotal() {
                const qty = parseFloat(this.cartQty) || 0;
                const price = parseFloat(this.cartPrice) || 0;
                return qty * price;
            },

            get cartSubtotal() {
                return this.cartItems.reduce((sum, item) => sum + (parseFloat(item.subtotal) || 0), 0);
            },

            get calculatedDeliveryFee() {
                const subtotal = this.cartSubtotal;
                const freeThreshold = parseFloat(this.logistics.freeThreshold || 0);
                if (freeThreshold > 0 && subtotal >= freeThreshold) {
                    return 0;
                }
                const baseFee = parseFloat(this.logistics.baseFee || 0);
                const distance = parseFloat(this.logistics.branchDistance || 0);
                const rate = parseFloat(this.logistics.globalRate || 50);
                const minFee = parseFloat(this.logistics.minFee || 0);
                const maxFee = parseFloat(this.logistics.maxFee || 5000);

                const computed = baseFee + (distance * rate);
                return Math.max(minFee, Math.min(maxFee, computed));
            },

            get cartTotal() {
                return this.cartSubtotal + this.calculatedDeliveryFee;
            },

            // Combobox Actions
            openCombobox() {
                this.checkComboboxFlip();
                this.comboboxOpen = true;
                this.comboboxSearch = '';
            },

            closeCombobox() {
                this.comboboxOpen = false;
                this.comboboxSearch = '';
            },

            checkComboboxFlip() {
                const container = this.$refs.comboboxContainer;
                if (!container) return;
                const rect = container.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                this.comboboxDropUp = spaceBelow < 250 && rect.top > 250;
            },

            selectIngredient(id) {
                this.selectedIngredientId = Number(id);
                this.comboboxOpen = false;
                this.comboboxSearch = '';
                this.qtyErrorMessage = '';

                const ing = this.selectedIngredient;
                if (ing) {
                    this.cartUnit = ing.unit || 'pcs';
                    this.cartPrice = parseFloat(ing.cost || 0);
                } else {
                    this.cartUnit = '';
                    this.cartPrice = 0;
                }
                this.validateQty();
            },

            clearIngredient() {
                this.selectedIngredientId = null;
                this.comboboxOpen = false;
                this.comboboxSearch = '';
                this.cartQty = '';
                this.cartUnit = '';
                this.cartPrice = 0;
                this.cartNotes = '';
                this.qtyErrorMessage = '';
            },

            selectUnit(unitName) {
                const ing = this.selectedIngredient;
                if (!ing) return;
                this.cartUnit = unitName;
                if (unitName === ing.unit) {
                    this.cartPrice = parseFloat(ing.cost || 0);
                } else {
                    const conv = (ing.unit_conversions || []).find(c => c.unit_name === unitName);
                    this.cartPrice = conv ? parseFloat(conv.price_per_unit || 0) : 0;
                }
                this.validateQty();
            },

            validateQty() {
                this.qtyErrorMessage = '';
                const qty = parseFloat(this.cartQty);
                if (!this.selectedIngredientId || !this.cartQty) return true;
                if (isNaN(qty) || qty <= 0) {
                    this.qtyErrorMessage = 'Quantity must be greater than zero.';
                    return false;
                }
                const ing = this.selectedIngredient;
                if (!ing) return true;

                const inBase = this.qtyInBase;
                const hqStock = this.currentHqStock;
                if (inBase > hqStock) {
                    let msg = `Insufficient stock at HQ. Only ${this.formatStockQty(hqStock, ing.unit)} available.`;
                    if (this.cartUnit !== ing.unit) {
                        const conv = (ing.unit_conversions || []).find(c => c.unit_name === this.cartUnit);
                        if (conv && parseFloat(conv.qty_in_base) > 0) {
                            const inUnits = Math.floor(hqStock / parseFloat(conv.qty_in_base));
                            msg += ` (Approx. ${inUnits} ${this.cartUnit})`;
                        }
                    }
                    this.qtyErrorMessage = msg;
                    return false;
                }
                return true;
            },

            // Cart Actions (0ms Instant)
            addToCart() {
                if (!this.selectedIngredientId) {
                    this.$dispatch('notify', { type: 'error', message: 'Please select an ingredient.' });
                    return;
                }
                const qty = parseFloat(this.cartQty);
                if (!qty || qty <= 0) {
                    this.qtyErrorMessage = 'Quantity must be greater than zero.';
                    return;
                }
                if (!this.validateQty()) {
                    this.$dispatch('notify', { type: 'error', message: this.qtyErrorMessage });
                    return;
                }
                if (this.isInCart(this.selectedIngredientId)) {
                    this.$dispatch('notify', { type: 'warning', message: 'This item is already in your cart.' });
                    return;
                }

                const ing = this.selectedIngredient;
                const selectedUnit = this.cartUnit || ing.unit;
                const qtyInBase = this.qtyInBase;
                const branchQty = this.currentBranchStock;

                this.cartItems.push({
                    ingredient_id: ing.id,
                    ingredient_name: ing.name,
                    unit: selectedUnit,
                    order_unit: selectedUnit,
                    quantity: qty,
                    qty_in_base: qtyInBase,
                    unit_price: this.cartPrice,
                    subtotal: this.itemSubtotal,
                    notes: this.cartNotes,
                    current_stock: branchQty,
                    min_stock: ing.minimum_stock,
                    is_low: branchQty <= ing.minimum_stock,
                });

                this.$dispatch('notify', { type: 'success', message: `${ing.name} added to cart.` });
                this.clearIngredient();
            },

            isInCart(id) {
                return this.cartItems.some(item => Number(item.ingredient_id) === Number(id));
            },

            promptRemoveItem(index) {
                const item = this.cartItems[index];
                if (!item) return;
                this.itemToRemoveIndex = index;
                this.itemToRemoveName = item.ingredient_name;
                this.$dispatch('open-modal', 'confirm-remove-cart-item');
            },

            confirmRemoveItem() {
                if (this.itemToRemoveIndex !== null && this.itemToRemoveIndex >= 0) {
                    const name = this.itemToRemoveName;
                    this.cartItems.splice(this.itemToRemoveIndex, 1);
                    this.itemToRemoveIndex = null;
                    this.itemToRemoveName = '';
                    this.$dispatch('close-modal', 'confirm-remove-cart-item');
                    this.$dispatch('notify', { type: 'info', message: `${name} removed from cart.` });
                }
            },

            removeFromCart(index) {
                this.promptRemoveItem(index);
            },

            promptClearCart() {
                if (this.cartItems.length === 0) return;
                this.$dispatch('open-modal', 'confirm-clear-cart');
            },

            confirmClearCart() {
                this.cartItems = [];
                this.clearIngredient();
                this.$dispatch('close-modal', 'confirm-clear-cart');
                this.$dispatch('notify', { type: 'info', message: 'Cart cleared.' });
            },

            clearCart() {
                this.promptClearCart();
            },

            addSuggestionToCart(item) {
                if (this.isInCart(item.id)) return;
                const ing = this.ingredientsList.find(i => Number(i.id) === Number(item.id));
                if (!ing) return;

                const branchQty = parseFloat(this.branchStock[ing.id] || 0);
                const mainQty = parseFloat(this.mainStock[ing.id] || 0);
                const deficit = Math.max(1, (parseFloat(ing.minimum_stock) || 0) - branchQty);
                const finalQty = Math.min(deficit, mainQty);

                if (finalQty <= 0 || mainQty <= 0) {
                    this.$dispatch('notify', { type: 'warning', message: `Cannot suggest ${ing.name} - HQ is out of stock.` });
                    return;
                }

                this.cartItems.push({
                    ingredient_id: ing.id,
                    ingredient_name: ing.name,
                    unit: ing.unit,
                    order_unit: ing.unit,
                    quantity: finalQty,
                    qty_in_base: finalQty,
                    unit_price: parseFloat(ing.cost || 0),
                    subtotal: finalQty * parseFloat(ing.cost || 0),
                    notes: 'Auto-replenishment for low stock.',
                    current_stock: branchQty,
                    min_stock: ing.minimum_stock,
                    is_low: true,
                });

                this.$dispatch('notify', { type: 'info', message: `${ing.name} added to cart.` });
            },

            promptSubmitOrder() {
                if (this.cartItems.length === 0) {
                    this.$dispatch('notify', { type: 'error', message: 'Your cart is empty.' });
                    return;
                }
                this.$dispatch('open-modal', 'confirm-submit-order');
            },

            async confirmSubmitOrder() {
                if (this.cartItems.length === 0) return;
                this.isSubmitting = true;
                try {
                    const success = await this.$wire.submitOrder(
                        JSON.parse(JSON.stringify(this.cartItems)),
                        this.orderPriority,
                        this.orderNotes
                    );
                    if (success !== false) {
                        this.cartItems = [];
                        this.clearIngredient();
                        this.orderNotes = '';
                        this.orderPriority = 'normal';
                        this.panel = 'requests';
                        this.$dispatch('close-modal', 'confirm-submit-order');
                    }
                } catch (err) {
                    console.error('Order submission failed', err);
                } finally {
                    this.isSubmitting = false;
                }
            },

            init() {
                if (tabState.init) tabState.init.call(this);
                this.$watch('cartQty', () => this.validateQty());
                if (this.selectedIngredientId) {
                    this.selectIngredient(this.selectedIngredientId);
                }
                window.addEventListener('update-stock-data', (event) => {
                    if (event.detail) {
                        if (event.detail.branchStock) this.branchStock = Object.freeze(event.detail.branchStock);
                        if (event.detail.mainStock) this.mainStock = Object.freeze(event.detail.mainStock);
                        if (event.detail.restockSuggestions) this.restockSuggestions = event.detail.restockSuggestions;
                    }
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
        window.Alpine.data('branchStockOrdering', ($wire, config) => window.branchStockOrdering($wire, config));
    };

    document.addEventListener('alpine:init', registerAlpineFactories);
    document.addEventListener('livewire:navigated', registerAlpineFactories);
    registerAlpineFactories();
})();
