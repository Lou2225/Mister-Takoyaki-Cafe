<div
    x-data="userManagementData(@js($panel), @js($mode), @js($view), @js($roles->pluck('name', 'id')), @js($branches->pluck('branch_name', 'id')), @js($activeTab))"
    @trigger-edit.window="if ($event.detail.mode === 'view') { openViewProfile($event.detail.id); } else { openEditProfile($event.detail.id); }"
    @user-mode-changed.window="panel = $event.detail.panel; mode = $event.detail.mode;"
    @user-profile-loaded.window="if (typeof loadUserProfile === 'function') { loadUserProfile($event.detail); } else if (typeof $data !== 'undefined' && typeof $data.loadUserProfile === 'function') { $data.loadUserProfile($event.detail); } else if (window.loadUserProfile) { window.loadUserProfile($event.detail); }"
    class="relative">

    <div class="relative min-h-[600px]">
        {{-- ════════════════ PANEL 2 — FORM (CREATE/EDIT) ════════════════ --}}
        @include('livewire.user-management.partials.form-panel')

        {{-- ════════════════ DASHBOARD (VIEW MODE) ════════════════ --}}
        @include('livewire.user-management.partials.view-profile-panel')

        {{-- ════════════════ PANEL 1 — LIST ════════════════ --}}
        <div x-show="panel === 'list'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">

            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">{{ auth()->user()->isSuperAdmin() ? 'User Management' : 'Staff Management' }}</h2>
                    <p class="text-[12px] text-gray-500 font-medium">System Overview: <span class="text-indigo-600 font-bold">{{ $totalUsers }} members</span></p>
                </div>
                <x-primary-button @click="panel = 'form'; mode = 'create'; $wire.showCreate()" class="h-10 !px-2.5 sm:!px-4">
                    <svg class="w-4 h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span class="hidden sm:inline">Register New User</span>
                </x-primary-button>
            </div>

            {{-- Sliding Tabs for Directory vs Archived --}}
            <x-sliding-tabs model="activeTab" class="mb-6">
                <x-sliding-tab model="activeTab" value="directory">
                    <x-slot name="icon">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </x-slot>
                    Team Directory
                </x-sliding-tab>
                <x-sliding-tab model="activeTab" value="archived">
                    <x-slot name="icon">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 01-2-2V4a2 2 0 012-2h14a2 2 0 012 2v2a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    </x-slot>
                    Archived
                    @if($archivedUsers->total() > 0)
                        <span class="inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-slate-400 text-white text-[9px] font-black ml-1">{{ $archivedUsers->total() }}</span>
                    @endif
                </x-sliding-tab>
            </x-sliding-tabs>

            @include('livewire.user-management.partials.directory-tab')
            @include('livewire.user-management.partials.archived-tab')

        </div>{{-- end panel 1 --}}
    </div>

    {{-- Modals and Slide-over Drawers --}}
    @include('livewire.user-management.partials.modals')

    {{-- Alpine Data Script --}}
    <script>
    (function() {
        const STORE_KEY = 'um';

        window.loadUserProfile = function(detail) {
            if (!detail) return;
            const data = (detail && detail.id !== undefined)
                ? detail
                : (detail && detail.data ? detail.data
                : (detail && detail[0] ? detail[0]
                : (detail && typeof detail === 'object' && Object.values(detail)[0]?.id !== undefined ? Object.values(detail)[0]
                : detail)));
            if (!data) return;
            const S = Alpine.store(STORE_KEY);
            if (S) {
                S.editUserId    = data.id || null;
                S.formRoleId    = String(data.roleId || '');
                S.formBranchId  = String(data.branchId || '');
                S.position      = data.position || '';
                S.addr_region   = data.region || '';
                S.addr_province = data.province || '';
                S.addr_city     = data.city || '';
                S.addr_barangay = data.barangay || '';
                S.addr_street   = data.street || '';
                const rLat = parseFloat(data.lat);
                const rLng = parseFloat(data.lng);
                S.addr_lat = (!isNaN(rLat) && rLat !== 0) ? rLat : null;
                S.addr_lng = (!isNaN(rLng) && rLng !== 0) ? rLng : null;
            }
        };

        const registerUserData = () => {
            if (!window.Alpine) return;

            // ── Bootstrap the Alpine store for all reactive UI state ──────────
            // The store is global and survives Livewire DOM morphing/cloneNode.
            if (!Alpine.store(STORE_KEY)) {
                Alpine.store(STORE_KEY, {
                    panel:            @js($panel),
                    mode:             @js($mode),
                    activeTab:        @js($activeTab),
                    historyTab:       @js($historyTab),
                    tableView:        @js($view ?: 'table'),
                    loadingProfileId: null,
                    formRoleId:       @js($formRoleId ?? ''),
                    formBranchId:     @js($formBranchId ?? ''),
                    position:         @js($position ?? ''),
                    addr_street:      @js($addr_street ?? ''),
                    addr_region:      @js($addr_region ?? ''),
                    addr_province:    @js($addr_province ?? ''),
                    addr_city:        @js($addr_city ?? ''),
                    addr_barangay:    @js($addr_barangay ?? ''),
                    addr_lat:         @js($addr_lat ?? null),
                    addr_lng:         @js($addr_lng ?? null),
                    editUserId:       @js($editUserId ?? null),
                    saving:           false,
                    restoring:        false,
                });
            } else {
                const s = Alpine.store(STORE_KEY);
                if (typeof s.panel !== 'string') s.panel = @js($panel) || 'list';
                if (typeof s.mode !== 'string') s.mode = @js($mode) || 'list';
            }

            Alpine.data('userManagementData', (initialPanel, initialMode, initialView, rolesMap, branchesMap, initialActiveTab) => {
                // ── All volatile state backed by the global Alpine store ───────────
                // Store getters are evaluated on EVERY node including Livewire morph
                // clones.  Because $wire is NOT passed to this factory, the factory
                // body is safe to call on detached clones.  Real $wire.entangle()
                // calls are deferred to init(), which only runs on live mounted nodes.
                const S = () => Alpine.store(STORE_KEY) || {};

                return {
                    // Panel / mode / tab state — always resolved from the store so
                    // that x-show, x-text etc. never see undefined on cloned nodes.
                    get panel()             { return S().panel            ?? initialPanel       ?? 'list'; },
                    set panel(v)            { Alpine.store(STORE_KEY).panel = v; },

                    get mode()              { return S().mode             ?? initialMode        ?? 'list'; },
                    set mode(v)             { Alpine.store(STORE_KEY).mode = v; },

                    get activeTab()         { return S().activeTab        ?? initialActiveTab   ?? 'directory'; },
                    set activeTab(v)        { Alpine.store(STORE_KEY).activeTab = v; },

                    get historyTab()        { return S().historyTab       ?? 'overview'; },
                    set historyTab(v)       { Alpine.store(STORE_KEY).historyTab = v; },

                    get tableView()         { return S().tableView        ?? initialView        ?? 'table'; },
                    set tableView(v)        { Alpine.store(STORE_KEY).tableView = v; },

                    get loadingProfileId()  { return S().loadingProfileId ?? null; },
                    set loadingProfileId(v) { Alpine.store(STORE_KEY).loadingProfileId = v; },

                    get saving()            { return S().saving           ?? false; },
                    set saving(v)           { Alpine.store(STORE_KEY).saving = v; },

                    get restoring()         { return S().restoring        ?? false; },
                    set restoring(v)        { Alpine.store(STORE_KEY).restoring = v; },

                    // ── Form-field state — backed directly by Livewire's $wire ──
                    // Reads are reactive; writes are deferred (sent with the next
                    // Livewire request), matching non-live entangle semantics.
                    // Falls back to the store on detached morph clones where $wire
                    // is unavailable. Never assign entangle() objects to these.
                    _w() { try { return this.$wire || null; } catch (e) { return null; } },
                    _get(key, fallback) {
                        const sVal = S()[key];
                        if (sVal !== undefined && sVal !== null && sVal !== '') {
                            return sVal;
                        }
                        const w = this._w();
                        let v = w ? w[key] : undefined;
                        if (v !== null && typeof v === 'object') v = undefined;
                        return (v !== undefined && v !== null && v !== '') ? v : (sVal ?? fallback);
                    },
                    _set(key, v) {
                        Alpine.store(STORE_KEY)[key] = v;
                        const w = this._w();
                        if (w) {
                            try { w[key] = v; } catch (e) {}
                        }
                    },
                    get editUserId()    { return this._get('editUserId', null); },
                    set editUserId(v)   { this._set('editUserId', v); },
                    get formRoleId()    { return this._get('formRoleId', ''); },
                    set formRoleId(v)   { this._set('formRoleId', v); },
                    get formBranchId()  { return this._get('formBranchId', ''); },
                    set formBranchId(v) { this._set('formBranchId', v); },
                    get position()      { return this._get('position', ''); },
                    set position(v)     { this._set('position', v); },
                    get addr_street()   { return this._get('addr_street', ''); },
                    set addr_street(v)  { this._set('addr_street', v); },
                    get addr_region()   { return this._get('addr_region', ''); },
                    set addr_region(v)  { this._set('addr_region', v); },
                    get addr_province() { return this._get('addr_province', ''); },
                    set addr_province(v){ this._set('addr_province', v); },
                    get addr_city()     { return this._get('addr_city', ''); },
                    set addr_city(v)    { this._set('addr_city', v); },
                    get addr_barangay() { return this._get('addr_barangay', ''); },
                    set addr_barangay(v){ this._set('addr_barangay', v); },
                    get addr_lat()      { const v = parseFloat(this._get('addr_lat', null)); return isNaN(v) ? null : v; },
                    set addr_lat(v)     { this._set('addr_lat', v); },
                    get addr_lng()      { const v = parseFloat(this._get('addr_lng', null)); return isNaN(v) ? null : v; },
                    set addr_lng(v)     { this._set('addr_lng', v); },

                    rolesMap:    rolesMap   || {},
                    branchesMap: branchesMap || {},

                    // ── Location state (component-local, not morphed) ──
                    loc: {
                        noProvince: false,
                        region:   { items: [], search: '', loading: false },
                        province: { items: [], search: '', loading: false },
                        city:     { items: [], search: '', loading: false },
                        barangay: { items: [], search: '', loading: false }
                    },

                    // Stubs — overridden in init() for live nodes, but safe to call
                    // on detached clones (no-ops that prevent ReferenceErrors).
                    updateIndicator() {},
                    syncToLivewire() {},
                    getCustomPinIcon() {
                        return L.divIcon({
                            html: `
                                <div class='relative flex flex-col items-center justify-end w-10 h-10'>
                                    <span class='absolute w-4 h-2 bg-indigo-500/40 rounded-full blur-[2px] animate-ping bottom-[-2px] left-1/2 -translate-x-1/2'></span>
                                    <div class='relative w-8 h-8 bg-indigo-600 rounded-t-full rounded-bl-full rotate-45 border-2 border-white shadow-lg flex items-center justify-center transition-all duration-300'>
                                        <div class='w-3.5 h-3.5 bg-white rounded-full -rotate-45 flex items-center justify-center shadow-inner'>
                                            <div class='w-1.5 h-1.5 bg-indigo-600 rounded-full'></div>
                                        </div>
                                    </div>
                                </div>
                            `,
                            className: 'custom-leaflet-icon',
                            iconSize: [40, 40],
                            iconAnchor: [20, 40]
                        });
                    },

                    invalidateUserCache(userId = null) {
                        if (!userId || this.editUserId == userId) {
                            this.editUserId = null;
                        }
                    },

                    async openViewProfile(userId) {
                        const targetId = (userId !== undefined && userId !== null && userId !== '')
                            ? userId
                            : (this.editUserId || (this.$wire ? this.$wire.editUserId : null) || Alpine.store(STORE_KEY).editUserId);
                        if (!targetId) return;
                        this.loadingProfileId = targetId;
                        try {
                            await this.$wire.showEdit(targetId, 'view');
                            this.panel = 'form';
                            this.mode = 'view';
                            this.$nextTick(() => {
                                setTimeout(() => this.updateIndicator('historyTab'), 50);
                                setTimeout(() => this.updateIndicator('historyTab'), 200);
                            });
                        } catch (err) {
                            console.error('Failed to open view profile:', err);
                        } finally {
                            this.loadingProfileId = null;
                        }
                    },

                    async openEditProfile(userId) {
                        const targetId = (userId !== undefined && userId !== null && userId !== '')
                            ? userId
                            : (this.editUserId || (this.$wire ? this.$wire.editUserId : null) || Alpine.store(STORE_KEY).editUserId);
                        if (!targetId) {
                            console.warn('openEditProfile: No targetId found');
                            return;
                        }
                        this.loadingProfileId = targetId;
                        try {
                            await this.$wire.showEdit(targetId, 'edit');
                            this.panel = 'form';
                            this.mode = 'edit';
                            this.$nextTick(() => {
                                if (typeof this.initMap === 'function') {
                                    this.initMap();
                                }
                            });
                        } catch (err) {
                            console.error('Failed to open edit profile:', err);
                        } finally {
                            this.loadingProfileId = null;
                        }
                    },

                    cancelForm() {
                        const uid = (typeof this.editUserId === 'number' || typeof this.editUserId === 'string') ? this.editUserId : null;
                        if (this.mode === 'edit' && uid) {
                            this.openViewProfile(uid);
                        } else {
                            this.panel = 'list';
                            this.mode = 'list';
                            this.$wire.backToList();
                        }
                    },

                    loadUserProfile(detail) {
                        if (!detail) return;
                        const data = (detail && detail.id !== undefined)
                            ? detail
                            : (detail && detail.data ? detail.data
                            : (detail && detail[0] ? detail[0]
                            : (detail && typeof detail === 'object' && Object.values(detail)[0]?.id !== undefined ? Object.values(detail)[0]
                            : detail)));
                        if (!data) return;
                        this.editUserId    = data.id || null;
                        this.formRoleId    = String(data.roleId || '');
                        this.formBranchId  = String(data.branchId || '');
                        this.position      = data.position || '';
                        this.addr_region   = data.region || '';
                        this.addr_province = data.province || '';
                        this.addr_city     = data.city || '';
                        this.addr_barangay = data.barangay || '';
                        this.addr_street   = data.street || '';
                        const rLat = parseFloat(data.lat);
                        const rLng = parseFloat(data.lng);
                        this.addr_lat = (!isNaN(rLat) && rLat !== 0) ? rLat : null;
                        this.addr_lng = (!isNaN(rLng) && rLng !== 0) ? rLng : null;

                        const S = Alpine.store(STORE_KEY);
                        if (S) {
                            S.editUserId    = this.editUserId;
                            S.formRoleId    = this.formRoleId;
                            S.formBranchId  = this.formBranchId;
                            S.position      = this.position;
                            S.addr_region   = this.addr_region;
                            S.addr_province = this.addr_province;
                            S.addr_city     = this.addr_city;
                            S.addr_barangay = this.addr_barangay;
                            S.addr_street   = this.addr_street;
                            S.addr_lat      = this.addr_lat;
                            S.addr_lng      = this.addr_lng;
                        }

                        this.loc.province.items = [];
                        this.loc.city.items = [];
                        this.loc.barangay.items = [];
                        this.loc.noProvince = false;
                        this.initializeExistingAddress();

                        this.$nextTick(() => {
                            if (this.mode === 'edit' && typeof this.initMap === 'function') {
                                this.initMap();
                            }
                        });
                    },

                    setRole(roleId) {
                        this.formRoleId = String(roleId);
                        if (this.formRoleId !== '3') this.position = '';
                        this.syncToLivewire();
                    },

                    setBranch(branchId) {
                        this.formBranchId = String(branchId);
                        this.syncToLivewire();
                    },

                    setPosition(pos) {
                        this.position = pos;
                        this.syncToLivewire();
                    },

                    syncToLivewire() {
                        if (!this.$wire) return;
                        this.$wire.set('formRoleId', this.formRoleId);
                        this.$wire.set('formBranchId', this.formBranchId);
                        this.$wire.set('position', this.position);
                        this.$wire.set('addr_region', this.addr_region);
                        this.$wire.set('addr_province', this.addr_province);
                        this.$wire.set('addr_city', this.addr_city);
                        this.$wire.set('addr_barangay', this.addr_barangay);
                        this.$wire.set('addr_street', this.addr_street);
                        this.$wire.set('addr_lat', this.addr_lat);
                        this.$wire.set('addr_lng', this.addr_lng);
                    },

                    async submitSave() {
                        this.syncToLivewire();
                        if (this.$wire) {
                            await this.$wire.runPreSaveValidation();
                        }
                    },

                    filtered(type) {
                        const s = this.loc[type];
                        if (!s) return [];
                        const q = s.search.toLowerCase();
                        return q ? s.items.filter(i => i.name.toLowerCase().includes(q)) : s.items;
                    },

                    async fetchWithRetry(url, retries = 2, delay = 1000) {
                        try {
                            const cached = sessionStorage.getItem(url);
                            if (cached) return JSON.parse(cached);
                        } catch (e) { console.error('Cache read failed:', e); }

                        for (let i = 0; i <= retries; i++) {
                            try {
                                const res = await fetch(url);
                                if (!res.ok) throw new Error('HTTP error! status: ' + res.status);
                                const data = await res.json();
                                try { sessionStorage.setItem(url, JSON.stringify(data)); } catch (e) {}
                                return data;
                            } catch (e) {
                                if (i === retries) throw e;
                                await new Promise(resolve => setTimeout(resolve, delay * (i + 1)));
                            }
                        }
                    },

                    async loadRegions() {
                        if (this.loc.region.items.length > 0) return;
                        this.loc.region.loading = true;
                        try {
                            const data = await this.fetchWithRetry('https://psgc.cloud/api/regions');
                            this.loc.region.items = data.sort((a, b) => a.name.localeCompare(b.name));
                        } catch (e) {
                            console.error('Regions fetch failed', e);
                            this.dispatchNotification('error', 'Failed to load regions. Please check your connection.');
                        } finally { this.loc.region.loading = false; }
                    },

                    async loadProvinces(regionCode) {
                        this.loc.province.items = []; this.loc.city.items = []; this.loc.barangay.items = [];
                        if (!regionCode) return;
                        this.loc.province.loading = true;
                        try {
                            const data = await this.fetchWithRetry('https://psgc.cloud/api/regions/' + regionCode + '/provinces');
                            this.loc.province.items = data.sort((a, b) => a.name.localeCompare(b.name));
                            this.loc.noProvince = this.loc.province.items.length === 0;
                            if (this.loc.noProvince) {
                                this.loc.province.loading = false;
                                this.loc.city.loading = true;
                                const data2 = await this.fetchWithRetry('https://psgc.cloud/api/regions/' + regionCode + '/cities-municipalities');
                                this.loc.city.items = data2.sort((a, b) => a.name.localeCompare(b.name));
                                this.loc.city.loading = false;
                            }
                        } catch (e) {
                            console.error('Provinces fetch failed', e);
                            this.dispatchNotification('error', 'Failed to load provinces.');
                        } finally { this.loc.province.loading = false; }
                    },

                    async loadCities(provinceCode) {
                        this.loc.city.items = []; this.loc.barangay.items = [];
                        if (!provinceCode) return;
                        this.loc.city.loading = true;
                        try {
                            const data = await this.fetchWithRetry('https://psgc.cloud/api/provinces/' + provinceCode + '/cities-municipalities');
                            this.loc.city.items = data.sort((a, b) => a.name.localeCompare(b.name));
                        } catch (e) {
                            console.error('Cities fetch failed', e);
                            this.dispatchNotification('error', 'Failed to load cities.');
                        } finally { this.loc.city.loading = false; }
                    },

                    async loadBarangays(cityCode) {
                        this.loc.barangay.items = [];
                        if (!cityCode) return;
                        this.loc.barangay.loading = true;
                        try {
                            const data = await this.fetchWithRetry('https://psgc.cloud/api/cities-municipalities/' + cityCode + '/barangays');
                            this.loc.barangay.items = data.sort((a, b) => a.name.localeCompare(b.name));
                        } catch (e) {
                            console.error('Barangays fetch failed', e);
                            this.dispatchNotification('error', 'Failed to load barangays.');
                        } finally { this.loc.barangay.loading = false; }
                    },

                    dispatchNotification(type, message) {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { type, message } }));
                    },

                    async selectRegion(region, fromMap = false) {
                        this.addr_region = region.name;
                        this.addr_province = ''; this.addr_city = ''; this.addr_barangay = '';
                        if (!fromMap) this.geocodeAddress();
                        await this.loadProvinces(region.code);
                    },
                    async selectProvince(province, fromMap = false) {
                        this.addr_province = province.name;
                        this.addr_city = ''; this.addr_barangay = '';
                        if (!fromMap) this.geocodeAddress();
                        await this.loadCities(province.code);
                    },
                    async selectCity(city, fromMap = false) {
                        this.addr_city = city.name;
                        this.addr_barangay = '';
                        if (!fromMap) this.geocodeAddress();
                        await this.loadBarangays(city.code);
                    },
                    selectBarangay(brgy, fromMap = false) {
                        this.addr_barangay = brgy.name;
                        if (!fromMap) this.geocodeAddress();
                    },

                    async geocodeAddress() {
                        const parts = [];
                        if (this.addr_barangay) parts.push(this.addr_barangay);
                        if (this.addr_city)     parts.push(this.addr_city);
                        if (this.addr_province) parts.push(this.addr_province);
                        if (this.addr_region)   parts.push(this.addr_region);
                        if (parts.length === 0) return;
                        const query = parts.join(', ') + ', Philippines';
                        try {
                            const response = await fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(query) + '&limit=1&countrycodes=ph');
                            const data = await response.json();
                            if (data && data.length > 0) {
                                const lat = parseFloat(data[0].lat);
                                const lng = parseFloat(data[0].lon);
                                this.addr_lat = lat;
                                this.addr_lng = lng;
                                if (this.map) {
                                    let zoom = 11;
                                    if (this.addr_barangay) zoom = 16;
                                    else if (this.addr_city) zoom = 14;
                                    else if (this.addr_province) zoom = 12;
                                    this.map.setView([lat, lng], zoom);
                                    if (this.marker) this.marker.setLatLng([lat, lng]);
                                    else this.marker = L.marker([lat, lng], { icon: this.getCustomPinIcon() }).addTo(this.map);
                                }
                            }
                        } catch (e) { console.error('Forward geocoding failed:', e); }
                    },

                    async autoMatchLocation(addr) {
                        const clean = (str) => str ? str.toLowerCase().replace(/city of|province of|region|district|barangay|brgy\.?|municipality of/g,'').replace(/[^a-z0-9]/g,'').trim() : '';
                        let rName = addr.region || '', pName = addr.state || addr.province || addr.county || '';
                        let cName = addr.city || addr.town || addr.municipality || '';
                        let bName = addr.quarter || addr.village || addr.suburb || addr.neighbourhood || '';
                        if (this.loc.region.items.length === 0) await this.loadRegions();
                        let crName = clean(rName), cpName = clean(pName);
                        let matchedR = this.loc.region.items.find(r => {
                            const t = clean(r.name);
                            return t === crName || (crName.includes('manila') && t.includes('ncr')) || (cpName.includes('manila') && t.includes('ncr'));
                        });
                        if (!matchedR && crName) matchedR = this.loc.region.items.find(r => clean(r.name).includes(crName) || crName.includes(clean(r.name)));
                        if (!matchedR) return;
                        await this.selectRegion(matchedR, true);
                        if (pName && this.loc.province.items.length > 0) {
                            let cpp = clean(pName);
                            let mP = this.loc.province.items.find(p => clean(p.name) === cpp) ||
                                     this.loc.province.items.find(p => { const t = clean(p.name).replace('province',''); const s = cpp.replace('province',''); return t && s && (t===s||t.includes(s)||s.includes(t)); });
                            if (mP) await this.selectProvince(mP, true);
                        }
                        if (cName && this.loc.city.items.length > 0) {
                            let cc = clean(cName);
                            let mC = this.loc.city.items.find(c => clean(c.name) === cc) ||
                                     this.loc.city.items.find(c => { const t = clean(c.name).replace('city','').replace('municipality','').replace('city of',''); const s = cc.replace('city','').replace('municipality','').replace('city of',''); return t && s && (t===s||t.includes(s)||s.includes(t)); });
                            if (mC) await this.selectCity(mC, true);
                        }
                        if (bName && this.loc.barangay.items.length > 0) {
                            let cb = clean(bName);
                            let mB = this.loc.barangay.items.find(b => clean(b.name) === cb) ||
                                     this.loc.barangay.items.find(b => { const t = clean(b.name).replace('barangay','').replace('brgy','').replace('poblacion','').replace('pob',''); const s = cb.replace('barangay','').replace('brgy','').replace('poblacion','').replace('pob',''); return t && s && (t===s||t.includes(s)||s.includes(t)); });
                            if (mB) this.selectBarangay(mB, true);
                        }
                    },

                    map: null, marker: null, mapTimeout: null,

                    patchLeaflet() {
                        if (typeof L === 'undefined' || L._patched) return;
                        L._patched = true;
                        ['_resetGrid','_setView','_update','_resetView'].forEach(fn => {
                            const orig = L.GridLayer.prototype[fn];
                            if (orig) L.GridLayer.prototype[fn] = function() { if (!this._map) return; return orig.apply(this, arguments); };
                        });
                    },

                    initMap() {
                        if (typeof L === 'undefined') { if (this.mapTimeout) clearTimeout(this.mapTimeout); this.mapTimeout = setTimeout(() => this.initMap(), 100); return; }
                        this.patchLeaflet();
                        if (this.map) {
                            if (this.mapTimeout) clearTimeout(this.mapTimeout);
                            this.mapTimeout = setTimeout(() => {
                                const container = document.getElementById('userMap');
                                if (!container) return;
                                this.map.invalidateSize();
                                const rLat = parseFloat(this.addr_lat), rLng = parseFloat(this.addr_lng);
                                const hasCoords = !isNaN(rLat) && !isNaN(rLng) && rLat !== 0 && rLng !== 0;
                                if (hasCoords) {
                                    this.map.setView([rLat, rLng], 16);
                                    if (this.marker) this.marker.setLatLng([rLat, rLng]);
                                    else this.marker = L.marker([rLat, rLng], { icon: this.getCustomPinIcon() }).addTo(this.map);
                                }
                                setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 250);
                                setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 500);
                            }, 50);
                            return;
                        }
                        if (this.mapTimeout) clearTimeout(this.mapTimeout);
                        this.mapTimeout = setTimeout(() => {
                            let container = document.getElementById('userMap');
                            if (!container) return;
                            if (container._leaflet_id) { const c = container.cloneNode(false); c.removeAttribute('class'); container.parentNode.replaceChild(c, container); container = c; }
                            const rLat = parseFloat(this.addr_lat), rLng = parseFloat(this.addr_lng);
                            const hasCoords = !isNaN(rLat) && !isNaN(rLng) && rLat !== 0 && rLng !== 0;
                            const centerLat = hasCoords ? rLat : 14.2189;
                            const centerLng = hasCoords ? rLng : 121.1672;
                            const zoom = hasCoords ? 15 : 11;
                            const street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, minZoom: 5, attribution: '© OpenStreetMap' });
                            const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles &copy; Esri' });
                            this.map = L.map(container, { layers: [street] }).setView([centerLat, centerLng], zoom);
                            L.control.layers({ 'Street': street, 'Satellite': satellite }).addTo(this.map);
                            if (hasCoords) this.marker = L.marker([rLat, rLng], { icon: this.getCustomPinIcon() }).addTo(this.map);
                            this.map.on('click', async (e) => {
                                const lt = e.latlng.lat, ln = e.latlng.lng;
                                if (this.marker) this.marker.setLatLng(e.latlng);
                                else this.marker = L.marker(e.latlng, { icon: this.getCustomPinIcon() }).addTo(this.map);
                                this.addr_lat = lt; this.addr_lng = ln;
                                try {
                                    const r = await fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lt + '&lon=' + ln + '&countrycodes=ph');
                                    const d = await r.json();
                                    if (d && d.address) {
                                        const st = (d.address.house_number || '') + ' ' + (d.address.road || d.address.pedestrian || '');
                                        if (st.trim()) this.addr_street = st.trim();
                                        await this.autoMatchLocation(d.address);
                                    }
                                } catch (err) { console.error(err); }
                            });
                            setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 250);
                            setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 500);
                        }, 50);
                    },

                    async initializeExistingAddress() {
                        if (this.loc.region.items.length === 0) await this.loadRegions();
                        if (!this.addr_region) return;
                        const region = this.loc.region.items.find(r => r.name === this.addr_region);
                        if (!region) return;
                        try {
                            const data = await this.fetchWithRetry('https://psgc.cloud/api/regions/' + region.code + '/provinces');
                            this.loc.province.items = data.sort((a, b) => a.name.localeCompare(b.name));
                            if (this.loc.province.items.length === 0) {
                                this.loc.noProvince = true;
                                const d2 = await this.fetchWithRetry('https://psgc.cloud/api/regions/' + region.code + '/cities-municipalities');
                                this.loc.city.items = d2.sort((a, b) => a.name.localeCompare(b.name));
                            }
                        } catch (e) { console.error('Preload provinces failed', e); }
                        if (this.addr_province && this.loc.province.items.length > 0) {
                            const prov = this.loc.province.items.find(p => p.name === this.addr_province);
                            if (prov) try {
                                const d = await this.fetchWithRetry('https://psgc.cloud/api/provinces/' + prov.code + '/cities-municipalities');
                                this.loc.city.items = d.sort((a, b) => a.name.localeCompare(b.name));
                            } catch (e) { console.error('Preload cities failed', e); }
                        }
                        if (this.addr_city && this.loc.city.items.length > 0) {
                            const city = this.loc.city.items.find(c => c.name === this.addr_city);
                            if (city) try {
                                const d = await this.fetchWithRetry('https://psgc.cloud/api/cities-municipalities/' + city.code + '/barangays');
                                this.loc.barangay.items = d.sort((a, b) => a.name.localeCompare(b.name));
                            } catch (e) { console.error('Preload barangays failed', e); }
                        }
                    },

                    init() {
                        // Ensure store values are primitives (purge any stale proxy objects)
                        const s = Alpine.store(STORE_KEY);
                        if (s) {
                            if (typeof s.panel !== 'string') s.panel = initialPanel || 'list';
                            if (typeof s.mode !== 'string') s.mode = initialMode || 'list';
                            ['editUserId','formRoleId','formBranchId','position','addr_street','addr_region',
                             'addr_province','addr_city','addr_barangay','addr_lat','addr_lng'].forEach(k => {
                                if (s[k] !== null && typeof s[k] === 'object') s[k] = null;
                            });
                        }

                        // Form fields read/write $wire directly via the getters above,
                        // so no entangle() wiring is needed here.
                        const $wire = this.$wire;

                        // ── Sliding tabs ──
                        if (typeof slidingTabs === 'function') {
                            const tabsState = slidingTabs(
                                { activeTab: $wire.entangle('activeTab').live, historyTab: @js($historyTab) },
                                ['activeTab', 'historyTab']
                            );
                            if (tabsState.init) tabsState.init.call(this);
                            if (tabsState.updateIndicator) this.updateIndicator = tabsState.updateIndicator.bind(this);
                            if (tabsState.syncToLivewire)  this.syncToLivewire  = tabsState.syncToLivewire.bind(this);
                        }

                        this.loadRegions();
                        this.initializeExistingAddress();

                        this.$watch('editUserId', (v) => { if (v && (this.mode==='edit'||this.mode==='view')) { this.loc.province.items=[]; this.loc.city.items=[]; this.loc.barangay.items=[]; this.loc.noProvince=false; this.initializeExistingAddress(); } });

                        this.$watch('mode', (val) => {
                            if (val === 'view') { this.$nextTick(() => { setTimeout(() => this.updateIndicator('historyTab'), 50); setTimeout(() => this.updateIndicator('historyTab'), 250); }); }
                            if (val === 'create') { this.loc.province.items=[]; this.loc.city.items=[]; this.loc.barangay.items=[]; this.loc.noProvince=false; }
                            else if (val === 'edit' || val === 'view') {
                                this.loc.province.items=[]; this.loc.city.items=[]; this.loc.barangay.items=[]; this.loc.noProvince=false; this.initializeExistingAddress();
                                if (val === 'edit') { this.$nextTick(() => { if (typeof this.initMap === 'function') this.initMap(); }); }
                            }
                        });

                        this.$watch('panel', (val) => {
                            if (val === 'form') { this.$nextTick(() => { setTimeout(() => this.updateIndicator('historyTab'), 50); setTimeout(() => this.updateIndicator('historyTab'), 250); }); }
                            else { this.$nextTick(() => { setTimeout(() => this.updateIndicator('activeTab'), 50); setTimeout(() => this.updateIndicator('activeTab'), 200); setTimeout(() => this.updateIndicator('activeTab'), 350); }); if (this.marker && this.map && this.mode === 'create') { this.map.removeLayer(this.marker); this.marker = null; } }
                        });

                        this.$watch('tableView', (val) => { if ($wire && $wire.get('view') !== val) $wire.set('view', val); });

                        window.addEventListener('notify', () => this.invalidateUserCache());

                        setTimeout(() => { this.updateIndicator('activeTab'); if (this.panel === 'form') this.updateIndicator('historyTab'); }, 50);
                        setTimeout(() => this.updateIndicator('activeTab'), 250);
                    }
                };
            });
        };

        if (window.Alpine) registerUserData();
        else document.addEventListener('alpine:init', registerUserData);
    })();
    </script>
</div>
