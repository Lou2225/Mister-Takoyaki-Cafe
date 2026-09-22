<div class="relative min-h-[600px]" x-data="{ 
    tab: @entangle('tab'),
    strength: 0,
    avatarModal: false,
    activeCategory: 'foods',
    tabWidth: 0,
    tabLeft: 0,
    activeCategoryWidth: 0,
    activeCategoryLeft: 0,

    // ── Address / map picker state (mirrors User Management) ──
    addr_region: $wire.entangle('addr_region'),
    addr_province: $wire.entangle('addr_province'),
    addr_city: $wire.entangle('addr_city'),
    addr_barangay: $wire.entangle('addr_barangay'),
    addr_street: $wire.entangle('addr_street'),
    addr_lat: $wire.entangle('addr_lat'),
    addr_lng: $wire.entangle('addr_lng'),
    loc: {
        noProvince: false,
        region:   { items: [], search: '' },
        province: { items: [], search: '' },
        city:     { items: [], search: '' },
        barangay: { items: [], search: '' }
    },
    map: null,
    marker: null,
    mapTimeout: null,

    checkStrength(pw) {
        let s = 0;
        if (pw.length >= 8) s++;
        if (/[A-Z]/.test(pw)) s++;
        if (/[a-z]/.test(pw)) s++;
        if (/\d/.test(pw)) s++;
        if (/([^A-Za-z0-9])/.test(pw)) s++;
        this.strength = s;
    },
    recalculateTab() {
        this.$nextTick(() => {
            const activeTab = this.$el.querySelector(`[data-tab='${this.tab}']`);
            if (activeTab) { this.tabWidth = activeTab.offsetWidth; this.tabLeft = activeTab.offsetLeft; }
        });
    },
    recalculateActiveCategoryTab() {
        this.$nextTick(() => {
            const activeTab = this.$el.querySelector(`[data-tab='${this.activeCategory}']`);
            if (activeTab) { this.activeCategoryWidth = activeTab.offsetWidth; this.activeCategoryLeft = activeTab.offsetLeft; }
        });
    },

    filtered(type) {
        const s = this.loc[type];
        const q = s.search.toLowerCase();
        return q ? s.items.filter(i => i.name.toLowerCase().includes(q)) : s.items;
    },
    async fetchWithRetry(url, retries = 2, delay = 1000) {
        try { const cached = sessionStorage.getItem(url); if (cached) return JSON.parse(cached); } catch (e) {}
        for (let i = 0; i <= retries; i++) {
            try {
                const res = await fetch(url);
                if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
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
        try {
            const data = await this.fetchWithRetry('https://psgc.cloud/api/regions');
            this.loc.region.items = data.sort((a, b) => a.name.localeCompare(b.name));
        } catch (e) { console.error('Regions fetch failed', e); }
    },
    async loadProvinces(regionCode) {
        this.loc.province.items = []; this.loc.city.items = []; this.loc.barangay.items = [];
        if (!regionCode) return;
        try {
            const data = await this.fetchWithRetry(`https://psgc.cloud/api/regions/${regionCode}/provinces`);
            this.loc.province.items = data.sort((a, b) => a.name.localeCompare(b.name));
            this.loc.noProvince = this.loc.province.items.length === 0;
            if (this.loc.noProvince) {
                const data2 = await this.fetchWithRetry(`https://psgc.cloud/api/regions/${regionCode}/cities-municipalities`);
                this.loc.city.items = data2.sort((a, b) => a.name.localeCompare(b.name));
            }
        } catch (e) { console.error('Provinces fetch failed', e); }
    },
    async loadCities(provinceCode) {
        this.loc.city.items = []; this.loc.barangay.items = [];
        if (!provinceCode) return;
        try {
            const data = await this.fetchWithRetry(`https://psgc.cloud/api/provinces/${provinceCode}/cities-municipalities`);
            this.loc.city.items = data.sort((a, b) => a.name.localeCompare(b.name));
        } catch (e) { console.error('Cities fetch failed', e); }
    },
    async loadBarangays(cityCode) {
        this.loc.barangay.items = [];
        if (!cityCode) return;
        try {
            const data = await this.fetchWithRetry(`https://psgc.cloud/api/cities-municipalities/${cityCode}/barangays`);
            this.loc.barangay.items = data.sort((a, b) => a.name.localeCompare(b.name));
        } catch (e) { console.error('Barangays fetch failed', e); }
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
        if (this.addr_city) parts.push(this.addr_city);
        if (this.addr_province) parts.push(this.addr_province);
        if (this.addr_region) parts.push(this.addr_region);
        if (parts.length === 0) return;
        const query = parts.join(', ') + ', Philippines';
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&countrycodes=ph`);
            const data = await response.json();
            if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat), lng = parseFloat(data[0].lon);
                this.addr_lat = lat; this.addr_lng = lng;
                if (this.map) {
                    let zoom = 11;
                    if (this.addr_barangay) zoom = 16; else if (this.addr_city) zoom = 14; else if (this.addr_province) zoom = 12;
                    this.map.setView([lat, lng], zoom);
                    if (this.marker) this.marker.setLatLng([lat, lng]);
                    else this.marker = L.marker([lat, lng], { icon: this.getCustomPinIcon() }).addTo(this.map);
                }
            }
        } catch (e) { console.error('Forward geocoding failed:', e); }
    },
    async autoMatchLocation(addr) {
        const clean = (str) => !str ? '' : str.toLowerCase()
            .replace(/city of|province of|region|district|barangay|brgy\.?|municipality of/g, '')
            .replace(/[^a-z0-9]/g, '').trim();

        let rName = addr.region || '';
        let pName = addr.state || addr.province || addr.county || '';
        let cName = addr.city || addr.town || addr.municipality || '';
        let bName = addr.quarter || addr.village || addr.suburb || addr.neighbourhood || '';

        if (this.loc.region.items.length === 0) await this.loadRegions();

        let crName = clean(rName), cpName = clean(pName);
        let matchedR = this.loc.region.items.find(r => {
            const target = clean(r.name);
            return target === crName || (crName.includes('manila') && target.includes('ncr')) || (cpName.includes('manila') && target.includes('ncr'));
        });
        if (!matchedR && crName) {
            matchedR = this.loc.region.items.find(r => clean(r.name).includes(crName) || crName.includes(clean(r.name)));
        }
        if (!matchedR) return;
        await this.selectRegion(matchedR, true);

        if (pName && this.loc.province.items.length > 0) {
            let cppName = clean(pName);
            let matchedP = this.loc.province.items.find(p => clean(p.name) === cppName)
                || this.loc.province.items.find(p => {
                    const target = clean(p.name).replace('province', ''), search = cppName.replace('province', '');
                    return target && search && (target === search || target.includes(search) || search.includes(target));
                });
            if (matchedP) await this.selectProvince(matchedP, true);
        }
        if (cName && this.loc.city.items.length > 0) {
            let ccName = clean(cName);
            let matchedC = this.loc.city.items.find(c => clean(c.name) === ccName)
                || this.loc.city.items.find(c => {
                    const target = clean(c.name).replace('city', '').replace('municipality', '').replace('city of', '');
                    const search = ccName.replace('city', '').replace('municipality', '').replace('city of', '');
                    return target && search && (target === search || target.includes(search) || search.includes(target));
                });
            if (matchedC) await this.selectCity(matchedC, true);
        }
        if (bName && this.loc.barangay.items.length > 0) {
            let cbName = clean(bName);
            let matchedB = this.loc.barangay.items.find(b => clean(b.name) === cbName)
                || this.loc.barangay.items.find(b => {
                    const target = clean(b.name).replace('barangay', '').replace('brgy', '').replace('poblacion', '').replace('pob', '');
                    const search = cbName.replace('barangay', '').replace('brgy', '').replace('poblacion', '').replace('pob', '');
                    return target && search && (target === search || target.includes(search) || search.includes(target));
                });
            if (matchedB) this.selectBarangay(matchedB, true);
        }
    },
    getCustomPinIcon() {
        return L.divIcon({
            html: `
                \x3cdiv class='relative flex flex-col items-center justify-end w-10 h-10'\x3e
                    \x3cspan class='absolute w-4 h-2 bg-indigo-500/40 rounded-full blur-[2px] animate-ping bottom-[-2px] left-1/2 -translate-x-1/2'\x3e\x3c/span\x3e
                    \x3cdiv class='relative w-8 h-8 bg-indigo-600 rounded-t-full rounded-bl-full rotate-45 border-2 border-white shadow-lg flex items-center justify-center'\x3e
                        \x3cdiv class='w-3.5 h-3.5 bg-white rounded-full -rotate-45 flex items-center justify-center shadow-inner'\x3e
                            \x3cdiv class='w-1.5 h-1.5 bg-indigo-600 rounded-full'\x3e\x3c/div\x3e
                        \x3c/div\x3e
                    \x3c/div\x3e
                \x3c/div\x3e
            `,
            className: 'custom-leaflet-icon', iconSize: [40, 40], iconAnchor: [20, 40]
        });
    },
    patchLeaflet() {
        if (typeof L === 'undefined' || L._patched) return;
        L._patched = true;
        ['_resetGrid', '_setView', '_update', '_resetView'].forEach(fn => {
            const orig = L.GridLayer.prototype[fn];
            if (orig) L.GridLayer.prototype[fn] = function (...args) { if (!this._map) return; return orig.apply(this, args); };
        });
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
                const container = document.getElementById('profileAddressMap');
                if (!container) return;
                this.map.invalidateSize();
                if (this.addr_lat && this.addr_lng) {
                    this.map.setView([this.addr_lat, this.addr_lng], 16);
                    if (this.marker) this.marker.setLatLng([this.addr_lat, this.addr_lng]);
                }
                setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 250);
                setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 500);
            }, 50);
            return;
        }
        if (this.mapTimeout) clearTimeout(this.mapTimeout);
        this.mapTimeout = setTimeout(() => {
            let container = document.getElementById('profileAddressMap');
            if (!container) return;
            if (container._leaflet_id) {
                const clone = container.cloneNode(false);
                clone.removeAttribute('class');
                container.parentNode.replaceChild(clone, container);
                container = clone;
            }
            let lat = this.addr_lat, lng = this.addr_lng;
            let startLat = lat || 14.2189, startLng = lng || 121.1672, startZoom = lat ? 15 : 11;

            const street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, minZoom: 5, attribution: '© OpenStreetMap' });
            const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles &copy; Esri' });

            this.map = L.map(container, { layers: [street] }).setView([startLat, startLng], startZoom);
            L.control.layers({ 'Street': street, 'Satellite': satellite }).addTo(this.map);
            if (lat && lng) this.marker = L.marker([lat, lng], { icon: this.getCustomPinIcon() }).addTo(this.map);

            this.map.on('click', async (e) => {
                const lat = e.latlng.lat, lng = e.latlng.lng;
                if (this.marker) this.marker.setLatLng(e.latlng);
                else this.marker = L.marker(e.latlng, { icon: this.getCustomPinIcon() }).addTo(this.map);
                this.addr_lat = lat; this.addr_lng = lng;
                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&countrycodes=ph`);
                    const data = await response.json();
                    if (data && data.address) {
                        let st = data.address.road || data.address.pedestrian || '';
                        let num = data.address.house_number || '';
                        let fst = (num + ' ' + st).trim();
                        if (fst) this.addr_street = fst;
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
        } catch (e) {}
        if (this.addr_province && this.loc.province.items.length > 0) {
            const province = this.loc.province.items.find(p => p.name === this.addr_province);
            if (province) {
                try {
                    const data = await this.fetchWithRetry(`https://psgc.cloud/api/provinces/${province.code}/cities-municipalities`);
                    this.loc.city.items = data.sort((a, b) => a.name.localeCompare(b.name));
                } catch (e) {}
            }
        }
        if (this.addr_city && this.loc.city.items.length > 0) {
            const city = this.loc.city.items.find(c => c.name === this.addr_city);
            if (city) {
                try {
                    const data = await this.fetchWithRetry(`https://psgc.cloud/api/cities-municipalities/${city.code}/barangays`);
                    this.loc.barangay.items = data.sort((a, b) => a.name.localeCompare(b.name));
                } catch (e) {}
            }
        }
    },

    init() {
        this.recalculateTab();
        this.$watch('tab', () => this.recalculateTab());
        this.$watch('activeCategory', () => this.recalculateActiveCategoryTab());
        this.$watch('avatarModal', (val) => { if (val) setTimeout(() => this.recalculateActiveCategoryTab(), 100); });
        this.loadRegions();
        this.initializeExistingAddress();
    }
}" @resize.window.debounce.150ms="recalculateTab(); recalculateActiveCategoryTab()"
   @password-updated.window="strength = 0">

    <div class="mb-5 flex items-center justify-between">
        <div>
            <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Profile Settings</h2>
            <p class="text-[12px] text-gray-500 font-medium">Manage your personal information and security settings</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Left: Profile Card --}}
        <div class="col-span-1 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-[0_8px_30px_-12px_rgba(0,0,0,0.05)] p-8 text-center relative overflow-hidden group">
                <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-50 rounded-full blur-3xl opacity-50 group-hover:bg-indigo-100 transition-colors duration-500"></div>
                
                <div class="relative">
                    {{-- Avatar Display --}}
                    <div class="relative mx-auto mb-6 w-24 h-24">
                        @if($avatarDisplay)
                            <div class="w-24 h-24 rounded-2xl flex items-center justify-center text-5xl shadow-xl rotate-3 group-hover:rotate-0 transition-transform duration-500" style="{{ $avatarDisplay['style'] }}">
                                {{ $avatarDisplay['emoji'] }}
                            </div>
                        @else
                            <div class="w-24 h-24 bg-gradient-to-tr from-indigo-600 to-violet-500 text-white rounded-2xl flex items-center justify-center text-3xl font-black shadow-xl shadow-indigo-200 rotate-3 group-hover:rotate-0 transition-transform duration-500">
                                {{ strtoupper(substr(auth()->user()->first_name, 0, 1) . substr(auth()->user()->last_name, 0, 1)) }}
                            </div>
                        @endif
                        {{-- Edit avatar button --}}
                        <button @click="avatarModal = true" type="button"
                            class="absolute -bottom-2 -right-2 w-8 h-8 bg-white border border-slate-200 rounded-full flex items-center justify-center text-slate-500 hover:text-indigo-600 hover:border-indigo-300 shadow-sm transition-all hover:scale-110 focus:outline-none"
