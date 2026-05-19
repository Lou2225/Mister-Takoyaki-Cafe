<div
    x-data="branchManagementData(@js($panel), @js($view))"
    x-on:switch-panel.window="
        panel = $event.detail?.panel || $event.detail[0]?.panel || 'list'; 
        mode = $event.detail?.mode || $event.detail[0]?.mode || 'list';
        if (panel === 'form') {
            initMap();
            initLagunaDefault();
        } else {
            if (this.marker && mode === 'create') {
                this.map.removeLayer(this.marker);
                this.marker = null;
            }
        }
    "
    x-on:refresh-global-map.window="updateGlobalBranches($event.detail.branches || $event.detail[0]?.branches)"
    @trigger-edit-branch.window="$wire.showEdit($event.detail.id)"
    class="relative">

    <script>
        function branchManagementData(initialPanel, initialView) {
            return {
                ...slidingTabs({ view: initialView || 'table' }, 'view'),
                panel: initialPanel || 'list',
                mode: 'list',
                view: initialView || 'table',
                status: @entangle('status').live,
                addr_lat: @entangle('addr_lat'),
                addr_lng: @entangle('addr_lng'),
                addr_region: @entangle('addr_region'),
                addr_province: @entangle('addr_province'),
                addr_city: @entangle('addr_city'),
                addr_barangay: @entangle('addr_barangay'),

                async initLagunaDefault() {
                    // Set defaults for Region & Province if empty
                    if (!this.addr_region || !this.addr_province) {
                        this.addr_region = 'Region IV-A (CALABARZON)';
                        this.addr_province = 'Laguna';
                    }
                    
                    // Preload Laguna cities (0403400000)
                    if (this.loc.city.items.length === 0) {
                        this.loc.city.loading = true;
                        try {
                            const data = await this.fetchWithRetry('https://psgc.cloud/api/provinces/0403400000/cities-municipalities');
                            this.loc.city.items = data.sort((a, b) => a.name.localeCompare(b.name));
                        } catch (e) { console.error('Failed to load Laguna cities:', e); }
                        finally { this.loc.city.loading = false; }
                    }

                    // If a city is already selected (e.g. edit/view mode), pre-load barangays for it
                    if (this.addr_city && this.loc.barangay.items.length === 0) {
                        const matchedCity = this.loc.city.items.find(c => c.name.toLowerCase() === this.addr_city.toLowerCase());
                        if (matchedCity) {
                            this.loc.barangay.loading = true;
                            try {
                                const data = await this.fetchWithRetry(`https://psgc.cloud/api/cities-municipalities/${matchedCity.code}/barangays`);
                                this.loc.barangay.items = data.sort((a, b) => a.name.localeCompare(b.name));
                            } catch (e) { console.error('Failed to load barangays:', e); }
                            finally { this.loc.barangay.loading = false; }
                        }
                    }
                },
                
                // ── Location ──
                loc: {
                    region: { items: [], search: '', loading: false },
                    province: { items: [], search: '', loading: false },
                    city: { items: [], search: '', loading: false },
                    barangay: { items: [], search: '', loading: false },
                    noProvince: false
                },

                getCustomPinIcon() {
                    return L.divIcon({
                        html: `
                            \x3cdiv class="relative flex flex-col items-center justify-end w-10 h-10"\x3e
                                \x3cspan class="absolute w-4 h-2 bg-indigo-500/40 rounded-full blur-[2px] animate-ping bottom-[-2px] left-1/2 -translate-x-1/2"\x3e\x3c/span\x3e
                                \x3cdiv class="relative w-8 h-8 bg-indigo-600 rounded-t-full rounded-bl-full rotate-45 border-2 border-white shadow-lg flex items-center justify-center transition-all duration-300"\x3e
                                    \x3cdiv class="w-3.5 h-3.5 bg-white rounded-full -rotate-45 flex items-center justify-center shadow-inner"\x3e
                                        \x3cdiv class="w-1.5 h-1.5 bg-indigo-600 rounded-full"\x3e\x3c/div\x3e
                                    \x3c/div\x3e
                                \x3c/div\x3e
                            \x3c/div\x3e
                        `,
                        className: 'custom-leaflet-icon',
                        iconSize: [40, 40],
                        iconAnchor: [20, 40]
                    });
                },

                filtered(type) {
                    const q = this.loc[type].search.toLowerCase();
                    return q ? this.loc[type].items.filter(i => i.name.toLowerCase().includes(q)) : this.loc[type].items;
                },

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
                    } catch (e) { console.error(e); }
                    finally { this.loc.region.loading = false; }
                },

                async selectRegion(region, fromMap = false) {
                    this.addr_region = region.name;
                    this.addr_province = ''; this.addr_city = ''; this.addr_barangay = '';
                    this.loc.province.items = []; this.loc.city.items = []; this.loc.barangay.items = [];
                    this.loc.noProvince = false;
                    
                    if (!fromMap) this.geocodeAddress();
                    
                    this.loc.province.loading = true;
                    try {
                        const data = await this.fetchWithRetry(`https://psgc.cloud/api/regions/${region.code}/provinces`);
                        this.loc.province.items = data.sort((a, b) => a.name.localeCompare(b.name));
                        if (this.loc.province.items.length === 0) {
                            this.loc.noProvince = true;
                            const data2 = await this.fetchWithRetry(`https://psgc.cloud/api/regions/${region.code}/cities-municipalities`);
                            this.loc.city.items = data2.sort((a, b) => a.name.localeCompare(b.name));
                        }
                    } catch (e) { console.error(e); }
                    finally { this.loc.province.loading = false; }
                },

                async selectProvince(province, fromMap = false) {
                    this.addr_province = province.name;
                    this.addr_city = ''; this.addr_barangay = '';
                    
                    if (!fromMap) this.geocodeAddress();
                    
                    this.loc.city.loading = true;
                    try {
                        const data = await this.fetchWithRetry(`https://psgc.cloud/api/provinces/${province.code}/cities-municipalities`);
                        this.loc.city.items = data.sort((a, b) => a.name.localeCompare(b.name));
                    } catch (e) { console.error(e); }
                    finally { this.loc.city.loading = false; }
                },

                async selectCity(city, fromMap = false) {
                    this.addr_city = city.name;
                    this.addr_barangay = '';
                    
                    if (!fromMap) this.geocodeAddress();
                    
                    this.loc.barangay.loading = true;
                    try {
                        const data = await this.fetchWithRetry(`https://psgc.cloud/api/cities-municipalities/${city.code}/barangays`);
                        this.loc.barangay.items = data.sort((a, b) => a.name.localeCompare(b.name));
                    } catch (e) { console.error(e); }
                    finally { this.loc.barangay.loading = false; }
                },

                selectBarangay(brgy, fromMap = false) {
                    this.addr_barangay = brgy.name;
                    if (!fromMap) this.geocodeAddress();
                },

                async geocodeAddress() {
                    const parts = [];
                    const barangay = this.addr_barangay;
                    const city = this.addr_city;
                    const province = this.addr_province;
                    const region = this.addr_region;
                    
                    if (barangay) parts.push(barangay);
                    if (city) parts.push(city);
                    if (province) parts.push(province);
                    if (region) parts.push(region);
                    
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
                                if (barangay) zoom = 16;
                                else if (city) zoom = 14;
                                else if (province) zoom = 12;
                                
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

                // ── Map ──
                map: null,
                marker: null,
                
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
                        setTimeout(() => this.initMap(), 100);
                        return;
                    }
                    this.patchLeaflet();
                    const updateMarker = () => {
                        const lat = parseFloat(this.addr_lat);
                        const lng = parseFloat(this.addr_lng);
                        
                        if (!isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
                            if (this.marker) {
                                this.marker.setLatLng([lat, lng]);
                            } else {
                                this.marker = L.marker([lat, lng], { icon: this.getCustomPinIcon() }).addTo(this.map);
                            }
                            this.map.setView([lat, lng], 15);
                        } else {
                            if (this.marker) {
                                this.map.removeLayer(this.marker);
                                this.marker = null;
                            }
                            this.map.setView([14.2189, 121.1672], 11);
                        }
                    };

                    if (this.map) {
                        setTimeout(() => {
                            this.map.invalidateSize();
                            updateMarker();
                        }, 50);
                        return;
                    }
                    setTimeout(() => {
                        const container = document.getElementById('branchMap');
                        if (!container) return;
                        
                        const street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors'
                        });
                        const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
                        });

                        this.map = L.map('branchMap', {
                            maxBounds: [[13.85, 120.85], [14.65, 121.95]],
                            maxBoundsViscosity: 1.0,
                            minZoom: 10,
                            layers: [street]
                        }).setView([14.2189, 121.1672], 11);

                        L.control.layers({ "Street": street, "Satellite": satellite }).addTo(this.map);

                        this.map.on('click', async (e) => {
                            if (this.marker) this.marker.setLatLng(e.latlng);
                            else this.marker = L.marker(e.latlng, { icon: this.getCustomPinIcon() }).addTo(this.map);
                            this.addr_lat = e.latlng.lat;
                            this.addr_lng = e.latlng.lng;
                            
                            try {
                                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${e.latlng.lat}&lon=${e.latlng.lng}`);
                                const data = await response.json();
                                if (data && data.address) {
                                    let st = data.address.road || data.address.pedestrian || '';
                                    let num = data.address.house_number || '';
                                    let fst = (num + ' ' + st).trim();
                                    if(fst) this.$wire.set('addr_street', fst);
                                    
                                    await this.autoMatchLocation(data.address);
                                }
                            } catch (error) { console.error(error); }
                        });

                        updateMarker();
                    }, 50);
                },

                // ── Global Map ──
                gMap: null,
                gMarkers: [],
                initGlobalMap() {
                    if (typeof L === 'undefined') {
                        setTimeout(() => this.initGlobalMap(), 100);
                        return;
                    }
                    this.patchLeaflet();
                    if (this.gMap) {
                        setTimeout(() => {
                            this.gMap.invalidateSize();
                            this.updateGlobalBranches();
                        }, 350);
                        return;
                    }
                    setTimeout(() => {
                        const container = document.getElementById('globalBranchMap');
                        if (!container) return;

                        const street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors'
                        });
                        const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
                        });

                        this.gMap = L.map('globalBranchMap', {
                            maxBounds: [[13.85, 120.85], [14.65, 121.95]],
                            maxBoundsViscosity: 1.0,
                            minZoom: 10,
                            layers: [street]
                        }).setView([14.2189, 121.1672], 11);

                        L.control.layers({ "Street": street, "Satellite": satellite }).addTo(this.gMap);
                        this.updateGlobalBranches();
                    }, 350);
                },

                updateGlobalBranches(branches) {
                    if (!this.gMap) return;
                    const rawData = branches || @js(json_decode($plottableBranches));
                    const bData = rawData.filter(b => {
                        const lat = parseFloat(b.lat);
                        const lng = parseFloat(b.lng);
                        return !isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
                    });
                    
                    this.gMarkers.forEach(m => this.gMap.removeLayer(m));
                    this.gMarkers = [];
                    
                    let mainBranch = null;

                    bData.forEach(b => {
                        const branchIcon = L.divIcon({
                            html: `
                                \x3cdiv class="relative flex flex-col items-center justify-end w-10 h-10"\x3e
                                    \x3cspan class="absolute w-4 h-2 ${b.is_main ? 'bg-amber-500/40' : 'bg-indigo-500/40'} rounded-full blur-[2px] ${b.status ? 'animate-ping' : ''} bottom-[-2px] left-1/2 -translate-x-1/2"\x3e\x3c/span\x3e
                                    \x3cdiv class="relative w-8 h-8 ${b.is_main ? 'bg-amber-500 border-amber-300' : 'bg-indigo-600 border-indigo-400'} rounded-t-full rounded-bl-full rotate-45 border-2 shadow-lg flex items-center justify-center transition-all duration-300"\x3e
                                        \x3cdiv class="w-3.5 h-3.5 bg-white rounded-full -rotate-45 flex items-center justify-center shadow-inner"\x3e
                                            \x3cdiv class="w-1.5 h-1.5 ${b.is_main ? 'bg-amber-600' : 'bg-indigo-600'} rounded-full"\x3e\x3c/div\x3e
                                        \x3c/div\x3e
                                    \x3c/div\x3e
                                \x3c/div\x3e
                            `,
                            className: 'custom-leaflet-icon',
                            iconSize: [40, 40],
                            iconAnchor: [20, 40]
                        });
                        const marker = L.marker([b.lat, b.lng], { icon: branchIcon }).addTo(this.gMap)
                            .bindPopup(`
                                \x3cdiv class="p-1"\x3e
                                    \x3cdiv class="flex items-center gap-2 mb-1"\x3e
                                        \x3cb class="text-[14px] text-slate-900"\x3e${b.name}\x3c/b\x3e
                                        ${b.is_main ? '\x3cspan class="px-1.5 py-0.5 bg-indigo-600 text-white text-[9px] font-black rounded uppercase tracking-tighter"\x3eMain Hub\x3c/span\x3e' : ''}
                                    \x3c/div\x3e
                                    \x3cp class="text-[11px] text-slate-500 mb-2"\x3e${b.formatted}\x3c/p\x3e
                                    \x3cspan class="text-[10px] font-bold ${b.status ? 'text-green-600' : 'text-red-600'} uppercase tracking-widest"\x3e
                                        ${b.status ? '● Operational' : '○ Inactive'}
                                    \x3c/span\x3e
                                \x3c/div\x3e
                            `);
                        this.gMarkers.push(marker);
                        if (b.is_main) mainBranch = b;
                    });

                    const container = document.getElementById('globalBranchMap');
                    if (container && container.offsetWidth > 0) {
                        // Center on main branch if found
                        if (mainBranch) {
                            this.gMap.setView([mainBranch.lat, mainBranch.lng], 15);
                        } else if (bData.length > 0) {
                            // Fallback: fit bounds to all branches if no main
                            const bounds = L.latLngBounds(bData.map(b => [b.lat, b.lng]));
                            this.gMap.fitBounds(bounds, { padding: [50, 50] });
                        }
                    }
                },

                init() {
                    this.$watch('view', (val) => {
                        if (val === 'map') {
                            this.initGlobalMap();
                        }
                    });
                }
            };
        }
    </script>

    <div class="relative min-h-[600px]">
        {{-- ════════════════ PANEL: FORM (CREATE/EDIT) ════════════════ --}}
        <div x-show="panel === 'form'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-bold text-gray-900 tracking-tight" x-text="mode === 'create' ? 'Register New Branch' : 'Configure Branch'"></h2>
                    <p class="text-[12px] text-gray-500 font-medium" x-text="mode === 'create' ? 'Define a new operational node' : 'Update branch configuration'"></p>
                </div>
                <x-secondary-button @click="panel = 'list'; mode = 'list'; $wire.backToList()" class="h-10">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    Back to List
                </x-secondary-button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    {{-- Branch Info --}}
                    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                        <h2 class="text-[13px] font-semibold text-gray-700 uppercase tracking-wider mb-4">Core Details</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="f_branch_name" value="Branch Name *" />
                                <x-text-input id="f_branch_name" name="branch_name" wire:model.live.debounce.500ms="branch_name" type="text" 
                                    class="mt-1 block w-full" placeholder="e.g. {{ \App\Services\ConfigurationService::getBusinessName() }} - Makati" 
                                    inputFilter="name" maxlength="150"
                                    @keydown="FormFilters.nameKeydown($event)" @paste="FormFilters.namePaste($event)"
                                    :hasError="$errors->has('branch_name')" />
                                <x-input-error :messages="$errors->get('branch_name')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="f_branch_code" value="Branch Code *" />
                                <x-text-input id="f_branch_code" name="branch_code" wire:model.live.debounce.500ms="branch_code" type="text" 
                                    class="mt-1 block w-full uppercase" placeholder="e.g. MAKATI-01" 
                                    maxlength="50"
                                    :hasError="$errors->has('branch_code')" />
                                <x-input-error :messages="$errors->get('branch_code')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="f_phone" value="Phone Number" />
                                <div class="flex items-center mt-1">
                                    <div class="flex-shrink-0 inline-flex items-center px-3 h-10 rounded-l-lg border border-r-0 border-gray-200 bg-gray-50 text-gray-500 text-[13px] font-bold">
                                        +63
                                    </div>
                                    <x-text-input id="f_phone" name="phone" wire:model.live.debounce.500ms="phone" type="text"
                                        class="block w-full rounded-l-none" placeholder="912 345 6789" autocomplete="tel"
                                        inputFilter="number" maxlength="10"
                                        @keydown="FormFilters.numberKeydown($event)" @paste="FormFilters.numberPaste($event)"
                                        :hasError="$errors->has('phone')" />
                                </div>
                                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="f_email" value="Email Address" />
                                <x-text-input id="f_email" name="email" wire:model.live.debounce.500ms="email" type="email" 
                                    class="mt-1 block w-full" placeholder="e.g. makati@mistertakoyaki.com" 
                                    autocomplete="email" inputFilter="email" maxlength="255"
                                    @keydown="FormFilters.emailKeydown($event)" @paste="FormFilters.emailPaste($event)"
                                    :hasError="$errors->has('email')" />
                                <x-input-error :messages="$errors->get('email')" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    {{-- Address --}}
                    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                        <div class="mb-5 flex items-center justify-between">
                            <div>
                                <h3 class="text-[14px] font-bold text-gray-900">Geographic Location</h3>
                                <p class="text-[12px] text-gray-500">Provide the precise address and coordinates.</p>
                            </div>
                            <button type="button" @click="$dispatch('open-modal', 'map-modal'); initMap();" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg text-[11px] font-bold hover:bg-indigo-100 transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /></svg>
                                Open Map Picker
                            </button>
                        </div>

                        <div class="flex flex-col gap-5">
                            {{-- Row 1: Region + Province --}}
                            <div class="flex flex-col sm:flex-row gap-5">
                                <div class="flex-1 w-full">
                                    <x-input-label value="Region *" />
                                    <div class="mt-1">
                                        <button type="button" disabled class="w-full flex items-center justify-between px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[13px] shadow-sm cursor-not-allowed h-10">
                                            <span class="truncate text-gray-700 font-medium" x-text="addr_region || 'Region IV-A (CALABARZON)'"></span>
                                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                        </button>
                                    </div>
                                    <x-input-error :messages="$errors->get('addr_region')" class="mt-1" />
                                </div>

                                <div class="flex-1 w-full">
                                    <x-input-label value="Province *" />
                                    <div class="mt-1">
                                        <button type="button" disabled class="w-full flex items-center justify-between px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-[13px] shadow-sm cursor-not-allowed h-10">
                                            <span class="truncate text-gray-700 font-medium" x-text="addr_province || 'Laguna'"></span>
                                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                        </button>
                                    </div>
                                    <x-input-error :messages="$errors->get('addr_province')" class="mt-1" />
                                </div>
                            </div>

                            {{-- Row 2: City + Barangay --}}
                            <div class="flex flex-col sm:flex-row gap-5">
                                <div class="flex-1 w-full">
                                    <x-input-label value="City / Municipality *" />
                                    <div x-on:click.capture="if(!addr_region || (!addr_province && !loc.noProvince)) { $event.stopPropagation(); } else { loc.city.search = ''; }">
                                        <x-dropdown align="left" width="full" containerClasses="block w-full mt-1">
                                            <x-slot name="trigger">
                                                <button type="button" :disabled="!addr_region || (!addr_province && !loc.noProvince)" class="w-full flex items-center justify-between px-3 py-2 bg-white border border-gray-200 rounded-lg text-[13px] shadow-sm disabled:bg-gray-50 disabled:opacity-75 disabled:cursor-not-allowed h-10">
                                                    <span class="truncate" :class="addr_city ? 'text-gray-900 font-medium' : 'text-gray-400'" x-text="addr_city || 'Select City...'"></span>
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                            </x-slot>
                                            <x-slot name="content">
                                                <div class="p-2 border-b border-gray-100 bg-gray-50/50">
                                                    <input x-model="loc.city.search" type="text" placeholder="Search city..." class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-[12px] focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                                                </div>
                                                <div class="max-h-48 overflow-y-auto">
                                                    <template x-for="c in filtered('city')" :key="c.code">
                                                        <x-dropdown-link href="#" @click.prevent="dropdownOpen = false; selectCity(c)">
                                                            <span x-text="c.name"></span>
                                                        </x-dropdown-link>
                                                    </template>
                                                </div>
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                    <x-input-error :messages="$errors->get('addr_city')" class="mt-1" />
                                </div>

                                <div class="flex-1 w-full">
                                    <x-input-label value="Barangay *" />
                                    <div x-on:click.capture="if(!addr_city) { $event.stopPropagation(); } else { loc.barangay.search = ''; }">
                                        <x-dropdown align="left" width="full" containerClasses="block w-full mt-1">
                                            <x-slot name="trigger">
                                                <button type="button" :disabled="!addr_city" class="w-full flex items-center justify-between px-3 py-2 bg-white border border-gray-200 rounded-lg text-[13px] shadow-sm disabled:bg-gray-50 disabled:opacity-75 disabled:cursor-not-allowed h-10">
                                                    <span class="truncate" :class="addr_barangay ? 'text-gray-900 font-medium' : 'text-gray-400'" x-text="addr_barangay || 'Select Barangay...'"></span>
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                            </x-slot>
                                            <x-slot name="content">
                                                <div class="p-2 border-b border-gray-100 bg-gray-50/50">
                                                    <input x-model="loc.barangay.search" type="text" placeholder="Search barangay..." class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-[12px] focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                                                </div>
                                                <div class="max-h-48 overflow-y-auto">
                                                    <template x-for="b in filtered('barangay')" :key="b.code">
                                                        <x-dropdown-link href="#" @click.prevent="dropdownOpen = false; selectBarangay(b)">
                                                            <span x-text="b.name"></span>
                                                        </x-dropdown-link>
                                                    </template>
                                                </div>
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                    <x-input-error :messages="$errors->get('addr_barangay')" class="mt-1" />
                                </div>
                            </div>

                            {{-- Row 3: Street --}}
                            <div>
                                <x-input-label value="Street / House No. / Landmark" />
                                <x-text-input wire:model.live.debounce.400ms="addr_street" class="w-full mt-1 h-10" placeholder="e.g. Unit 123, Rosewood Ave, Phase 1" :hasError="$errors->has('addr_street')" />
                                <x-input-error :messages="$errors->get('addr_street')" class="mt-1" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Side Info --}}
                <div class="space-y-6">
                    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                        <h2 class="text-[13px] font-semibold text-gray-700 uppercase tracking-wider mb-4">Administration</h2>
                        <div>
                            <x-input-label value="Assigned Manager" />
                            <x-dropdown align="left" width="full" containerClasses="block w-full mt-1">
                                <x-slot name="trigger">
                                    <button type="button" class="flex items-center justify-between w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-[13px] shadow-sm hover:border-indigo-300 transition-all h-10">
                                        <span class="truncate font-medium">{{ $user_id ? $managers->firstWhere('id', $user_id)?->first_name . ' ' . $managers->firstWhere('id', $user_id)?->last_name : 'Unassigned' }}</span>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                </x-slot>
                                <x-slot name="content" class="max-h-60 overflow-y-auto">
                                    <x-dropdown-link href="#" wire:click.prevent="$set('user_id', '')">None / Unassigned</x-dropdown-link>
                                    @foreach($managers as $m)
                                        <x-dropdown-link href="#" wire:click.prevent="$set('user_id', {{ $m->id }})">
                                            {{ $m->first_name }} {{ $m->last_name }}
                                        </x-dropdown-link>
                                    @endforeach
                                </x-slot>
                            </x-dropdown>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-1" />
                        </div>

                        <div class="mt-6 pt-6 border-t border-slate-100">
                            <h3 class="text-[12px] font-bold text-slate-800 uppercase tracking-widest mb-4">Operational Status</h3>
                            <label for="f_status" class="flex items-center gap-3 cursor-pointer select-none">
                                <input type="checkbox" id="f_status" name="status" wire:model.blur="status"
                                    class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-indigo-600 h-4 w-4">
                                <div>
                                    <span class="block text-[13px] font-semibold text-gray-800">Active Node</span>
                                    <span class="block text-[12px] text-gray-500">Allow transactions.</span>
                                </div>
                            </label>
                        </div>

                        {{-- Main Branch Designation --}}
                        @if($branch_id && $this->isSuperAdmin())
                            <div class="mt-6 pt-6 border-t border-slate-100">
                                <h3 class="text-[12px] font-bold text-slate-800 uppercase tracking-widest mb-4">Node Hierarchy</h3>
                                @if(!$is_main)
                                    <button type="button" wire:click="validateBeforeSetMain" class="w-full h-11 bg-indigo-50 text-indigo-600 rounded-xl text-[11px] font-black uppercase tracking-widest hover:bg-indigo-100 transition-all border border-indigo-100 flex items-center justify-center gap-2 group">
                                        <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-2.066 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946 2.066 3.42 3.42 0 013.137 3.137 3.42 3.42 0 002.066 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-2.066 1.946 3.42 3.42 0 01-3.137 3.137 3.42 3.42 0 00-1.946 2.066 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-2.066 3.42 3.42 0 01-3.137-3.137 3.42 3.42 0 00-2.066-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 002.066-1.946 3.42 3.42 0 013.137-3.137z" /></svg>
                                        Designate as Main Branch
                                    </button>
                                    <p class="mt-2 text-[10px] text-slate-400 font-medium leading-relaxed">Designating this as the Main Branch will transfer all <b>Procurement (Stock In)</b> privileges to this location.</p>
                                @else
                                    <div class="flex items-center gap-3 p-3 bg-indigo-50/50 rounded-xl border border-indigo-100/50">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                        </div>
                                        <div>
                                            <p class="text-[12px] font-black text-indigo-700 uppercase tracking-wider leading-none">Primary Node</p>
                                            <p class="text-[10px] text-indigo-500 font-bold mt-1">This is your main headquarters.</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-col gap-2">
                        <x-primary-button type="button" wire:click="validateBeforeSaveBranch" class="w-full justify-center h-11 text-[13px] font-black">
                            <span x-text="mode === 'edit' ? 'Save Changes' : 'Add Branch'"></span>
                        </x-primary-button>
                        <x-secondary-button @click="panel = 'list'; mode = 'list'; $wire.backToList()" class="w-full justify-center h-11">Cancel</x-secondary-button>
                    </div>

                    @if($branch_id)
                        <div class="pt-6 border-t border-slate-100 mt-2">
                            <h3 class="text-[11px] font-black text-red-600 uppercase tracking-[0.2em] mb-3">Danger Zone</h3>
                            <x-secondary-button wire:click="confirmDeleteBranch({{ $branch_id }})" class="w-full justify-center border-red-200 text-red-600 hover:bg-red-50 h-10">
                                Delete Branch
                            </x-secondary-button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ════════════════ PANEL: LIST ════════════════ --}}
        <div x-show="panel === 'list'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">
            
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Branch Management</h2>
                    <p class="text-[12px] text-gray-500 font-medium">Total Branches: <span class="text-indigo-600 font-bold">{{ $systemStats['total'] }}</span></p>
                </div>
                <div class="flex items-center gap-2">
                    <x-secondary-button wire:click="showInsights" class="h-10">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2-2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Analytics
                    </x-secondary-button>
                    @if($this->isSuperAdmin())
                        <x-primary-button @click="panel = 'form'; mode = 'create'; $wire.resetForm()" class="h-10">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Branch
                        </x-primary-button>
                    @endif
                </div>
            </div>

            {{-- Fleet Metrics Grid (Matching Dashboard Premium Aesthetic - Compact Footprint) --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                {{-- Total Branches --}}
                <div class="p-4 bg-gradient-to-br from-indigo-500/10 via-indigo-500/5 to-white border border-indigo-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Branches</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $systemStats['total'] }}</h3>
                    <p class="text-[10px] text-slate-400 font-semibold mt-1.5 leading-none">Registered branch locations</p>
                </div>
                
                {{-- Operational --}}
                <div class="p-4 bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white border border-emerald-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-black text-emerald-600 tracking-tight leading-none">{{ $systemStats['active'] }}</h3>
                    <p class="text-[10px] text-slate-400 font-semibold mt-1.5 leading-none">Operational store nodes</p>
                </div>

                {{-- Decommissioned --}}
                <div class="p-4 bg-gradient-to-br from-rose-500/10 via-rose-500/5 to-white border border-rose-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Inactive</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-rose-100 flex items-center justify-center text-rose-600 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-black text-rose-600 tracking-tight leading-none">{{ $systemStats['inactive'] }}</h3>
                    <p class="text-[10px] text-slate-400 font-semibold mt-1.5 leading-none">Temporarily closed locations</p>
                </div>

                {{-- Workforce --}}
                <div class="p-4 bg-gradient-to-br from-amber-500/10 via-amber-500/5 to-white border border-amber-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Workforce</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-amber-100 flex items-center justify-center text-amber-600 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $systemStats['staff'] }}</h3>
                    <p class="text-[10px] text-slate-400 font-semibold mt-1.5 leading-none">Total employees deployed</p>
                </div>
            </div>

            {{-- Unified Toolbar --}}
            <div class="relative z-20 flex flex-col lg:flex-row lg:items-center justify-between mb-6 gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm">
                
                {{-- Left: Search Bar --}}
                <div class="flex flex-1 w-full lg:w-auto">
                    <x-search-bar wireModel="search" placeholder="Find branches..." width="w-full lg:w-72" />
                </div>

                {{-- Right: Filters & View Toggle --}}
                <div class="flex flex-wrap items-center lg:justify-end gap-2">

                    {{-- Status Filter --}}
                    <x-dropdown align="right" width="48" wire:key="filter-status">
                        <x-slot name="trigger">
                            <x-secondary-button type="button" class="gap-1.5 h-9 !px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                <span class="text-[12px] whitespace-nowrap">{{ $is_active === '1' ? 'Active Only' : ($is_active === '0' ? 'Inactive Only' : 'All Status') }}</span>
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </x-secondary-button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link href="#" wire:click.prevent="$set('is_active', '')">All Status</x-dropdown-link>
                            <x-dropdown-link href="#" wire:click.prevent="$set('is_active', '1')">Active Only</x-dropdown-link>
                            <x-dropdown-link href="#" wire:click.prevent="$set('is_active', '0')">Inactive Only</x-dropdown-link>
                        </x-slot>
                    </x-dropdown>

                    {{-- macOS Divider --}}
                    <div class="hidden lg:block w-px h-6 bg-slate-200 mx-2"></div>

                    {{-- View Toggle: Map (Separate) --}}
                    <button type="button" @click="view = (view === 'map' ? 'table' : 'map')"
                        class="w-9 h-9 flex items-center justify-center rounded-lg transition-colors focus:outline-none shrink-0"
                        :class="view === 'map' ? 'bg-indigo-50 text-indigo-600 border border-indigo-100 shadow-sm' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100'"
                        title="Toggle Map View">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>

                    {{-- View Toggle (Seamless Single Icon matching User Management) --}}
                    <button type="button" @click="view = (view === 'table' ? 'board' : 'table')"
                        class="w-9 h-9 flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors focus:outline-none shrink-0"
                        :title="view === 'table' ? 'Switch to Board View' : 'Switch to Table View'">
                        
                        {{-- Show Board Icon (when in table or map, click to switch to Board) --}}
                        <svg x-cloak x-show="view === 'table' || view === 'map'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        
                        {{-- Show Table Icon (when in board, click to switch to Table) --}}
                        <svg x-cloak x-show="view === 'board'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <div x-show="view === 'table'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <x-data-table>
                    <x-slot name="header">
                        <th class="py-3 px-4 border-r border-slate-100/50 text-[11px] font-black text-slate-500 uppercase tracking-widest">Branch Name</th>
                        <th class="py-3 px-4 border-r border-slate-100/50 text-[11px] font-black text-slate-500 uppercase tracking-widest">Manager</th>
                        <th class="py-3 px-4 border-r border-slate-100/50 text-[11px] font-black text-slate-500 uppercase tracking-widest">Status</th>
                        @if($this->isSuperAdmin())
                            <th class="py-3 px-4 text-right text-[11px] font-black text-slate-500 uppercase tracking-widest">Actions</th>
                        @endif
                    </x-slot>
                    
                    @forelse($branches as $branch)
                        <tr wire:key="branch-row-{{ $branch->id }}" class="hover:bg-slate-50/50 transition-colors group">
                            <td class="py-4 px-4 border-r border-slate-100/50">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-[13px] group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                                        {{ strtoupper(substr($branch->branch_name, 0, 1)) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-[13px] {{ $branch->is_main ? 'text-indigo-600' : 'text-slate-900' }}">{{ $branch->branch_name }}</span>
                                            @if($branch->is_main)
                                                <svg class="w-3.5 h-3.5 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                            @endif
                                        </div>
                                        @php
                                           $addrObj = json_decode($branch->address, true); 
                                           $addrStr = is_array($addrObj) ? ($addrObj['formatted'] ?? '') : $branch->address;
                                        @endphp
                                        <span class="text-[11px] text-slate-400 font-medium">{{ Str::limit($addrStr ?: 'No address provided', 50) }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 border-r border-slate-100/50">
                                @if($branch->manager)
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-6 h-6 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-[10px] ring-1 ring-indigo-200">
                                            {{ strtoupper(substr($branch->manager->first_name, 0, 1)) }}
                                        </div>
                                        <span class="text-[13px] font-bold text-slate-700">{{ $branch->manager->first_name }} {{ $branch->manager->last_name }}</span>
                                    </div>
                                @else
                                    <span class="text-[12px] font-bold text-slate-300 italic tracking-wide">UNASSIGNED</span>
                                @endif
                            </td>
                            <td class="py-4 px-4 border-r border-slate-100/50">
                                @if($this->isSuperAdmin())
                                    <button wire:click="toggleStatus({{ $branch->id }})"
                                        class="flex items-center gap-2 text-[12px] font-medium text-slate-600 hover:opacity-80 transition-opacity focus:outline-none">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $branch->status ? 'bg-[#27C93F] shadow-[0_0_4px_rgba(39,201,63,0.5)]' : 'bg-[#FF5F56] shadow-[0_0_4px_rgba(255,95,86,0.5)]' }}"></span>
                                        {{ $branch->status ? 'Active' : 'Inactive' }}
                                    </button>
                                @else
                                    <div class="flex items-center gap-2 text-[12px] font-medium text-slate-600">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $branch->status ? 'bg-[#27C93F] shadow-[0_0_4px_rgba(39,201,63,0.5)]' : 'bg-[#FF5F56] shadow-[0_0_4px_rgba(255,95,86,0.5)]' }}"></span>
                                        {{ $branch->status ? 'Active' : 'Inactive' }}
                                    </div>
                                @endif
                            </td>
                            @if($this->isSuperAdmin())
                                <td class="py-4 px-4 text-right">
                                    <div class="flex items-center justify-end">
                                        <x-secondary-button @click="$dispatch('trigger-edit-branch', {id: {{ $branch->id }}})" class="h-8 px-3 inline-flex items-center gap-1.5 text-xs shadow-none border-slate-200">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            Edit Branch
                                        </x-secondary-button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-empty-state title="No branches found" description="Adjust filters or add a new branch." />
                            </td>
                        </tr>
                    @endforelse
                </x-data-table>
                <div class="mt-4">
                    <x-pagination :paginator="$branches" />
                </div>
            </div>

            {{-- Board View --}}
            <div x-show="view === 'board'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @forelse($branches as $branch)
                        <div wire:key="branch-card-{{ $branch->id }}" class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm hover:shadow-xl hover:shadow-indigo-500/5 hover:-translate-y-1 transition-all duration-300 group">
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-11 h-11 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-500 font-black text-lg shadow-sm ring-1 ring-slate-100 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                                    {{ strtoupper(substr($branch->branch_name, 0, 1)) }}
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-[0.1em] border {{ $branch->status ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100' }}">
                                        {{ $branch->status ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-bold {{ $branch->is_main ? 'text-indigo-600' : 'text-slate-900' }} text-[15px]">{{ $branch->branch_name }}</h3>
                                @if($branch->is_main)
                                    <svg class="w-4 h-4 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                @endif
                            </div>
                            @php
                               $addrObj = json_decode($branch->address, true); 
                               $addrStr = is_array($addrObj) ? ($addrObj['formatted'] ?? '') : $branch->address;
                            @endphp
                            <p class="text-[11px] text-slate-400 font-medium mb-4 line-clamp-2 min-h-[32px]">{{ $addrStr ?: 'No address set' }}</p>
                            
                            <div class="pt-4 border-t border-slate-50 flex items-center justify-between">
                                <div class="flex flex-col">
                                    <span class="text-[9px] text-slate-400 font-black uppercase tracking-[0.2em]">Manager</span>
                                    <span class="text-[12px] font-bold text-slate-700">{{ $branch->manager?->first_name ?? '—' }} {{ $branch->manager?->last_name ?? '' }}</span>
                                </div>
                                @if($this->isSuperAdmin())
                                    <div class="flex items-center justify-end">
                                        <button @click="$dispatch('trigger-edit-branch', {id: {{ $branch->id }}})" class="p-2 rounded-xl bg-slate-50 text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full">
                            <x-empty-state title="No branches found" description="Try adjusting your filters or search criteria." />
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Map View --}}
            <div x-show="view === 'map'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="mt-6">
                <div class="bg-white rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-200/80 p-2 relative">
                    <div id="globalBranchMap" wire:ignore class="h-[600px] w-full rounded-xl z-10 border border-slate-100"></div>
                </div>
            </div>


        </div>

        {{-- Panel: Analytics --}}
        <div x-show="panel === 'insights' || panel === 'analytics'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Branch Analytics</h2>
                    <p class="text-[12px] text-gray-500 font-medium mt-0.5">Compare performance across branches</p>
                </div>
                <div>
                    <button @click="panel = 'list'; $wire.backToList()" class="inline-flex items-center gap-2 px-4 py-2 text-[12px] font-bold text-gray-700 bg-white border border-gray-200 rounded-xl hover:border-gray-300 focus:outline-none shadow-sm transition-all h-10">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        <span>Back to List</span>
                    </button>
                </div>
            </div>

            <div class="flex flex-col xl:flex-row gap-6 pb-12">
                {{-- Left Sidebar: Comparison Parameters --}}
                <div class="w-full xl:w-72 shrink-0 space-y-5">
                    <div class="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                        <h3 class="text-[13px] font-bold text-gray-900 uppercase tracking-widest mb-4">Timeframe</h3>
                        
                        @if($dateError)
                            <div class="mb-4 flex items-start gap-2 p-2 bg-red-50 border border-red-200 rounded-lg">
                                <svg class="w-3.5 h-3.5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <p class="text-[11px] font-bold text-red-800 leading-tight">{{ $dateError }}</p>
                            </div>
                        @endif

                        <div class="flex flex-col gap-3">
                            <x-date-filter startModel="startDate" endModel="endDate" activeModel="activeFilter" align="left" class="w-full" />
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] flex flex-col" style="max-height: 500px;">
                        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 rounded-t-2xl shrink-0">
                            <h3 class="text-[13px] font-bold text-gray-900 uppercase tracking-widest">Target Branch</h3>
                            <button wire:click="{{ count($selectedBranchIds) === $allBranches->count() ? 'clearAllBranches' : 'selectAllBranches' }}" class="text-[10px] font-black text-indigo-600 hover:text-indigo-800 transition-colors uppercase tracking-widest">
                                {{ count($selectedBranchIds) === $allBranches->count() ? 'Deselect All' : 'Select All' }}
                            </button>
                        </div>
                        <div class="p-2 overflow-y-auto space-y-1 scrollbar-thin scrollbar-thumb-gray-200 hover:scrollbar-thumb-gray-300">
                            @foreach($allBranches as $b)
                                @php $included = in_array($b->id, $selectedBranchIds); @endphp
                                <label class="flex items-center gap-3 p-2.5 rounded-xl cursor-pointer transition-all hover:bg-slate-50 border {{ $included ? 'border-indigo-100 bg-indigo-50/30 shadow-[inset_0_0_0_1px_rgba(99,102,241,0.1)]' : 'border-transparent' }}">
                                    <input type="checkbox" wire:click="toggleBranch({{ $b->id }})" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-gray-300" {{ $included ? 'checked' : '' }}>
                                    <div class="flex flex-col">
                                        <span class="text-[13px] font-bold {{ $included ? 'text-indigo-900' : 'text-gray-700' }} leading-tight">{{ $b->branch_name }}</span>
                                        <span class="text-[10px] {{ $b->status ? 'text-emerald-500' : 'text-rose-400' }} font-bold uppercase tracking-widest mt-0.5">{{ $b->status ? 'Active' : 'Inactive' }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Right Main Content --}}
                <div class="flex-1 min-w-0 space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 2xl:grid-cols-4 gap-5">
                        {{-- KPI Cards --}}
                        <div class="bg-indigo-600 rounded-3xl p-6 text-white shadow-[0_15px_30px_-10px_rgba(79,70,229,0.3)] relative overflow-hidden group border border-indigo-400/20">
                            <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                            <div class="relative">
                                <div class="w-10 h-10 bg-white/20 rounded-2xl flex items-center justify-center mb-4 backdrop-blur-md">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-100/80">Net Revenue</p>
                                <h3 class="text-2xl font-black mt-1 tracking-tighter">₱{{ number_format($totals['net'], 2) }}</h3>
                                <p class="text-[10px] font-bold text-indigo-200/60 mt-1">Excl. Fees</p>
                            </div>
                        </div>

                        <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-[0_8px_30px_rgba(0,0,0,0.02)] relative overflow-hidden group">
                            <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-slate-50 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                            <div class="relative">
                                <div class="w-10 h-10 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-center mb-4">
                                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                                </div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total Orders</p>
                                <h3 class="text-2xl font-black text-slate-900 mt-1 tracking-tighter">{{ number_format($totals['orders']) }}</h3>
                                <p class="text-[10px] font-bold text-slate-400/60 mt-1">Confirmed Completions</p>
                            </div>
                        </div>

                        <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-[0_8px_30px_rgba(0,0,0,0.02)] relative overflow-hidden group">
                            <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-slate-50 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                            <div class="relative">
                                <div class="w-10 h-10 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-center mb-4">
                                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                </div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Avg. Basket</p>
                                <h3 class="text-2xl font-black text-slate-900 mt-1 tracking-tighter">₱{{ number_format($totals['atv'] , 2) }}</h3>
                                <p class="text-[10px] font-bold text-slate-400/60 mt-1">Revenue per Transaction</p>
                            </div>
                        </div>

                        <div class="bg-slate-900 rounded-3xl p-6 text-white shadow-xl relative overflow-hidden group">
                            <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                            <div class="relative">
                                <div class="w-10 h-10 bg-white/10 rounded-2xl flex items-center justify-center mb-4 backdrop-blur-md">
                                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-7.714 2.143L11 21l-2.286-6.857L1 12l7.714-2.143L11 3z" /></svg>
                                </div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Top Performer</p>
                                <h3 class="text-lg font-black mt-1 leading-tight">{{ $matrix->first()['name'] ?? 'Audit Pending' }}</h3>
                                <p class="text-[10px] font-bold text-indigo-400/60 mt-2">Fleet Leadership</p>
                            </div>
                        </div>
                    </div>

                    {{-- CSS Visual Bar Chart --}}
                    @if($matrix->count() > 0)
                        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.02)]">
                            <div class="mb-5 flex items-center justify-between">
                                <h3 class="text-[14px] font-bold text-slate-900">Revenue Distribution</h3>
                                <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Net Sales (₱)</span>
                            </div>
                            <div class="space-y-4">
                                @foreach($matrix->take(5) as $index => $row)
                                    @php 
                                        $percentage = $maxNetSales > 0 ? ($row['net_sales'] / $maxNetSales) * 100 : 0;
                                        $isTop = $index === 0;
                                    @endphp
                                    <div class="flex items-center gap-4">
                                        <div class="w-1/3 shrink-0 line-clamp-2 leading-tight text-[12px] font-bold {{ $isTop ? 'text-indigo-600' : 'text-slate-700' }}">
                                            {{ $row['name'] }}
                                        </div>
                                        <div class="flex-1 flex items-center">
                                            <div class="h-6 rounded-r-lg flex items-center {{ $percentage > 15 ? 'justify-end pr-2' : '' }} transition-all duration-1000 ease-out {{ $isTop ? 'bg-indigo-500 shadow-[0_0_10px_rgba(99,102,241,0.3)]' : 'bg-slate-300' }}" style="width: {{ $percentage }}%">
                                                @if($percentage > 15)
                                                    <span class="text-[11px] font-black tracking-tight text-white">₱{{ number_format($row['net_sales'], 0) }}</span>
                                                @endif
                                            </div>
                                            @if($percentage <= 15)
                                                <span class="ml-2 text-[11px] font-black tracking-tight text-slate-500">₱{{ number_format($row['net_sales'], 0) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if($matrix->count() > 5)
                                <div class="mt-4 text-center">
                                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">+ {{ $matrix->count() - 5 }} more branches in table below</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Data Table --}}
                    <div class="mt-4">
                        <x-data-table>
                            <x-slot name="header">
                                <th class="py-3 px-4 border-r border-slate-100/50 text-[11px] font-black text-slate-500 uppercase tracking-widest">Rank</th>
                                <th class="py-3 px-4 border-r border-slate-100/50 text-[11px] font-black text-slate-500 uppercase tracking-widest">Branch Node</th>
                                <th class="py-3 px-4 border-r border-slate-100/50 text-[11px] font-black text-slate-500 uppercase tracking-widest text-right">Net Revenue</th>
                                <th class="py-3 px-4 border-r border-slate-100/50 text-[11px] font-black text-slate-500 uppercase tracking-widest text-right">Gross Sales</th>
                                <th class="py-3 px-4 border-r border-slate-100/50 text-[11px] font-black text-slate-500 uppercase tracking-widest text-center cursor-help group/tooltip relative" title="Total number of transactions">Volume
                                    <span class="hidden group-hover/tooltip:block absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-slate-900 text-white text-[10px] font-normal whitespace-nowrap rounded-lg shadow-lg z-50">Order count</span>
                                </th>
                                <th class="py-3 px-4 text-[11px] font-black text-slate-500 uppercase tracking-widest text-center cursor-help group/tooltip relative" title="Percentage share of compared net revenue">Share
                                    <span class="hidden group-hover/tooltip:block absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-slate-900 text-white text-[10px] font-normal whitespace-nowrap rounded-lg shadow-lg z-50 w-max">Net Sales ÷ Total Compared Net Sales × 100</span>
                                </th>
                            </x-slot>
                            
                            @forelse($matrix as $row)
                                <tr wire:key="analytics-row-{{ $row['id'] }}" class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="py-4 px-4 border-r border-slate-100/50">
                                        <div class="w-7 h-7 rounded-lg flex items-center justify-center font-black text-[12px] {{ $loop->first ? 'bg-amber-100 text-amber-700 shadow-sm border border-amber-200' : 'bg-slate-50 text-slate-400 border border-slate-100' }}">
                                            {{ $loop->iteration }}
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 border-r border-slate-100/50 font-bold text-[13px] text-slate-900 group-hover:text-indigo-600 transition-colors">{{ $row['name'] }}</td>
                                    <td class="py-4 px-4 border-r border-slate-100/50 text-right">
                                        <span class="font-black text-slate-900 text-[14px]">₱{{ number_format($row['net_sales'], 2) }}</span>
                                    </td>
                                    <td class="py-4 px-4 border-r border-slate-100/50 text-right">
                                        <span class="font-bold text-slate-500 text-[13px]">₱{{ number_format($row['gross_sales'], 2) }}</span>
                                    </td>
                                    <td class="py-4 px-4 border-r border-slate-100/50 text-center font-black text-slate-700 text-[13px] tracking-tight group/vol relative cursor-help">
                                        {{ number_format($row['order_count']) }}
                                        <div class="hidden group-hover/vol:block absolute top-full left-1/2 -translate-x-1/2 mt-1 px-2 py-1 bg-slate-900 text-white text-[9px] font-normal rounded shadow-lg z-50 whitespace-nowrap">Total Transactions</div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center justify-center gap-3 group/score relative">
                                            <div class="w-20 bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200/50 p-0.5 cursor-help" title="Percentage share of compared net sales: {{ number_format($row['share_pct'], 1) }}%">
                                                <div class="h-full rounded-full transition-all duration-1000 bg-indigo-500" style="width: {{ $row['share_pct'] }}%;"></div>
                                            </div>
                                            <span class="text-[12px] font-black w-10 text-right cursor-help text-indigo-600">{{ number_format($row['share_pct'], 1) }}%</span>
                                            <div class="hidden group-hover/score:block absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-slate-900 text-white text-[10px] font-normal rounded-lg shadow-lg z-50 w-max">Revenue Share of Compared Network: {{ number_format($row['share_pct'], 1) }}%</div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12">
                                        <x-empty-state title="No branches selected" description="Select one or more target branch from the sidebar to view comparison data." />
                                    </td>
                                </tr>
                            @endforelse
                        </x-data-table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Map Picker Modal --}}
    <x-modal name="map-modal" maxWidth="4xl" focusable>
        <div class="h-1 w-full bg-indigo-500 rounded-t-lg"></div>
        <div class="p-6">
            <h3 class="text-[17px] font-bold text-gray-900 leading-tight mb-2">Pin Branch Location</h3>
            <p class="text-[13px] text-gray-500 mb-5">Zoom in and click to drop a pin. This will automatically set your street address based on OpenStreetMap data. You will still need to manually configure the PSGC region dropdowns.</p>
            <div wire:ignore class="border border-gray-200 rounded-2xl overflow-hidden shadow-sm" style="height: 550px; z-index: 10;">
                <div id="branchMap" class="w-full h-full"></div>
            </div>
            <div class="mt-4 flex items-center justify-end gap-2">
                <x-primary-button @click="$dispatch('close-modal', 'map-modal')">Confirm Pin</x-primary-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="delete-branch" maxWidth="sm" focusable>
        <div class="h-1 w-full bg-[#FF5F56] rounded-t-lg"></div>
        <div class="p-6 text-left">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-50 border border-red-100 flex items-center justify-center text-[#FF5F56]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-width="2" /></svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight">Delete Branch</h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">This will permanently delete the branch and all its data. This action cannot be undone.</p>
                </div>
            </div>
            <div class="px-3 py-2 bg-gray-50 border border-gray-100 rounded-lg text-[13px] text-gray-800 font-bold mb-5 truncate">
                {{ $branch_name }}
            </div>
            <div class="flex items-center justify-end gap-2">
                <x-secondary-button @click="$dispatch('close-modal', 'delete-branch')">Cancel</x-secondary-button>
                <x-danger-button wire:click="deleteBranch({{ $deleteTargetId ?? 0 }})" @click="$dispatch('close-modal', 'delete-branch')">Delete Branch</x-danger-button>
            </div>
        </div>
    </x-modal>

    {{-- Save Confirm Modal --}}
    <x-modal name="confirm-save-branch" maxWidth="sm" focusable>
        <div class="h-1 w-full bg-gradient-to-r from-indigo-400 to-blue-500 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight">{{ $branch_id ? 'Update Configuration' : 'Register Branch' }}</h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">{{ $branch_id ? 'Apply changes to this operational node?' : 'Are you sure you want to register this new branch location?' }}</p>
                </div>
            </div>
            
            <div class="flex items-center justify-end gap-2 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-save-branch')" class="h-10">Cancel</x-secondary-button>
                <x-primary-button wire:click="{{ $branch_id ? 'updateBranch' : 'saveBranch' }}" @click="$dispatch('close-modal', 'confirm-save-branch')" class="h-10 px-6">
                    Confirm & Save
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- ════════════════ MODAL: CONFIRM SET MAIN ════════════════ --}}
    <x-modal name="confirm-set-main" maxWidth="md" focusable>
        <div class="p-6">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-indigo-100 flex items-center justify-center text-indigo-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.268 14c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <div>
                    <h2 class="text-[18px] font-black text-slate-900 leading-tight">Reassign Primary Operations Center?</h2>
                    <p class="text-[12px] text-slate-500 font-medium mt-1">This is a significant structural change to your logistics engine.</p>
                </div>
            </div>

            <div class="bg-indigo-50/50 border border-indigo-100 rounded-2xl p-5 mb-6">
                <h3 class="text-[13px] font-bold text-indigo-900 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>
                    Operational Impact
                </h3>
                <ul class="space-y-2.5">
                    <li class="flex items-start gap-2 text-[11px] text-slate-600 leading-relaxed font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 mt-1.5 shrink-0"></span>
                        <span><b>Procurement Rights:</b> Only this branch will be permitted to perform manual <b>Stock In</b> movements.</span>
                    </li>
                    <li class="flex items-start gap-2 text-[11px] text-slate-600 leading-relaxed font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 mt-1.5 shrink-0"></span>
                        <span><b>Supply Chain Hub:</b> This node will be designated as the source for all satellite branch orders.</span>
                    </li>
                    <li class="flex items-start gap-2 text-[11px] text-slate-600 leading-relaxed font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 mt-1.5 shrink-0"></span>
                        <span><b>System Default:</b> This branch will be the pre-selected option in logistics modules.</span>
                    </li>
                </ul>
            </div>

            <div class="mb-6">
                <label class="flex items-start gap-3 cursor-pointer group p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-all">
                    <input type="checkbox" wire:model.live="confirmMainDesignation" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                    <span class="text-[12px] font-bold text-slate-700 leading-relaxed select-none group-hover:text-slate-900 transition-colors">
                        I understand that this will reconfigure the company's logistics hub and transfer all primary procurement privileges.
                    </span>
                </label>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-secondary-button x-on:click="$dispatch('close')" class="h-11 px-6">
                    Cancel
                </x-secondary-button>
                <x-primary-button 
                    wire:click="setMainBranch({{ $branch_id ?? 0 }})" 
                    x-on:click="$dispatch('close')" 
                    class="h-11 px-6 bg-indigo-600 hover:bg-indigo-700 shadow-indigo-200 disabled:opacity-50 disabled:grayscale disabled:cursor-not-allowed"
                    :disabled="!$confirmMainDesignation">
                    Confirm & Set as Main
                </x-primary-button>
            </div>
        </div>
    </x-modal>

</div>

