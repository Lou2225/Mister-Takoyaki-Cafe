<div
    x-data="userManagementData($wire, @js($panel), @js($mode), @js($view), @js($roles->pluck('name', 'id')), @js($branches->pluck('branch_name', 'id')), @js($activeTab))"
    @trigger-edit.window="if ($event.detail.mode === 'view') { openViewProfile($event.detail.id); } else { $wire.showEdit($event.detail.id, $event.detail.mode || 'edit'); }"
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
        const registerUserData = () => {
            if (!window.Alpine) return;
            if (Alpine.data('userManagementData')) return;

            Alpine.data('userManagementData', ($wire, initialPanel, initialMode, initialView, rolesMap, branchesMap, initialActiveTab) => {
                const tabsState = (typeof slidingTabs === 'function')
                    ? slidingTabs({ activeTab: $wire.entangle('activeTab').live, historyTab: @js($historyTab) }, ['activeTab', 'historyTab'])
                    : { init() {} };

                return {
                    ...tabsState,
                    activeTab: $wire.entangle('activeTab').live,
                    historyTab: @js($historyTab),
                    archivedView: 'table',
                    panel: $wire.entangle('panel'),
                    mode: $wire.entangle('mode'),
                    tableView: initialView || 'table',

                    // ── View Profile loading state ──
                    loadingProfileId: null,

                    // ── Entangled Form State (0ms deferred sync) ──
                    editUserId: $wire.entangle('editUserId'),
                    formRoleId: $wire.entangle('formRoleId'),
                    formBranchId: $wire.entangle('formBranchId'),
                    position: $wire.entangle('position'),
                    addr_street: $wire.entangle('addr_street'),
                    addr_region: $wire.entangle('addr_region'),
                    addr_province: $wire.entangle('addr_province'),
                    addr_city: $wire.entangle('addr_city'),
                    addr_barangay: $wire.entangle('addr_barangay'),
                    addr_lat: $wire.entangle('addr_lat'),
                    addr_lng: $wire.entangle('addr_lng'),

                    rolesMap: rolesMap || {},
                    branchesMap: branchesMap || {},

                    // ── Location state ──
                    loc: {
                        noProvince: false,
                        region:   { items: [], search: '', loading: false },
                        province: { items: [], search: '', loading: false },
                        city:     { items: [], search: '', loading: false },
                        barangay: { items: [], search: '', loading: false }
                    },

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
                        this.loadingProfileId = userId;
                        try {
                            await this.$wire.showEdit(userId, 'view');
                            this.panel = 'form';
                            this.mode = 'view';
                            this.$nextTick(() => {
                                setTimeout(() => this.updateIndicator('historyTab'), 50);
                                setTimeout(() => this.updateIndicator('historyTab'), 200);
                            });
                        } finally {
                            this.loadingProfileId = null;
                        }
                    },

                    filtered(type) {
                        const s = this.loc[type];
                        const q = s.search.toLowerCase();
                        return q ? s.items.filter(i => i.name.toLowerCase().includes(q)) : s.items;
                    },

                    // ── PSGC loaders ──
                    async fetchWithRetry(url, retries = 2, delay = 1000) {
                        try {
                            const cached = sessionStorage.getItem(url);
                            if (cached) return JSON.parse(cached);
                        } catch (e) { console.error('Cache read failed:', e); }

                        for (let i = 0; i <= retries; i++) {
                            try {
                                const res = await fetch(url);
                                if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                                const data = await res.json();
                                try {
                                    sessionStorage.setItem(url, JSON.stringify(data));
                                } catch (e) { console.error('Cache write failed:', e); }
                                return data;
                            } catch (e) {
                                if (i === retries) throw e;
                                console.warn(`Fetch failed for ${url}, retrying (${i + 1}/${retries})...`, e);
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
                        }
                        finally { this.loc.region.loading = false; }
                    },

                    async loadProvinces(regionCode) {
                        this.loc.province.items = []; this.loc.city.items = []; this.loc.barangay.items = [];
                        if (!regionCode) return;
                        this.loc.province.loading = true;
                        try {
                            const data = await this.fetchWithRetry(`https://psgc.cloud/api/regions/${regionCode}/provinces`);
                            this.loc.province.items = data.sort((a, b) => a.name.localeCompare(b.name));
                            
                            this.loc.noProvince = this.loc.province.items.length === 0;

                            if (this.loc.noProvince) {
                                this.loc.province.loading = false;
                                this.loc.city.loading = true;
                                const data2 = await this.fetchWithRetry(`https://psgc.cloud/api/regions/${regionCode}/cities-municipalities`);
                                this.loc.city.items = data2.sort((a, b) => a.name.localeCompare(b.name));
                                this.loc.city.loading = false;
                            }
                        } catch (e) { 
                            console.error('Provinces fetch failed', e);
                            this.dispatchNotification('error', 'Failed to load provinces.');
                        }
                        finally { this.loc.province.loading = false; }
                    },

                    async loadCities(provinceCode) {
                        this.loc.city.items = []; this.loc.barangay.items = [];
                        if (!provinceCode) return;
                        this.loc.city.loading = true;
                        try {
                            const data = await this.fetchWithRetry(`https://psgc.cloud/api/provinces/${provinceCode}/cities-municipalities`);
                            this.loc.city.items = data.sort((a, b) => a.name.localeCompare(b.name));
                        } catch (e) { 
                            console.error('Cities fetch failed', e);
                            this.dispatchNotification('error', 'Failed to load cities.');
                        }
                        finally { this.loc.city.loading = false; }
                    },

                    async loadBarangays(cityCode) {
                        this.loc.barangay.items = [];
                        if (!cityCode) return;
                        this.loc.barangay.loading = true;
                        try {
                            const data = await this.fetchWithRetry(`https://psgc.cloud/api/cities-municipalities/${cityCode}/barangays`);
                            this.loc.barangay.items = data.sort((a, b) => a.name.localeCompare(b.name));
                        } catch (e) { 
                            console.error('Barangays fetch failed', e);
                            this.dispatchNotification('error', 'Failed to load barangays.');
                        }
                        finally { this.loc.barangay.loading = false; }
                    },

                    dispatchNotification(type, message) {
                        window.dispatchEvent(new CustomEvent('notify', {
                            detail: { type, message }
                        }));
                    },

                    // ── Cascade handlers ──
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
                        if (this.addr_city) parts.push(this.addr_city);
                        if (this.addr_province) parts.push(this.addr_province);
                        if (this.addr_region) parts.push(this.addr_region);
                        
                        if (parts.length === 0) return;
                        
                        const query = parts.join(', ') + ', Philippines';
                        try {
                            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&countrycodes=ph`);
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
                                    if (this.marker) {
                                        this.marker.setLatLng([lat, lng]);
                                    } else {
                                        this.marker = L.marker([lat, lng], { icon: this.getCustomPinIcon() }).addTo(this.map);
                                    }
                                }
                            }
                        } catch (e) {
                            console.error('Forward geocoding failed:', e);
                        }
                    },

                    async autoMatchLocation(addr) {
                        const clean = (str) => {
                            if (!str) return '';
                            return str.toLowerCase()
                                .replace(/city of|province of|region|district|barangay|brgy\.?|municipality of/g, '')
                                .replace(/[^a-z0-9]/g, '')
                                .trim();
                        };

                        let rName = addr.region || '';
                        let pName = addr.state || addr.province || addr.county || '';
                        let cName = addr.city || addr.town || addr.municipality || '';
                        let bName = addr.quarter || addr.village || addr.suburb || addr.neighbourhood || '';

                        if (this.loc.region.items.length === 0) await this.loadRegions();

                        let crName = clean(rName);
                        let cpName = clean(pName);
                        let matchedR = this.loc.region.items.find(r => {
                            const target = clean(r.name);
                            return target === crName || 
                                   (crName.includes('manila') && target.includes('ncr')) ||
                                   (cpName.includes('manila') && target.includes('ncr'));
                        });

                        if (!matchedR && crName) {
                            matchedR = this.loc.region.items.find(r => clean(r.name).includes(crName) || crName.includes(clean(r.name)));
                        }

                        if (!matchedR) return;
                        await this.selectRegion(matchedR, true);

                        if (pName && this.loc.province.items.length > 0) {
                            let cppName = clean(pName);
                            let matchedP = this.loc.province.items.find(p => clean(p.name) === cppName);
                            if (!matchedP) {
                                matchedP = this.loc.province.items.find(p => {
                                    const target = clean(p.name).replace('province', '');
                                    const search = cppName.replace('province', '');
                                    return target && search && (target === search || target.includes(search) || search.includes(target));
                                });
                            }
                            if (matchedP) await this.selectProvince(matchedP, true);
                        }

                        if (cName && this.loc.city.items.length > 0) {
                            let ccName = clean(cName);
                            let matchedC = this.loc.city.items.find(c => clean(c.name) === ccName);
                            if (!matchedC) {
                                matchedC = this.loc.city.items.find(c => {
                                    const target = clean(c.name).replace('city', '').replace('municipality', '').replace('city of', '');
                                    const search = ccName.replace('city', '').replace('municipality', '').replace('city of', '');
                                    return target && search && (target === search || target.includes(search) || search.includes(target));
                                });
                            }
                            if (matchedC) await this.selectCity(matchedC, true);
                        }

                        if (bName && this.loc.barangay.items.length > 0) {
                            let cbName = clean(bName);
                            let matchedB = this.loc.barangay.items.find(b => clean(b.name) === cbName);
                            if (!matchedB) {
                                matchedB = this.loc.barangay.items.find(b => {
                                    const target = clean(b.name).replace('barangay', '').replace('brgy', '').replace('poblacion', '').replace('pob', '');
                                    const search = cbName.replace('barangay', '').replace('brgy', '').replace('poblacion', '').replace('pob', '');
                                    return target && search && (target === search || target.includes(search) || search.includes(target));
                                });
                            }
                            if (matchedB) this.selectBarangay(matchedB, true);
                        }
                    },

                    map: null,
                    marker: null,
                    mapTimeout: null,

                    patchLeaflet() {
                        if (typeof L === 'undefined' || L._patched) return;
                        L._patched = true;

                        const originalResetGrid = L.GridLayer.prototype._resetGrid;
                        if (originalResetGrid) {
                            L.GridLayer.prototype._resetGrid = function() {
                                if (!this._map) return;
                                return originalResetGrid.apply(this, arguments);
                            };
                        }

                        const originalSetView = L.GridLayer.prototype._setView;
                        if (originalSetView) {
                            L.GridLayer.prototype._setView = function() {
                                if (!this._map) return;
                                return originalSetView.apply(this, arguments);
                            };
                        }

                        const originalUpdate = L.GridLayer.prototype._update;
                        if (originalUpdate) {
                            L.GridLayer.prototype._update = function() {
                                if (!this._map) return;
                                return originalUpdate.apply(this, arguments);
                            };
                        }

                        const originalResetView = L.GridLayer.prototype._resetView;
                        if (originalResetView) {
                            L.GridLayer.prototype._resetView = function() {
                                if (!this._map) return;
                                return originalResetView.apply(this, arguments);
                            };
                        }
                    },

                    initMap() {
                        if (typeof L === 'undefined') {
                            if (this.mapTimeout) clearTimeout(this.mapTimeout);
                            this.mapTimeout = setTimeout(() => this.initMap(), 100);
                            return;
                        }
                        this.patchLeaflet();
                        if (this.map) {
                            if (this.mapTimeout) clearTimeout(this.mapTimeout);
                            this.mapTimeout = setTimeout(() => {
                                const container = document.getElementById('userMap');
                                if (!container) return;
                                this.map.invalidateSize(); 
                                let lat = this.addr_lat;
                                let lng = this.addr_lng;
                                if (lat && lng) {
                                    this.map.setView([lat, lng], 16);
                                    if (this.marker) this.marker.setLatLng([lat, lng]);
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

                            if (container._leaflet_id) {
                                const clone = container.cloneNode(false);
                                clone.removeAttribute('class');
                                container.parentNode.replaceChild(clone, container);
                                container = clone;
                            }

                            let lat = this.addr_lat;
                            let lng = this.addr_lng;
                            let startLat = lat || 14.2189;
                            let startLng = lng || 121.1672;
                            let startZoom = lat ? 15 : 11;
                            
                            const street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                maxZoom: 19,
                                minZoom: 10,
                                attribution: '© OpenStreetMap'
                            });
                            const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                                attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
                            });

                            const lagunaBounds = L.latLngBounds([13.9, 120.9], [14.5, 121.6]);

                            this.map = L.map(container, {
                                maxBounds: lagunaBounds,
                                maxBoundsViscosity: 1.0,
                                layers: [street]
                            }).setView([startLat, startLng], startZoom);

                            L.control.layers({ 'Street': street, 'Satellite': satellite }).addTo(this.map);                        
                            if (lat && lng) {
                                this.marker = L.marker([lat, lng], { icon: this.getCustomPinIcon() }).addTo(this.map);
                            }

                            this.map.on('click', async (e) => {
                                const lat = e.latlng.lat;
                                const lng = e.latlng.lng;
                                if (this.marker) this.marker.setLatLng(e.latlng);
                                else this.marker = L.marker(e.latlng, { icon: this.getCustomPinIcon() }).addTo(this.map);
                                
                                this.addr_lat = lat;
                                this.addr_lng = lng;
                                
                                try {
                                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&countrycodes=ph`);
                                    const data = await response.json();
                                    if (data && data.address) {
                                        let st = data.address.road || data.address.pedestrian || '';
                                        let num = data.address.house_number || '';
                                        let fst = (num + ' ' + st).trim();
                                        if(fst) this.addr_street = fst;
                                        
                                        await this.autoMatchLocation(data.address);
                                    }
                                } catch (error) { console.error(error); }
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
                            const data = await this.fetchWithRetry(`https://psgc.cloud/api/regions/${region.code}/provinces`);
                            this.loc.province.items = data.sort((a, b) => a.name.localeCompare(b.name));
                            if (this.loc.province.items.length === 0) {
                                this.loc.noProvince = true;
                                const data2 = await this.fetchWithRetry(`https://psgc.cloud/api/regions/${region.code}/cities-municipalities`);
                                this.loc.city.items = data2.sort((a, b) => a.name.localeCompare(b.name));
                            }
                        } catch (e) { console.error('Preload provinces failed', e); }

                        if (this.addr_province && this.loc.province.items.length > 0) {
                            const province = this.loc.province.items.find(p => p.name === this.addr_province);
                            if (province) {
                                try {
                                    const data = await this.fetchWithRetry(`https://psgc.cloud/api/provinces/${province.code}/cities-municipalities`);
                                    this.loc.city.items = data.sort((a, b) => a.name.localeCompare(b.name));
                                } catch (e) { console.error('Preload cities failed', e); }
                            }
                        }

                        if (this.addr_city && this.loc.city.items.length > 0) {
                            const city = this.loc.city.items.find(c => c.name === this.addr_city);
                            if (city) {
                                try {
                                    const data = await this.fetchWithRetry(`https://psgc.cloud/api/cities-municipalities/${city.code}/barangays`);
                                    this.loc.barangay.items = data.sort((a, b) => a.name.localeCompare(b.name));
                                } catch (e) { console.error('Preload barangays failed', e); }
                            }
                        }
                    },

                    init() {
                        if (tabsState.init) tabsState.init.call(this);

                        this.loadRegions();
                        this.initializeExistingAddress();

                        this.$watch('editUserId', (val) => {
                            if (val && (this.mode === 'edit' || this.mode === 'view')) {
                                this.loc.province.items = [];
                                this.loc.city.items     = [];
                                this.loc.barangay.items = [];
                                this.loc.noProvince     = false;
                                this.initializeExistingAddress();
                            }
                        });

                        this.$watch('mode', (val) => {
                            if (val === 'view') {
                                this.$nextTick(() => {
                                    setTimeout(() => this.updateIndicator('historyTab'), 50);
                                    setTimeout(() => this.updateIndicator('historyTab'), 250);
                                });
                            }
                            if (val === 'create') {
                                this.loc.province.items = [];
                                this.loc.city.items     = [];
                                this.loc.barangay.items = [];
                                this.loc.noProvince     = false;
                            } else if (val === 'edit' || val === 'view') {
                                this.loc.province.items = [];
                                this.loc.city.items     = [];
                                this.loc.barangay.items = [];
                                this.loc.noProvince     = false;
                                this.initializeExistingAddress();
                            }
                        });

                        this.$watch('panel', (val) => {
                            if (val === 'form') {
                                this.$nextTick(() => {
                                    setTimeout(() => this.updateIndicator('historyTab'), 50);
                                    setTimeout(() => this.updateIndicator('historyTab'), 250);
                                });
                            } else {
                                this.$nextTick(() => {
                                    setTimeout(() => this.updateIndicator('activeTab'), 50);
                                    setTimeout(() => this.updateIndicator('activeTab'), 200);
                                    setTimeout(() => this.updateIndicator('activeTab'), 350);
                                });
                                if (this.marker && this.map && this.mode === 'create') {
                                    this.map.removeLayer(this.marker);
                                    this.marker = null;
                                }
                            }
                        });

                        this.$watch('tableView', (val) => {
                            if (this.$wire && this.$wire.get('view') !== val) {
                                this.$wire.set('view', val);
                            }
                        });

                        // Invalidate cache if a user mutation occurs
                        window.addEventListener('notify', () => {
                            this.invalidateUserCache();
                        });

                        // Initial indicator positioning
                        setTimeout(() => {
                            this.updateIndicator('activeTab');
                            if (this.panel === 'form') {
                                this.updateIndicator('historyTab');
                            }
                        }, 50);
                        setTimeout(() => {
                            this.updateIndicator('activeTab');
                        }, 250);
                    }
                };
            });
        };

        if (window.Alpine) registerUserData();
        else document.addEventListener('alpine:init', registerUserData);
    })();
    </script>
</div>