title="Change avatar" aria-label="Change avatar">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                        </button>
                    </div>

                    <h3 class="text-xl font-black text-slate-900 tracking-tight">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</h3>
                    <p class="text-[13px] text-slate-500 font-medium mt-1">{{ auth()->user()->email }}</p>
                    
                    @if($avatarDisplay)
                        <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase tracking-widest">{{ $avatarDisplay['label'] }}</p>
                    @endif

                    <div class="mt-8 pt-8 border-t border-slate-100 text-left space-y-4">
                        <div>
                            <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Account ID</span>
                            <span class="inline-block text-[12px] font-black text-slate-700 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-100">#{{ auth()->user()->employee_id ?: 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">System Role</span>
                            <span class="inline-block text-[12px] font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-100">{{ auth()->user()->role->name ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Work Location</span>
                            <span class="block text-[12px] font-bold text-slate-700 leading-snug break-words">{{ auth()->user()->branch->branch_name ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Status</span>
                            @if(auth()->user()->is_active)
                                <span class="inline-flex items-center gap-1.5 text-[10px] font-black text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-100">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                    ACTIVE
                                </span>
                            @else
                                <span class="inline-block text-[10px] font-black text-red-600 bg-red-50 px-2.5 py-1 rounded-full border border-red-100">INACTIVE</span>
                            @endif
                        </div>
                        <div>
                            <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Last Sign-in</span>
                            @php
                                $prevLogin = auth()->user()->previous_login_at
                                    ? \Carbon\Carbon::parse(auth()->user()->previous_login_at)->timezone('Asia/Manila')
                                    : null;
                            @endphp
                            @if($prevLogin)
                                <span class="block text-[12px] font-bold text-slate-700" title="{{ $prevLogin->diffForHumans() }}">{{ $prevLogin->format('M d, Y h:i A') }}</span>
                            @else
                                <span class="block text-[12px] font-medium text-slate-400 italic">Not recorded yet</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Tabs and Content --}}
        <div class="col-span-1 lg:col-span-3">
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm">
                
                <div class="px-6 pt-6 border-b border-slate-100">
                    <x-sliding-tabs model="tab" class="px-0">
                        <x-sliding-tab model="tab" value="profile">
                            <x-slot name="icon">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </x-slot>
                            Personal Information
                        </x-sliding-tab>
                        <x-sliding-tab model="tab" value="security">
                            <x-slot name="icon">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </x-slot>
                            Security & Password
                        </x-sliding-tab>
                    </x-sliding-tabs>
                </div>

                {{-- Personal Information Tab (Address is now part of the same form/save) --}}
                <div x-cloak x-show="tab === 'profile'" class="p-6 animate-fadeIn">
                    <form wire:submit="updateProfile">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div>
                                <x-input-label for="f_first_name" value="First Name *" />
                                <x-text-input id="f_first_name" wire:model.live.debounce.500ms="firstName"
                                    type="text" class="mt-1 block w-full" placeholder="Juan"
                                    autocomplete="given-name" inputFilter="nameStrict" maxlength="100"
                                    @keydown="FormFilters.nameStrictKeydown($event)" @paste="FormFilters.nameStrictPaste($event)"
                                    :hasError="$errors->has('firstName')" />
                                <x-input-error :messages="$errors->get('firstName')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="f_middle_name" value="Middle Name" />
                                <x-text-input id="f_middle_name" wire:model.live.debounce.500ms="middleName"
                                    type="text" class="mt-1 block w-full" placeholder="Dela"
                                    autocomplete="additional-name" inputFilter="nameStrict" maxlength="100"
                                    @keydown="FormFilters.nameStrictKeydown($event)" @paste="FormFilters.nameStrictPaste($event)"
                                    :hasError="$errors->has('middleName')" />
                                <x-input-error :messages="$errors->get('middleName')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="f_last_name" value="Last Name *" />
                                <x-text-input id="f_last_name" wire:model.live.debounce.500ms="lastName" type="text"
                                    class="mt-1 block w-full" placeholder="Cruz" autocomplete="family-name" 
                                    inputFilter="nameStrict" maxlength="100"
                                    @keydown="FormFilters.nameStrictKeydown($event)" @paste="FormFilters.nameStrictPaste($event)"
                                    :hasError="$errors->has('lastName')" />
                                <x-input-error :messages="$errors->get('lastName')" class="mt-1" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
                            <div>
                                <x-input-label for="f_email" value="Email Address *" />
                                <x-text-input id="f_email" wire:model.live.debounce.500ms="email" type="email"
                                    class="mt-1 block w-full" placeholder="juan.cruz@gmail.com"
                                    autocomplete="email" inputFilter="email" maxlength="255"
                                    @keydown="FormFilters.emailKeydown($event)" @paste="FormFilters.emailPaste($event)"
                                    :hasError="$errors->has('email')" />
                                <x-input-error :messages="$errors->get('email')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="f_phone" value="Phone Number" />
                                <div class="flex items-center mt-1">
                                    <div class="flex-shrink-0 inline-flex items-center px-3 h-10 rounded-l-lg border border-r-0 border-gray-200 bg-gray-50 text-gray-500 text-[13px] font-bold">
                                        +63
                                    </div>
                                    <x-text-input id="f_phone" wire:model.live.debounce.500ms="phone" type="text"
                                        class="block w-full rounded-l-none" placeholder="912 345 6789" autocomplete="tel"
                                        inputFilter="number" maxlength="10"
                                        @keydown="FormFilters.numberKeydown($event)" @paste="FormFilters.numberPaste($event)"
                                        :hasError="$errors->has('phone')" />
                                </div>
                                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                            </div>
                        </div>

                        <div x-data x-cloak
                             x-show="$wire.email.trim().toLowerCase() !== @js(strtolower(auth()->user()->email))"
                             x-transition class="mt-6 sm:max-w-sm">
                            <x-input-label for="email_password" value="Current Password (required to change your email)" />
                            <x-text-input id="email_password" type="password" wire:model="emailPassword"
                                class="mt-1 block w-full" autocomplete="current-password"
                                :hasError="$errors->has('emailPassword')" />
                            <x-input-error :messages="$errors->get('emailPassword')" class="mt-1" />
                        </div>

                        {{-- Residential Address — same form, same Save button now --}}
                        <div class="mt-8 pt-8 border-t border-slate-100">
                            <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">Residential Address</h3>
                                    <p class="text-xs text-gray-500 mt-1">Visible to your administrators on your staff profile.</p>
                                </div>
                                <button type="button" @click="$dispatch('open-modal', 'profile-map-modal'); initMap();"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg text-[11px] font-bold hover:bg-indigo-100 transition-all shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    Open Map Picker
                                </button>
                            </div>

                            <div class="flex flex-col gap-5">
                                <div class="flex flex-col sm:flex-row gap-5">
                                    <div class="flex-1 w-full">
                                        <x-input-label value="Region" />
                                        <div class="relative mt-1"
                                            x-data="{
                                                open: false, dropUp: false,
                                                checkFlip() { const r=this.$el.getBoundingClientRect(); this.dropUp=(window.innerHeight-r.bottom)<250 && r.top>250; },
                                                openDropdown() { loadRegions(); this.checkFlip(); this.open = true; loc.region.search=''; },
                                                closeDropdown() { this.open = false; loc.region.search=''; }
                                            }"
                                            @click.outside="closeDropdown()" @keydown.escape.window="closeDropdown()">
                                            <div class="relative flex items-center">
                                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                </div>
                                                <input type="text"
                                                    :value="open ? loc.region.search : (addr_region || '')"
                                                    @input="loc.region.search = $event.target.value; open = true"
                                                    @focus="openDropdown()" @click="openDropdown()"
                                                    placeholder="Search or select region..."
                                                    class="w-full pl-10 pr-10 py-2 bg-white border border-gray-200 rounded-lg text-[13px] shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all h-10"
                                                    autocomplete="off">
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                    <button type="button" x-show="addr_region || loc.region.search" x-cloak
                                                        @click.stop="addr_region=''; loc.region.search=''; open=false;"
                                                        class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div x-show="open" x-cloak x-transition
                                                :class="dropUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                                class="absolute left-0 right-0 z-50 bg-white rounded-xl shadow-xl border border-slate-100 p-1.5 max-h-48 overflow-y-auto custom-scrollbar">
                                                <template x-for="r in filtered('region')" :key="r.code">
                                                    <button type="button" @click="selectRegion(r); open=false;"
                                                        class="w-full text-left px-3.5 py-2.5 rounded-lg hover:bg-indigo-50/70 transition-colors"
                                                        :class="addr_region === r.name ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                                        <span class="text-[13px] font-medium truncate" x-text="r.name"></span>
                                                    </button>
                                                </template>
                                                <template x-if="filtered('region').length === 0">
                                                    <div class="px-4 py-3 text-[12px] text-slate-400 italic text-center">No regions found</div>
                                                </template>
                                            </div>
                                        </div>
                                        <x-input-error :messages="$errors->get('addr_region')" class="mt-1" />
                                    </div>

                                    <div class="flex-1 w-full">
                                        <x-input-label value="Province" />
                                        <div class="relative mt-1"
                                            x-data="{
                                                open: false, dropUp: false,
                                                get isDisabled() { return !addr_region || loc.noProvince; },
                                                checkFlip() { const r=this.$el.getBoundingClientRect(); this.dropUp=(window.innerHeight-r.bottom)<250 && r.top>250; },
                                                openDropdown() { if (this.isDisabled) return; this.checkFlip(); this.open = true; loc.province.search=''; },
                                                closeDropdown() { this.open = false; loc.province.search=''; }
                                            }"
                                            @click.outside="closeDropdown()" @keydown.escape.window="closeDropdown()">
                                            <div class="relative flex items-center">
                                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                </div>
                                                <input type="text" :disabled="isDisabled"
                                                    :value="loc.noProvince ? 'N/A (Direct to City)' : (open ? loc.province.search : (addr_province || ''))"
                                                    @input="loc.province.search = $event.target.value; open = true"
                                                    @focus="openDropdown()" @click="openDropdown()"
                                                    placeholder="Search or select province..."
                                                    class="w-full pl-10 pr-10 py-2 bg-white border border-gray-200 rounded-lg text-[13px] shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all h-10 disabled:bg-gray-50 disabled:opacity-75 disabled:cursor-not-allowed"
                                                    autocomplete="off">
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                    <button type="button" x-show="!isDisabled && (addr_province || loc.province.search)" x-cloak
                                                        @click.stop="addr_province=''; loc.province.search=''; open=false;"
                                                        class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div x-show="open" x-cloak x-transition
                                                :class="dropUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                                class="absolute left-0 right-0 z-50 bg-white rounded-xl shadow-xl border border-slate-100 p-1.5 max-h-48 overflow-y-auto custom-scrollbar">
                                                <template x-for="p in filtered('province')" :key="p.code">
                                                    <button type="button" @click="selectProvince(p); open=false;"
                                                        class="w-full text-left px-3.5 py-2.5 rounded-lg hover:bg-indigo-50/70 transition-colors"
                                                        :class="addr_province === p.name ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                                        <span class="text-[13px] font-medium truncate" x-text="p.name"></span>
                                                    </button>
                                                </template>
                                                <template x-if="filtered('province').length === 0">
                                                    <div class="px-4 py-3 text-[12px] text-slate-400 italic text-center">No provinces found</div>
                                                </template>
                                            </div>
                                        </div>
                                        <x-input-error :messages="$errors->get('addr_province')" class="mt-1" />
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-5">
                                    <div class="flex-1 w-full">
                                        <x-input-label value="City / Municipality *" />
                                        <div class="relative mt-1"
                                            x-data="{
                                                open: false, dropUp: false,
                                                get isDisabled() { return !addr_region || (!addr_province && !loc.noProvince); },
                                                checkFlip() { const r=this.$el.getBoundingClientRect(); this.dropUp=(window.innerHeight-r.bottom)<250 && r.top>250; },
                                                openDropdown() { if (this.isDisabled) return; this.checkFlip(); this.open=true; loc.city.search=''; },
                                                closeDropdown() { this.open=false; loc.city.search=''; }
                                            }"
                                            @click.outside="closeDropdown()" @keydown.escape.window="closeDropdown()">
                                            <div class="relative flex items-center">
                                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                </div>
                                                <input type="text" :disabled="isDisabled"
                                                    :value="open ? loc.city.search : (addr_city || '')"
                                                    @input="loc.city.search = $event.target.value; open = true"
                                                    @focus="openDropdown()" @click="openDropdown()"
                                                    placeholder="Search or select city..."
                                                    class="w-full pl-10 pr-10 py-2 bg-white border border-gray-200 rounded-lg text-[13px] shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all h-10 disabled:bg-gray-50 disabled:opacity-75 disabled:cursor-not-allowed"
                                                    autocomplete="off">
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                    <button type="button" x-show="!isDisabled && (addr_city || loc.city.search)" x-cloak
                                                        @click.stop="addr_city=''; loc.city.search=''; open=false;"
                                                        class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div x-show="open" x-cloak x-transition
                                                :class="dropUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                                class="absolute left-0 right-0 z-50 bg-white rounded-xl shadow-xl border border-slate-100 p-1.5 max-h-48 overflow-y-auto custom-scrollbar">
                                                <template x-for="c in filtered('city')" :key="c.code">
                                                    <button type="button" @click="selectCity(c); open=false;"
                                                        class="w-full text-left px-3.5 py-2.5 rounded-lg hover:bg-indigo-50/70 transition-colors"
                                                        :class="addr_city === c.name ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                                        <span class="text-[13px] font-medium truncate" x-text="c.name"></span>
                                                    </button>
                                                </template>
                                                <template x-if="filtered('city').length === 0">
                                                    <div class="px-4 py-3 text-[12px] text-slate-400 italic text-center">No cities found</div>
                                                </template>
                                            </div>
                                        </div>
                                        <x-input-error :messages="$errors->get('addr_city')" class="mt-1" />
                                    </div>

                                    <div class="flex-1 w-full">
                                        <x-input-label value="Barangay *" />
                                        <div class="relative mt-1"
                                            x-data="{
                                                open: false, dropUp: false,
                                                checkFlip() { const r=this.$el.getBoundingClientRect(); this.dropUp=(window.innerHeight-r.bottom)<250 && r.top>250; },
                                                openDropdown() { if (!addr_city) return; this.checkFlip(); this.open=true; loc.barangay.search=''; },
                                                closeDropdown() { this.open=false; loc.barangay.search=''; }
                                            }"
                                            @click.outside="closeDropdown()" @keydown.escape.window="closeDropdown()">
                                            <div class="relative flex items-center">
                                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                </div>
                                                <input type="text" :disabled="!addr_city"
                                                    :value="open ? loc.barangay.search : (addr_barangay || '')"
                                                    @input="loc.barangay.search = $event.target.value; open = true"
                                                    @focus="openDropdown()" @click="openDropdown()"
                                                    placeholder="Search or select barangay..."
                                                    class="w-full pl-10 pr-10 py-2 bg-white border border-gray-200 rounded-lg text-[13px] shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all h-10 disabled:bg-gray-50 disabled:opacity-75 disabled:cursor-not-allowed"
                                                    autocomplete="off">
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                    <button type="button" x-show="addr_city && (addr_barangay || loc.barangay.search)" x-cloak
                                                        @click.stop="addr_barangay=''; loc.barangay.search=''; open=false;"
                                                        class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div x-show="open" x-cloak x-transition
                                                :class="dropUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                                class="absolute left-0 right-0 z-50 bg-white rounded-xl shadow-xl border border-slate-100 p-1.5 max-h-48 overflow-y-auto custom-scrollbar">
                                                <template x-for="b in filtered('barangay')" :key="b.code">
                                                    <button type="button" @click="selectBarangay(b); open=false;"
                                                        class="w-full text-left px-3.5 py-2.5 rounded-lg hover:bg-indigo-50/70 transition-colors"
                                                        :class="addr_barangay === b.name ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                                        <span class="text-[13px] font-medium truncate" x-text="b.name"></span>
                                                    </button>
                                                </template>
                                                <template x-if="filtered('barangay').length === 0">
                                                    <div class="px-4 py-3 text-[12px] text-slate-400 italic text-center">No barangays found</div>
                                                </template>
                                            </div>
                                        </div>
                                        <x-input-error :messages="$errors->get('addr_barangay')" class="mt-1" />
                                    </div>
                                </div>

                                <div>
                                    <x-input-label value="House # / Street / Subdivision" />
                                    <input type="text" x-model="addr_street" maxlength="255"
                                        placeholder="e.g. Unit 123, Rosewood Ave, Phase 1"
                                        class="mt-1 block w-full h-10 px-3 py-2 bg-white border border-gray-200 rounded-lg text-[13px] text-gray-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                                    <x-input-error :messages="$errors->get('addr_street')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="updateProfile">
                                Save Profile Changes
                            </x-primary-button>
                        </div>
                    </form>
                </div>
                                <div x-cloak x-show="tab === 'security'" class="p-6 animate-fadeIn">
                    <form wire:submit="updatePassword" class="max-w-xl">
                        <div class="mb-6">
                            <h3 class="text-sm font-bold text-gray-900">Update Password</h3>
                            <p class="text-xs text-gray-500 mt-1">Ensure your account is using a long, random password to stay secure.</p>
                        </div>

