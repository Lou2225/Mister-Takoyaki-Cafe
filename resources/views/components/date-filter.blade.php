@props([
    'startModel' => 'startDate',
    'endModel' => 'endDate',
    'activeModel' => 'activeFilter',
    'refreshAction' => '',
    'align' => 'right'
])

<div x-data="{
    isOpen: false,
    startDate: @entangle($startModel),
    endDate: @entangle($endModel),
    activeFilter: @entangle($activeModel),
    tempStartDate: '',
    tempEndDate: '',
    currentYear: new Date().getFullYear(),
    currentMonth: new Date().getMonth(),

    init() {
        this.tempStartDate = this.startDate || '';
        this.tempEndDate = this.endDate || '';
        
        // Always align the right calendar (currentMonth) with the End Date if set, otherwise current date
        const targetDateStr = this.tempEndDate || this.tempStartDate;
        if (targetDateStr) {
            const d = new Date(targetDateStr);
            if (!isNaN(d.getTime())) {
                this.currentYear = d.getFullYear();
                this.currentMonth = d.getMonth();
            }
        }
        
        this.$watch('startDate', (val) => {
            this.tempStartDate = val || '';
        });
        this.$watch('endDate', (val) => {
            this.tempEndDate = val || '';
        });
    },

    get formattedActiveLabel() {
        if (this.activeFilter && this.activeFilter !== 'All Time' && this.startDate && this.endDate) {
            return this.formatDateLabel(this.startDate) + ' - ' + this.formatDateLabel(this.endDate);
        }
        if (this.startDate && this.endDate) {
            return this.formatDateLabel(this.startDate) + ' - ' + this.formatDateLabel(this.endDate);
        }
        return 'All Time';
    },

    formatDateLabel(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return '';
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
    },

    nextMonths() {
        // Enforce that we cannot navigate past today's month/year
        const today = new Date();
        const nextMonth = this.currentMonth === 11 ? 0 : this.currentMonth + 1;
        const nextYear = this.currentMonth === 11 ? this.currentYear + 1 : this.currentYear;
        
        // If the right calendar would go to a future month, stop it
        if (new Date(nextYear, nextMonth, 1) > today) {
            return;
        }

        if (this.currentMonth === 11) {
            this.currentMonth = 0;
            this.currentYear++;
        } else {
            this.currentMonth++;
        }
    },

    prevMonths() {
        if (this.currentMonth === 0) {
            this.currentMonth = 11;
            this.currentYear--;
        } else {
            this.currentMonth--;
        }
    },

    getMonths() {
        // Left month: Previous Month (currentMonth - 1)
        let m1 = this.currentMonth - 1;
        let y1 = this.currentYear;
        if (m1 < 0) {
            m1 = 11;
            y1--;
        }
        const month1 = this.getMonthDays(y1, m1);

        // Right month: Current Month (currentMonth)
        const month2 = this.getMonthDays(this.currentYear, this.currentMonth);
        
        return [month1, month2];
    },

    isFuture(dateStr) {
        if (!dateStr) return false;
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return new Date(dateStr) > today;
    },

    get isValidRange() {
        if (!this.tempStartDate || !this.tempEndDate) return false;
        const start = new Date(this.tempStartDate);
        const end = new Date(this.tempEndDate);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return start <= today && end <= today && end >= start;
    },

    getMonthDays(year, month) {
        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        let firstDayIndex = new Date(year, month, 1).getDay();
        firstDayIndex = firstDayIndex === 0 ? 6 : firstDayIndex - 1;

        const days = [];
        for (let i = 0; i < firstDayIndex; i++) {
            days.push({ day: null, dateStr: null });
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const padMonth = String(month + 1).padStart(2, '0');
            const padDay = String(d).padStart(2, '0');
            const dateStr = `${year}-${padMonth}-${padDay}`;
            days.push({ day: d, dateStr: dateStr });
        }

        return {
            name: monthNames[month] + ' ' + year,
            days: days,
            year: year,
            month: month
        };
    },

    selectDate(dateStr) {
        if (this.isFuture(dateStr)) return;
        if (!this.tempStartDate || (this.tempStartDate && this.tempEndDate)) {
            this.tempStartDate = dateStr;
            this.tempEndDate = '';
        } else if (this.tempStartDate && !this.tempEndDate) {
            if (new Date(dateStr) < new Date(this.tempStartDate)) {
                this.tempEndDate = this.tempStartDate;
                this.tempStartDate = dateStr;
            } else {
                this.tempEndDate = dateStr;
            }
        }
    },

    isSelected(dateStr) {
        if (!dateStr) return false;
        return this.tempStartDate === dateStr || this.tempEndDate === dateStr;
    },

    isInRange(dateStr) {
        if (!dateStr || !this.tempStartDate || !this.tempEndDate) return false;
        const d = new Date(dateStr);
        const start = new Date(this.tempStartDate);
        const end = new Date(this.tempEndDate);
        return d > start && d < end;
    },

    isStart(dateStr) {
        return this.tempStartDate === dateStr;
    },

    isEnd(dateStr) {
        return this.tempEndDate === dateStr;
    },

    applyPreset(preset) {
        const today = new Date();
        let start, end, label;

        switch(preset) {
            case 'today':
                start = this.formatDate(today);
                end = this.formatDate(today);
                label = 'Today Only';
                break;
            case 'yesterday':
                const yesterday = new Date(today);
                yesterday.setDate(today.getDate() - 1);
                start = this.formatDate(yesterday);
                end = this.formatDate(yesterday);
                label = 'Yesterday';
                break;
            case 'week':
                const lastWeek = new Date(today);
                lastWeek.setDate(today.getDate() - 7);
                start = this.formatDate(lastWeek);
                end = this.formatDate(today);
                label = 'Last 7 Days';
                break;
            case 'last_week':
                const day = today.getDay();
                const diffToMon = day === 0 ? 13 : day + 6;
                const diffToSun = day === 0 ? 7 : day;
                
                const pMon = new Date(today);
                pMon.setDate(today.getDate() - diffToMon);
                const pSun = new Date(today);
                pSun.setDate(today.getDate() - diffToSun);
                
                start = this.formatDate(pMon);
                end = this.formatDate(pSun);
                label = 'Last week';
                break;
            case 'month':
                const lastMonth = new Date(today);
                lastMonth.setDate(today.getDate() - 30);
                start = this.formatDate(lastMonth);
                end = this.formatDate(today);
                label = 'Last 30 Days';
                break;
            case 'last_month':
                const prevMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const prevMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                start = this.formatDate(prevMonthStart);
                end = this.formatDate(prevMonthEnd);
                label = 'Last month';
                break;
            case 'year':
                const thisYearStart = new Date(today.getFullYear(), 0, 1);
                start = this.formatDate(thisYearStart);
                end = this.formatDate(today);
                label = 'This year';
                break;
            case 'last_year':
                const lastYearStart = new Date(today.getFullYear() - 1, 0, 1);
                const lastYearEnd = new Date(today.getFullYear() - 1, 11, 31);
                start = this.formatDate(lastYearStart);
                end = this.formatDate(lastYearEnd);
                label = 'Last year';
                break;
            case 'all':
                start = '';
                end = '';
                label = 'All Time';
                break;
        }

        this.tempStartDate = start;
        this.tempEndDate = end;
        this.activeFilter = label;
        
        const refreshAct = '{{ $refreshAction }}';
        if (refreshAct) {
            this.$wire.set('{{ $startModel }}', start, false);
            this.$wire.set('{{ $endModel }}', end, false);
            this.$wire.set('{{ $activeModel }}', label, false);
            this.$wire.call(refreshAct);
        } else {
            this.$wire.set('{{ $startModel }}', start, false);
            this.$wire.set('{{ $endModel }}', end, false);
            this.$wire.set('{{ $activeModel }}', label, true);
        }
        
        this.isOpen = false;
    },

    formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    },

    applyCustomRange() {
        if (!this.tempStartDate || !this.tempEndDate) return;
        this.activeFilter = 'Custom Range';
        this.startDate = this.tempStartDate;
        this.endDate = this.tempEndDate;
        
        const refreshAct = '{{ $refreshAction }}';
        if (refreshAct) {
            this.$wire.set('{{ $startModel }}', this.tempStartDate, false);
            this.$wire.set('{{ $endModel }}', this.tempEndDate, false);
            this.$wire.set('{{ $activeModel }}', 'Custom Range', false);
            this.$wire.call(refreshAct);
        } else {
            this.$wire.set('{{ $startModel }}', this.tempStartDate, false);
            this.$wire.set('{{ $endModel }}', this.tempEndDate, false);
            this.$wire.set('{{ $activeModel }}', 'Custom Range', true);
        }
        
        this.isOpen = false;
    },

    cancel() {
        this.tempStartDate = this.startDate || '';
        this.tempEndDate = this.endDate || '';
        this.isOpen = false;
    }
}" @click.away="cancel()" class="relative select-none" wire:ignore>
    {{-- Collapsed Trigger Button --}}
    <button type="button" @click="isOpen = !isOpen" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-black text-gray-700 bg-white border border-gray-200 rounded-xl shadow-sm hover:bg-gray-50 h-10 transition-all select-none">
        <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span x-text="formattedActiveLabel">All Time</span>
        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0 transition-transform duration-300" :class="isOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    {{-- Dropdown Panel --}}
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-2 scale-95"
         class="absolute top-12 {{ $align === 'left' ? 'left-0' : 'right-0' }} bg-white rounded-2xl shadow-2xl border border-gray-100 p-4 sm:p-6 z-50 flex flex-col md:flex-row gap-6 min-w-[300px] sm:min-w-[400px] md:min-w-[780px] max-w-[95vw] md:max-w-none overflow-hidden select-none"
         x-cloak>
         
        <!-- Left pane: Presets list -->
        <div class="w-full md:w-44 shrink-0 flex flex-row md:flex-col overflow-x-auto md:overflow-x-visible border-b md:border-b-0 md:border-r border-slate-100 pb-2 md:pb-0 md:pr-4 gap-1">
            <template x-for="preset in [
                { id: 'today', name: 'Today' },
                { id: 'yesterday', name: 'Yesterday' },
                { id: 'week', name: 'This week' },
                { id: 'last_week', name: 'Last week' },
                { id: 'month', name: 'This month' },
                { id: 'last_month', name: 'Last month' },
                { id: 'year', name: 'This year' },
                { id: 'last_year', name: 'Last year' },
                { id: 'all', name: 'All time' }
            ]">
                <button type="button" @click="applyPreset(preset.id)"
                        :class="activeFilter === preset.name || (preset.id === 'week' && activeFilter === 'Last 7 Days') || (preset.id === 'month' && activeFilter === 'Last 30 Days') ? 'bg-indigo-50 text-indigo-700 font-black' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-medium'"
                        class="w-full text-left px-3 py-2 text-[12px] rounded-lg transition-all whitespace-nowrap">
                    <span x-text="preset.name"></span>
                </button>
            </template>
        </div>

        <!-- Right pane: Calendar Months Grid -->
        <div class="flex-1 flex flex-col gap-4">
            
            <!-- Calendar Months Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <template x-for="(monthData, mIdx) in getMonths()">
                    <div class="flex-1">
                        
                        <!-- Inline Header with Arrows -->
                        <div class="flex items-center justify-between mb-4 px-1 select-none">
                            <!-- Left Side: Arrow (always on left card, mobile-only on right card) -->
                            <div class="w-8 h-8 flex items-center justify-start">
                                <button type="button" @click="prevMonths()"
                                        :class="mIdx === 0 ? 'flex' : 'hidden md:hidden'"
                                        class="p-1 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button type="button" @click="prevMonths()"
                                        :class="mIdx === 1 ? 'flex md:hidden' : 'hidden'"
                                        class="p-1 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                            </div>

                            <!-- Center: Month/Year Title -->
                            <span class="text-[13px] font-black text-slate-900" x-text="monthData.name"></span>

                            <!-- Right Side: Arrow (always on right card, mobile-only on left card) -->
                            <div class="w-8 h-8 flex items-center justify-end">
                                <button type="button" @click="nextMonths()"
                                        :class="mIdx === 1 ? 'flex' : 'hidden md:hidden'"
                                        class="p-1 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                                <button type="button" @click="nextMonths()"
                                        :class="mIdx === 0 ? 'flex md:hidden' : 'hidden'"
                                        class="p-1 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Weekday Headers -->
                        <div class="grid grid-cols-7 gap-1 text-center mb-2">
                            <template x-for="dayName in ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su']">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider" x-text="dayName"></span>
                            </template>
                        </div>

                        <!-- Days Grid -->
                        <div class="grid grid-cols-7 gap-y-1.5 gap-x-1">
                            <template x-for="d in monthData.days">
                                <div class="relative h-8 flex items-center justify-center">
                                    <!-- Continuous Range Background Highlight -->
                                    <div class="absolute inset-y-1.5 left-0 right-0 z-0 transition-all"
                                         :class="[
                                             isInRange(d.dateStr) ? 'bg-indigo-50/70' : '',
                                             isStart(d.dateStr) && tempEndDate ? 'bg-indigo-50/70 rounded-l-full left-1/2' : '',
                                             isEnd(d.dateStr) && tempStartDate ? 'bg-indigo-50/70 rounded-r-full right-1/2' : ''
                                         ]">
                                    </div>

                                    <!-- Day Button -->
                                    <button type="button" 
                                            @click="d.day && !isFuture(d.dateStr) ? selectDate(d.dateStr) : null"
                                            :disabled="!d.day || isFuture(d.dateStr)"
                                            :class="[
                                                isSelected(d.dateStr) ? 'bg-indigo-600 text-white font-black shadow-lg shadow-indigo-100 z-10' : (d.day ? (isFuture(d.dateStr) ? 'text-slate-300 opacity-30 cursor-not-allowed pointer-events-none' : 'text-slate-700 hover:bg-slate-100 z-10') : 'text-transparent cursor-default'),
                                                d.day ? 'w-8 h-8 rounded-full flex items-center justify-center text-[12px] font-bold transition-all transform active:scale-95' : ''
                                            ]">
                                        <span x-text="d.day || ''"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
            
            <!-- Bottom pane (Inputs & Actions) -->
            <div class="border-t border-slate-100 pt-4 flex flex-col sm:flex-row items-center justify-between gap-3 mt-4 shrink-0">
                <!-- Date Inputs -->
                <div class="flex items-center gap-2 text-slate-400">
                    <input type="text" readonly :value="tempStartDate ? formatDateLabel(tempStartDate) : 'Start Date'"
                           class="w-28 sm:w-36 text-center text-[12px] font-black text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-2 py-1.5 outline-none select-none">
                    <span class="text-xs font-bold">—</span>
                    <input type="text" readonly :value="tempEndDate ? formatDateLabel(tempEndDate) : 'End Date'"
                           class="w-28 sm:w-36 text-center text-[12px] font-black text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-2 py-1.5 outline-none select-none">
                </div>
                
                <!-- Cancel / Apply Buttons -->
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="button" @click="cancel()" 
                            class="flex-1 sm:flex-none px-4 py-2 text-[12px] font-black text-slate-500 hover:bg-slate-50 rounded-xl border border-slate-200 transition-all text-center">
                        Cancel
                    </button>
                    <button type="button" @click="applyCustomRange()"
                            :disabled="!isValidRange"
                            :class="!isValidRange ? 'opacity-50 cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-100 active:scale-95'"
                            class="flex-1 sm:flex-none px-5 py-2 text-[12px] font-black rounded-xl transition-all text-center">
                        Apply
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
