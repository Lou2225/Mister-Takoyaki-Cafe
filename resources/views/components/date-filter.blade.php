@props([
    'startModel',
    'endModel',
    'activeModel',
    'refreshAction' => null,
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
    isTwoMonth: window.matchMedia('(min-width: 1024px)').matches,
    dropdownStyle: '',

   init() {
        this.tempStartDate = this.startDate || '';
        this.tempEndDate = this.endDate || '';

        const targetDateStr = this.tempEndDate || this.tempStartDate;
        if (targetDateStr) {
            const d = new Date(targetDateStr);
            if (!isNaN(d.getTime())) {
                this.currentYear = d.getFullYear();
                this.currentMonth = d.getMonth();
            }
        }

        this.$watch('startDate', (val) => { this.tempStartDate = val || ''; });
        this.$watch('endDate',   (val) => { this.tempEndDate   = val || ''; });

        const mq = window.matchMedia('(min-width: 1024px)');
        mq.addEventListener('change', (e) => { this.isTwoMonth = e.matches; });

        // rAF-batched reposition — fires on resize AND scroll (capture: true so
        // it also catches scrolling inside any nested scrollable container,
        // not just window scroll), keeping the panel glued to the trigger.
        this._scheduled = false;
        this._onReposition = () => {
            if (this._scheduled) return;
            this._scheduled = true;
            requestAnimationFrame(() => {
                this._scheduled = false;
                this.updatePosition();
            });
        };

        this.$watch('isOpen', (open) => {
            if (open) {
                this.$nextTick(() => this.updatePosition());
                window.addEventListener('resize', this._onReposition);
                window.addEventListener('scroll', this._onReposition, true);
            } else {
                window.removeEventListener('resize', this._onReposition);
                window.removeEventListener('scroll', this._onReposition, true);
                // The panel is still fading out via x-transition:leave
                // (150ms) when this fires. Clearing dropdownStyle right
                // away snaps it back to the default top-16 sheet position
                // mid-fade — that snap is the glitch. Wait for the leave
                // transition to finish so it fades out anchored in place.
                setTimeout(() => { this.dropdownStyle = ''; }, 150);
            }
        });
    },

    destroy() {
        window.removeEventListener('resize', this._onReposition);
        window.removeEventListener('scroll', this._onReposition, true);
    },

    updatePosition() {
        // If not open, clear inline style and return
        if (!this.isOpen || !this.$refs.trigger) {
            this.dropdownStyle = '';
            return;
        }

        // Anchor to the trigger's live viewport position on every device —
        // phone, tablet, and desktop all use the same math now. Re-run on
        // every scroll/resize tick (see _onReposition above); since the
        // panel is `fixed`, viewport coords are correct and the panel
        // tracks the button as the page scrolls.
        const rect = this.$refs.trigger.getBoundingClientRect();

        // document.documentElement.clientWidth excludes the scrollbar,
        // unlike window.innerWidth — using innerWidth here was letting the
        // panel's right edge land past the true visible area on some
        // devices, clipping the last button in the footer row.
        const viewportWidth = document.documentElement.clientWidth;

        // Cap the panel width to the viewport (minus a 16px gutter on each
        // side) so it can never be wider than the screen on narrow phones.
        const desiredWidth = this.isTwoMonth ? 780 : 340;
        const panelWidth = Math.min(desiredWidth, viewportWidth - 32);

        // Align panel's right edge with trigger's right edge
        let left = rect.right - panelWidth;

        const minLeft = 16;
        if (left < minLeft) left = minLeft;

        const maxLeft = viewportWidth - panelWidth - 16;
        if (left > maxLeft) left = maxLeft;

        // Flip above the trigger when there isn't enough room below.
        // This matters most on pages like KDS History, where the trigger
        // sits well down the layout (title, tabs, stat cards, table
        // header all come before it) — with no flip, the panel opens
        // downward regardless and gets pushed against/off the bottom edge.
        const gutter = 16;
        const viewportHeight = document.documentElement.clientHeight;
        const panelHeight = this.$refs.panel ? this.$refs.panel.offsetHeight : 0;
        const spaceBelow = viewportHeight - rect.bottom - gutter;
        const spaceAbove = rect.top - gutter;

        let top;
        if (panelHeight && panelHeight > spaceBelow && spaceAbove > spaceBelow) {
            // More room above than below, and it doesn't fit below — flip up.
            top = Math.max(gutter, rect.top - panelHeight - 8);
        } else {
            top = rect.bottom + 8; // default: place under the trigger
        }

        this.dropdownStyle = `top: ${top}px; left: ${left}px; width: ${panelWidth}px;`;
    },
    
    get formattedActiveLabel() {
        if (this.startDate && this.endDate) {
            return this.formatDateLabel(this.startDate) + ' — ' + this.formatDateLabel(this.endDate);
        }
        return 'All Time';
    },

    formatDateLabel(dateStr) {
        if (!dateStr) return '';
        const [y, m, d] = dateStr.split('-').map(Number);
        const date = new Date(y, m - 1, d);
        if (isNaN(date.getTime())) return '';
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return months[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear();
    },

    nextMonths() {
        const today = new Date();
        const alreadyAtToday = (
            this.currentYear === today.getFullYear() &&
            this.currentMonth === today.getMonth()
        );
        if (alreadyAtToday) return;

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
        if (!this.isTwoMonth) {
            return [this.getMonthDays(this.currentYear, this.currentMonth)];
        }
        let m1 = this.currentMonth - 1;
        let y1 = this.currentYear;
        if (m1 < 0) { m1 = 11; y1--; }
        return [this.getMonthDays(y1, m1), this.getMonthDays(this.currentYear, this.currentMonth)];
    },

    isFuture(dateStr) {
        if (!dateStr) return false;
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const [y, m, d] = dateStr.split('-').map(Number);
        return new Date(y, m - 1, d) > today;
    },

    get isValidRange() {
        if (!this.tempStartDate || !this.tempEndDate) return false;
        const [sy, sm, sd] = this.tempStartDate.split('-').map(Number);
        const [ey, em, ed] = this.tempEndDate.split('-').map(Number);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const start = new Date(sy, sm - 1, sd);
        const end   = new Date(ey, em - 1, ed);
        return start <= today && end <= today && end >= start;
    },

    getMonthDays(year, month) {
        const monthNames = [
            'January','February','March','April','May','June',
            'July','August','September','October','November','December'
        ];
        const daysInMonth  = new Date(year, month + 1, 0).getDate();
        let firstDayIndex  = new Date(year, month, 1).getDay();
        firstDayIndex = firstDayIndex === 0 ? 6 : firstDayIndex - 1;

        const days = [];
        for (let i = 0; i < firstDayIndex; i++) days.push({ day: null, dateStr: null });
        for (let d = 1; d <= daysInMonth; d++) {
            const padMonth = String(month + 1).padStart(2, '0');
            const padDay   = String(d).padStart(2, '0');
            days.push({ day: d, dateStr: `${year}-${padMonth}-${padDay}` });
        }
        return { name: monthNames[month] + ' ' + year, days, year, month };
    },

    selectDate(dateStr) {
        if (this.isFuture(dateStr)) return;
        if (!this.tempStartDate || (this.tempStartDate && this.tempEndDate)) {
            this.tempStartDate = dateStr;
            this.tempEndDate   = '';
        } else {
            if (new Date(dateStr) < new Date(this.tempStartDate)) {
                this.tempEndDate   = this.tempStartDate;
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
        const [y,  m,  d]  = dateStr.split('-').map(Number);
        const [sy, sm, sd] = this.tempStartDate.split('-').map(Number);
        const [ey, em, ed] = this.tempEndDate.split('-').map(Number);
        const date  = new Date(y,  m  - 1, d);
        const start = new Date(sy, sm - 1, sd);
        const end   = new Date(ey, em - 1, ed);
        return date > start && date < end;
    },

    isStart(dateStr) { return this.tempStartDate === dateStr; },
    isEnd(dateStr)   { return this.tempEndDate   === dateStr; },

    applyPreset(preset) {
        const today = new Date();
        let start, end, label;

        switch(preset) {
            case 'today':
                start = this.formatDate(today);
                end   = this.formatDate(today);
                label = 'Today';
                break;
            case 'yesterday':
                const yesterday = new Date(today);
                yesterday.setDate(today.getDate() - 1);
                start = this.formatDate(yesterday);
                end   = this.formatDate(yesterday);
                label = 'Yesterday';
                break;
            case 'week':
                const lastWeek = new Date(today);
                lastWeek.setDate(today.getDate() - 6);
                start = this.formatDate(lastWeek);
                end   = this.formatDate(today);
                label = 'This week';
                break;
            case 'last_week':
                const day = today.getDay();
                const diffToMon = day === 0 ? 13 : day + 6;
                const diffToSun = day === 0 ? 7  : day;
                const pMon = new Date(today); pMon.setDate(today.getDate() - diffToMon);
                const pSun = new Date(today); pSun.setDate(today.getDate() - diffToSun);
                start = this.formatDate(pMon);
                end   = this.formatDate(pSun);
                label = 'Last week';
                break;
            case 'month':
                const lastMonth = new Date(today);
                lastMonth.setDate(today.getDate() - 29);
                start = this.formatDate(lastMonth);
                end   = this.formatDate(today);
                label = 'This month';
                break;
            case 'last_month':
                const prevMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const prevMonthEnd   = new Date(today.getFullYear(), today.getMonth(), 0);
                start = this.formatDate(prevMonthStart);
                end   = this.formatDate(prevMonthEnd);
                label = 'Last month';
                break;
            case 'year':
                const thisYearStart = new Date(today.getFullYear(), 0, 1);
                start = this.formatDate(thisYearStart);
                end   = this.formatDate(today);
                label = 'This year';
                break;
            case 'last_year':
                const lastYearStart = new Date(today.getFullYear() - 1, 0,  1);
                const lastYearEnd   = new Date(today.getFullYear() - 1, 11, 31);
                start = this.formatDate(lastYearStart);
                end   = this.formatDate(lastYearEnd);
                label = 'Last year';
                break;
            case 'all':
                start = '';
                end   = '';
                label = 'All time';
                break;
        }

        this.tempStartDate = start;
        this.tempEndDate   = end;
        this.activeFilter  = label;

        const refreshAct = '{{ $refreshAction }}';
        if (refreshAct) {
            this.$wire.set('{{ $startModel }}', start, false);
            this.$wire.set('{{ $endModel }}',   end,   false);
            this.$wire.set('{{ $activeModel }}', label, false);
            this.$wire.call(refreshAct);
        } else {
            this.$wire.set('{{ $startModel }}', start, false);
            this.$wire.set('{{ $endModel }}',   end,   false);
            this.$wire.set('{{ $activeModel }}', label, true);
        }

        this.isOpen = false;
    },

    formatDate(date) {
        const year  = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day   = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    },

    applyCustomRange() {
        if (!this.isValidRange) return;
        this.activeFilter = 'Custom Range';
        this.startDate    = this.tempStartDate;
        this.endDate      = this.tempEndDate;

        const refreshAct = '{{ $refreshAction }}';
        if (refreshAct) {
            this.$wire.set('{{ $startModel }}', this.tempStartDate, false);
            this.$wire.set('{{ $endModel }}',   this.tempEndDate,   false);
            this.$wire.set('{{ $activeModel }}', 'Custom Range',    false);
            this.$wire.call(refreshAct);
        } else {
            this.$wire.set('{{ $startModel }}', this.tempStartDate, false);
            this.$wire.set('{{ $endModel }}',   this.tempEndDate,   false);
            this.$wire.set('{{ $activeModel }}', 'Custom Range',    true);
        }

        this.isOpen = false;
    },

    cancel() {
        this.tempStartDate = this.startDate || '';
        this.tempEndDate   = this.endDate   || '';
        this.isOpen        = false;
    }
<<<<<<< HEAD
}" @click.outside="cancel()" class="relative select-none">

    {{-- Trigger Button --}}
    <button type="button" x-ref="trigger" @click.stop="isOpen = !isOpen"
    class="inline-flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-4 text-[11px] sm:text-[13px] font-black text-gray-700 bg-white border border-gray-200 rounded-xl shadow-sm hover:bg-gray-50 h-10 transition-all select-none">
=======
}" @click.away="cancel()" class="relative select-none" wire:ignore>
    {{-- Collapsed Trigger Button --}}
    <button type="button" @click="isOpen = !isOpen" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-black text-gray-700 bg-white border border-gray-200 rounded-xl shadow-sm hover:bg-gray-50 h-10 transition-all select-none">
>>>>>>> 8ad7217b87e6ff71676d665e65e7079c934664b2
        <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span x-text="formattedActiveLabel">All Time</span>
        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0 transition-transform duration-300"
             :class="isOpen ? 'rotate-180' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    {{-- Mobile backdrop --}}
    <div x-show="isOpen" x-cloak @click="cancel()"
         x-transition.opacity
         class="fixed inset-0 bg-black/20 z-40 sm:hidden"></div>

    {{-- Dropdown Panel --}}
   <div x-show="isOpen"
     x-ref="panel"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-2 scale-95"
     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
     x-transition:leave-end="opacity-0 translate-y-2 scale-95"
     @click.stop
     :class="isTwoMonth ? 'lg:min-w-[780px] lg:max-w-none' : ''"
     :style="dropdownStyle"
     class="fixed
       w-auto
       max-w-none
       bg-white rounded-2xl shadow-2xl border border-gray-100
       p-3 sm:p-6
       z-50
       flex flex-col
       gap-3 sm:gap-6
       max-h-[calc(100vh-80px)]
       overflow-y-auto
       select-none
       lg:w-[780px]"
     x-cloak>

        {{-- Left pane: Presets --}}
        <div class="w-full shrink-0 flex flex-row overflow-x-auto border-b border-slate-100 pb-2 gap-1">
            <template x-for="preset in [
                { id: 'today',      name: 'Today' },
                { id: 'yesterday',  name: 'Yesterday' },
                { id: 'week',       name: 'This week' },
                { id: 'last_week',  name: 'Last week' },
                { id: 'month',      name: 'This month' },
                { id: 'last_month', name: 'Last month' },
                { id: 'year',       name: 'This year' },
                { id: 'last_year',  name: 'Last year' },
                { id: 'all',        name: 'All time' }
            ]">
                <button type="button" @click.stop="applyPreset(preset.id)"
        :class="activeFilter === preset.name ? 'bg-indigo-50 text-indigo-700 font-black' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-medium'"
        class="w-full text-left px-2 py-1.5 text-[11px] sm:px-3 sm:py-2 sm:text-[12px] rounded-lg transition-all whitespace-nowrap">
                    <span x-text="preset.name"></span>
                </button>
            </template>
        </div>

        {{-- Right pane: Calendars --}}
        <div class="w-full flex-1 min-w-0 flex flex-col gap-4">
            <div class="grid gap-6" :class="isTwoMonth ? 'grid-cols-2' : 'grid-cols-1'">
                <template x-for="(monthData, mIdx) in getMonths()">
                    <div class="flex-1">  
                        <div class="flex items-center justify-between mb-4 px-1 select-none">
                            <div class="w-8 h-8 flex items-center justify-start">
                                <button type="button" @click.stop="prevMonths()"
                                        x-show="!isTwoMonth || mIdx === 0"
                                        class="p-1 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                            </div>

                            <span class="text-[13px] font-black text-slate-900" x-text="monthData.name"></span>

                            <div class="w-8 h-8 flex items-center justify-end">
                                <button type="button" @click.stop="nextMonths()"
                                        :disabled="currentYear === new Date().getFullYear() && currentMonth === new Date().getMonth()"
                                        x-show="!isTwoMonth || mIdx === 1"
                                        :class="(currentYear === new Date().getFullYear() && currentMonth === new Date().getMonth()) ? 'opacity-30 cursor-not-allowed' : ''"
                                        class="p-1 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-7 gap-1 text-center mb-2">
                            <template x-for="dayName in ['Mo','Tu','We','Th','Fr','Sa','Su']">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider" x-text="dayName"></span>
                            </template>
                        </div>

                        <div class="grid grid-cols-7 gap-y-1.5 gap-x-0 w-full">
                            <template x-for="d in monthData.days">
                                <div class="relative h-8 sm:h-8 w-full flex items-center justify-center">
                                    <div class="absolute inset-y-1.5 left-0 right-0 z-0 transition-all pointer-events-none"
                                         :class="[ 
                                             isInRange(d.dateStr) ? 'bg-indigo-50/70' : '',
                                             isStart(d.dateStr) && tempEndDate ? 'bg-indigo-50/70 rounded-l-full left-1/2' : '',
                                             isEnd(d.dateStr) && tempStartDate ? 'bg-indigo-50/70 rounded-r-full right-1/2' : ''
                                         ]">
                                    </div>
                                    <button type="button"
                                            @pointerdown.stop
                                            @click.stop="d.day && !isFuture(d.dateStr) ? selectDate(d.dateStr) : null"
                                            :disabled="!d.day || isFuture(d.dateStr)"
                                            :class="[
                                                isSelected(d.dateStr)
                                                    ? 'bg-indigo-600 text-white font-black shadow-lg shadow-indigo-100 z-10 pointer-events-auto'
                                                    : (d.day
                                                        ? (isFuture(d.dateStr)
                                                            ? 'text-slate-300 opacity-30 cursor-not-allowed pointer-events-none'
                                                            : 'text-slate-700 hover:bg-slate-100 z-10 pointer-events-auto')
                                                        : 'text-transparent cursor-default'),
                                                d.day ? 'w-8 h-8 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-[11px] sm:text-[12px] font-bold transition-all transform active:scale-95' : ''
                                            ]">
                                        <span x-text="d.day || ''"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Bottom: Inputs & Actions --}}
            <div class="border-t border-slate-100 pt-3 flex flex-col lg:flex-row items-center justify-between gap-2 mt-2 shrink-0">
                <div class="flex items-center gap-2 text-slate-400">
                    <input type="text" readonly
                           :value="tempStartDate ? formatDateLabel(tempStartDate) : 'Start Date'"
                           class="w-24 sm:w-36 text-center text-[11px] sm:text-[12px] font-black text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-2 py-1.5 outline-none select-none">
                    <span class="text-xs font-bold">—</span>
                    <input type="text" readonly
                           :value="tempEndDate ? formatDateLabel(tempEndDate) : 'End Date'"
                           class="w-24 sm:w-36 text-center text-[11px] sm:text-[12px] font-black text-slate-800 bg-slate-50 border border-slate-200 rounded-xl px-2 py-1.5 outline-none select-none">
                </div>
                <div class="flex items-center gap-2 w-full lg:w-auto">
                    <button type="button" @click.stop="cancel()"
                            class="flex-1 lg:flex-none px-4 py-2 text-[12px] font-black text-slate-500 hover:bg-slate-50 rounded-xl border border-slate-200 transition-all text-center">
                        Cancel
                    </button>
                    <button type="button" @click.stop="applyCustomRange()"
                            :disabled="!isValidRange"
                            :class="!isValidRange ? 'opacity-50 cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-100 active:scale-95'"
                            class="flex-1 lg:flex-none px-5 py-2 text-[12px] font-black rounded-xl transition-all text-center">
                        Apply
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>