<input type="text" name="username" autocomplete="username" value="{{ auth()->user()->email }}"
    class="sr-only" tabindex="-1" aria-hidden="true" readonly>

<div class="space-y-5" x-data="{ showPasswords: false }">
                            
                            {{-- Current Password --}}
                            <div>
                                <x-input-label for="current_password" value="Current Password" />
                                <div class="relative mt-1">
                                    <x-text-input id="current_password" x-bind:type="showPasswords ? 'text' : 'password'" wire:model="currentPassword"
                                        class="block w-full" autocomplete="current-password" :hasError="$errors->has('currentPassword')" />
                                </div>
                                <x-input-error :messages="$errors->get('currentPassword')" class="mt-1" />
                            </div>

                            {{-- New Password --}}
                            <div>
                                <x-input-label for="new_password" value="New Password" />
                                <div class="relative mt-1">
                                    <x-text-input id="new_password" x-bind:type="showPasswords ? 'text' : 'password'" wire:model="password"
                                        class="block w-full" autocomplete="new-password"
                                        x-on:input="checkStrength($event.target.value)"
                                        :hasError="$errors->has('password')" />
                                </div>
                                
                                {{-- Password Strength Meter --}}
                                <div class="mt-2 flex gap-1 h-1.5">
                                    <div class="flex-1 rounded-full transition-colors duration-300" :class="strength >= 1 ? (strength <= 2 ? 'bg-red-400' : (strength <= 3 ? 'bg-amber-400' : 'bg-emerald-400')) : 'bg-gray-100'"></div>
                                    <div class="flex-1 rounded-full transition-colors duration-300" :class="strength >= 2 ? (strength <= 2 ? 'bg-red-400' : (strength <= 3 ? 'bg-amber-400' : 'bg-emerald-400')) : 'bg-gray-100'"></div>
                                    <div class="flex-1 rounded-full transition-colors duration-300" :class="strength >= 3 ? (strength <= 3 ? 'bg-amber-400' : 'bg-emerald-400') : 'bg-gray-100'"></div>
                                    <div class="flex-1 rounded-full transition-colors duration-300" :class="strength >= 4 ? 'bg-emerald-400' : 'bg-gray-100'"></div>
                                    <div class="flex-1 rounded-full transition-colors duration-300" :class="strength >= 5 ? 'bg-emerald-500' : 'bg-gray-100'"></div>
                                </div>
                                <p class="mt-1.5 text-[10px] font-bold uppercase tracking-wider transition-colors"
                                   :class="strength <= 2 ? 'text-red-500' : (strength <= 3 ? 'text-amber-500' : 'text-emerald-600')"
                                   x-text="strength === 0 ? '' : (strength <= 2 ? 'Weak' : (strength <= 3 ? 'Fair' : 'Strong'))"></p>

                                <x-input-error :messages="$errors->get('password')" class="mt-1" />
                            </div>

                            {{-- Confirm Password --}}
                            <div>
                                <x-input-label for="confirm_password" value="Confirm Password" />
                                <div class="relative mt-1">
                                    <x-text-input id="confirm_password" x-bind:type="showPasswords ? 'text' : 'password'" wire:model="passwordConfirmation"
                                        class="block w-full" autocomplete="new-password" :hasError="$errors->has('passwordConfirmation')" />
                                </div>
                                <x-input-error :messages="$errors->get('passwordConfirmation')" class="mt-1" />
                            </div>

                            {{-- Show Password Checkbox --}}
                            <div class="block mt-6">
                                <label for="show_passwords" class="flex items-center gap-3 cursor-pointer select-none">
                                    <input id="show_passwords" type="checkbox" x-model="showPasswords" name="show_passwords"
                                        class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900 h-4 w-4">
                                    <div>
                                        <span class="block text-[13px] font-semibold text-gray-800">Show Passwords</span>
                                        <span class="block text-[12px] text-gray-500">Display password characters as plain text.</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-start">
                            <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="updatePassword">
                                Update Password
                            </x-primary-button>
                        </div>
                    </form>
                    <div class="mt-10 pt-8 border-t border-slate-100"
                         x-data="{ open: false, pendingAction: null, pendingSessionId: null }"
                         @sessions-signed-out.window="open = false; pendingAction = null">
                        <div class="max-w-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">Devices & Sessions</h3>
                                <p class="text-xs text-gray-500 mt-1">Everywhere you're currently signed in. Lost a device? Sign it out from here.</p>
                            </div>
                            <button type="button" wire:click="$refresh" class="text-slate-400 hover:text-slate-600 transition-colors" title="Refresh list">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            </button>
                        </div>

                        @if(config('session.driver') !== 'database')
                            <div class="mt-4 p-3 bg-amber-50 border border-amber-100 rounded-lg text-[12px] text-amber-700">
                                Per-device session listing needs the "database" session driver. You can still sign out every other device below.
                            </div>
                        @else
                            <div class="mt-4 space-y-2">
                                @forelse($this->activeSessions as $s)
                                    <div class="flex items-center justify-between p-3 rounded-xl border {{ $s['is_current'] ? 'border-indigo-200 bg-indigo-50/50' : 'border-slate-100 bg-white' }}">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-9 h-9 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-500 shrink-0">
                                                @if($s['device'] === 'Mobile')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[13px] font-bold text-slate-800 truncate">
                                                    {{ $s['browser'] }} on {{ $s['platform'] }}
                                                    @if($s['is_current'])
                                                        <span class="ml-1 text-[10px] font-black text-indigo-600 uppercase">· This device</span>
                                                    @endif
                                                </p>
                                                <p class="text-[11px] text-slate-400 font-medium">{{ $s['ip_address'] }} · Active {{ $s['last_activity']->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                        @unless($s['is_current'])
                                            <button type="button"
                                                @click="pendingAction = 'single'; pendingSessionId = '{{ $s['id'] }}'; open = true"
                                                class="text-[11px] font-bold text-red-500 hover:text-red-600 shrink-0 ml-3">
                                                Sign out
                                            </button>
                                        @endunless
                                    </div>
                                @empty
                                    <p class="text-[12px] text-slate-400 italic">No other active sessions found.</p>
                                @endforelse
                            </div>
                        @endif

                        <div class="mt-4">
                            <x-secondary-button type="button" @click="pendingAction = 'all'; pendingSessionId = null; open = true">Sign out all other devices</x-secondary-button>
                        </div>

                        <div x-cloak x-show="open" x-transition class="mt-4 p-4 rounded-xl border border-slate-200 bg-slate-50 space-y-3">
                            <x-input-label for="sessions_password" value="Confirm with your current password" />
                            <x-text-input id="sessions_password" type="password" wire:model="sessionsPassword"
                                class="mt-1 block w-full" autocomplete="current-password"
                                :hasError="$errors->has('sessionsPassword')" />
                            <x-input-error :messages="$errors->get('sessionsPassword')" class="mt-1" />
                            <div class="flex items-center gap-2 pt-1">
                                <x-danger-button type="button"
                                    wire:loading.attr="disabled"
                                    x-on:click="pendingAction === 'all' ? $wire.signOutOtherDevices() : $wire.logoutSession(pendingSessionId)">
                                    <span x-text="pendingAction === 'all' ? 'Sign out everywhere else' : 'Sign out this device'"></span>
                                </x-danger-button>
                                <x-secondary-button type="button" @click="open = false; pendingAction = null">Cancel</x-secondary-button>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════ RESIDENTIAL ADDRESS MAP MODAL ══════════════ --}}
    <x-modal name="profile-map-modal" maxWidth="2xl" focusable>
        <div class="h-1 w-full bg-gradient-to-r from-indigo-500 to-purple-600 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight">Geographic Locator</h3>
                    <p class="text-[11px] text-gray-500 mt-0.5 uppercase tracking-wider font-bold">Pin your exact location</p>
                </div>
                <button @click="$dispatch('close-modal', 'profile-map-modal')" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18" /></svg>
                </button>
            </div>

            <div id="profileAddressMap" class="w-full h-[400px] rounded-xl border border-gray-200 shadow-inner z-10" wire:ignore></div>

            <div class="mt-5 p-4 bg-indigo-50 border border-indigo-100 rounded-xl flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-indigo-600 shrink-0 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <h4 class="text-[12px] font-bold text-indigo-900">How to use</h4>
                    <p class="text-[11px] text-indigo-700/80 leading-relaxed">Click anywhere on the map to drop a pin. Region, city, and street will be auto-filled using reverse-geocoding.</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 mt-6">
                <x-primary-button type="button" @click="$dispatch('close-modal', 'profile-map-modal')" class="h-10 px-8">Confirm Pin Location</x-primary-button>
            </div>
        </div>
    </x-modal>
        <div x-show="avatarModal" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[500] flex items-center justify-center p-4"
        @keydown.escape.window="avatarModal = false">

        {{-- Backdrop --}}
        <div class="absolute inset-0" style="background: rgba(15,23,42,0.6); backdrop-filter: blur(4px);" @click="avatarModal = false"></div>

        {{-- Modal Panel --}}
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100">

            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-7 pt-7 pb-5">
                <div>
                    <h3 class="text-[17px] font-black text-slate-900">Choose Your Avatar</h3>
                    <p class="text-[12px] text-slate-500 mt-0.5">Pick a fun avatar to represent you across the system</p>
                </div>
                <button @click="avatarModal = false" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 transition-colors focus:outline-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Category Tabs --}}
            <div class="px-7 mb-6">
                <x-sliding-tabs model="activeCategory" class="px-0" ref="avatarTabList">
                    <x-sliding-tab model="activeCategory" value="foods">
                        <span class="flex items-center gap-2">🍜 Cartoon Foods</span>
                    </x-sliding-tab>
                    <x-sliding-tab model="activeCategory" value="animals">
                        <span class="flex items-center gap-2">🦊 Animals</span>
                    </x-sliding-tab>
                </x-sliding-tabs>
            </div>

            {{-- Avatar Grid --}}
            <div class="px-7 pb-7">
                {{-- Foods --}}
                <div x-show="activeCategory === 'foods'" x-cloak class="animate-fadeIn">
                    <div style="display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.75rem;">
                    @foreach($avatarCollection['foods'] as $av)
                        <button wire:click="selectAvatar('{{ $av['id'] }}')" @click="avatarModal = false" type="button"
                            class="group flex flex-col items-center gap-2 p-3 rounded-2xl transition-all focus:outline-none hover:scale-105
                                {{ $selectedAvatar === $av['id'] ? 'ring-2 ring-indigo-500 ring-offset-2 bg-indigo-50' : 'hover:bg-slate-50' }}">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-3xl shadow-lg transition-shadow" style="{{ $av['style'] }}">
                                {{ $av['emoji'] }}
                            </div>
                            <span class="text-[10px] font-bold text-slate-600 text-center leading-tight">{{ $av['label'] }}</span>
                            @if($selectedAvatar === $av['id'])
                                <div class="w-4 h-4 bg-indigo-500 rounded-full flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            @endif
                        </button>
                    @endforeach
                    </div>
                </div>
                {{-- Animals --}}
                <div x-show="activeCategory === 'animals'" x-cloak class="animate-fadeIn">
                    <div style="display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.75rem;">
                    @foreach($avatarCollection['animals'] as $av)
                        <button wire:click="selectAvatar('{{ $av['id'] }}')" @click="avatarModal = false" type="button"
                            class="group flex flex-col items-center gap-2 p-3 rounded-2xl transition-all focus:outline-none hover:scale-105
                                {{ $selectedAvatar === $av['id'] ? 'ring-2 ring-indigo-500 ring-offset-2 bg-indigo-50' : 'hover:bg-slate-50' }}">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-3xl shadow-lg transition-shadow" style="{{ $av['style'] }}">
                                {{ $av['emoji'] }}
                            </div>
                            <span class="text-[10px] font-bold text-slate-600 text-center leading-tight">{{ $av['label'] }}</span>
                            @if($selectedAvatar === $av['id'])
                                <div class="w-4 h-4 bg-indigo-500 rounded-full flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            @endif
                        </button>
                    @endforeach
                    </div>
                </div>

                {{-- Remove Avatar Option --}}
                @if($selectedAvatar)
                    <div class="mt-5 pt-5 border-t border-slate-100 flex justify-center">
                        <button wire:click="selectAvatar('')" @click="avatarModal = false" type="button"
                            class="text-[11px] font-bold text-slate-400 hover:text-red-500 transition-colors focus:outline-none underline underline-offset-2">
                            Remove avatar (use initials instead)
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
