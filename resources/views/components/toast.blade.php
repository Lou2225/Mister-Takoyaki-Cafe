<div
    x-data="{
        notifications: [],
        add(notificationDetail) {
            // Livewire 2 sometimes wraps event detail in an array
            const notification = notificationDetail?.type ? notificationDetail : (notificationDetail?.[0]?.type ? notificationDetail[0] : notificationDetail);
            
            const id = Math.random().toString(36).substr(2, 9);
            this.notifications.push({
                id: id,
                type: notification.type || 'info',
                message: notification.message || '',
                duration: notification.duration || 4000
            });
            setTimeout(() => {
                this.remove(id);
            }, notification.duration || 4000);
            
            // If this is a success notification, close the associated modal after a short delay
            if (notification.type === 'success') {
                setTimeout(() => {
                    const modalToClose = notification.modal || 'confirm-save-user';
                    window.dispatchEvent(new CustomEvent('close-modal', { 
                        detail: modalToClose
                    }));
                    
                    // Also close conflict modal if it was the one open
                    if (!notification.modal) {
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirm-manager-replace' }));
                    }
                }, 500); // Give toast time to appear before closing modal
            }
        },
        remove(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
        }
    }"
    @notify.window="add($event.detail)"
    x-init="
        @if(session('success'))
            add({ type: 'success', message: '{{ session('success') }}' });
        @endif
        @if(session('error'))
            add({ type: 'error', message: '{{ session('error') }}' });
        @endif
    "
    class="fixed bottom-6 right-6 flex flex-col items-end gap-3 z-[10000] pointer-events-none"
>
    <template x-for="notification in notifications" :key="notification.id">
        <div
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-y-4 opacity-0 scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="pointer-events-auto flex items-center gap-3 bg-white border border-gray-100 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.08)] px-5 py-3.5 min-w-[320px] max-w-md overflow-hidden relative group"
        >
            {{-- Left Accent --}}
            <div :class="{
                'bg-green-500': notification.type === 'success',
                'bg-red-500': notification.type === 'error',
                'bg-blue-500': notification.type === 'info'
            }" class="absolute left-0 top-0 bottom-0 w-1.5 h-full"></div>

            {{-- Icon --}}
            <div :class="{
                'bg-green-50 text-green-600 border-green-100': notification.type === 'success',
                'bg-red-50 text-red-600 border-red-100': notification.type === 'error',
                'bg-blue-50 text-blue-600 border-blue-100': notification.type === 'info'
            }" class="w-10 h-10 rounded-xl border flex items-center justify-center flex-shrink-0">
                
                {{-- Success Icon --}}
                <template x-if="notification.type === 'success'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </template>

                {{-- Error Icon --}}
                <template x-if="notification.type === 'error'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </template>

                {{-- Info Icon --}}
                <template x-if="notification.type === 'info'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </div>

            {{-- Body --}}
            <div class="flex-1">
                <p class="text-[14px] font-bold text-gray-900 leading-tight" x-text="notification.type.charAt(0).toUpperCase() + notification.type.slice(1)"></p>
                <p class="text-[12px] text-gray-500 mt-0.5" x-text="notification.message"></p>
            </div>

            {{-- Close Button --}}
            <button @click="remove(notification.id)" class="text-gray-400 hover:text-gray-600 transition-colors opacity-0 group-hover:opacity-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
