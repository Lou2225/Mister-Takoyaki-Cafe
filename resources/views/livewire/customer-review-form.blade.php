<div>
    @if($isSubmitted)
        <div class="bg-white rounded-3xl shadow-xl shadow-rose-100/50 p-8 text-center border border-gray-100 relative overflow-hidden transform transition-all duration-500 scale-100 opacity-100">
            <div class="w-20 h-20 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight mb-2">Thank You!</h2>
            <p class="text-[14px] text-gray-500 font-medium leading-relaxed mb-6">Your feedback has been submitted successfully. We appreciate your time!</p>
            
            <div class="absolute -right-10 -bottom-10 opacity-5 pointer-events-none">
                <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path></svg>
            </div>
        </div>
    @else
        <form wire:submit="submit" class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-6 sm:p-8 border border-gray-100 transition-all">
            
            <div class="mb-8 text-center pb-6 border-b border-gray-50">
                <h1 class="text-xl font-black tracking-tight text-gray-900 mb-1">{{ tap($title) ? $title : 'How was your experience?' }}</h1>
                <p class="text-[13px] text-gray-500 font-medium">{{ tap($subtitle) ? $subtitle : 'Thank you for your feedback!' }}</p>
            </div>

            @if($branch)
                <div class="mb-6 flex items-center justify-center gap-2 px-4 py-2 bg-rose-50 rounded-lg border border-rose-100 text-rose-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span class="text-[12px] font-bold uppercase tracking-wide">{{ $branch->branch_name }}</span>
                </div>
            @endif

            <!-- Dynamic Questions Section -->
            <div class="space-y-8 mb-8">
                @foreach($questions as $index => $question)
                    <div>
                        <label class="block text-[13px] font-bold text-gray-700 mb-3 text-center sm:text-left">
                            {{ $question['text'] }}
                            @if($question['required']) <span class="text-rose-500">*</span> @endif
                        </label>

                        @if($question['type'] === 'rating')
                            <div class="flex items-center justify-center sm:justify-start gap-2 flex-row-reverse" x-data="{ hoverRating: 0 }" @mouseleave="hoverRating = 0">
                                @for($i = 5; $i >= 1; $i--)
                                    <button type="button" 
                                            wire:click="setRating({{ $index }}, {{ $i }})"
                                            @mouseover="hoverRating = {{ $i }}"
                                            aria-label="Rate {{ $i }} out of 5"
                                            class="transform transition-transform active:scale-90 focus:outline-none p-1"
                                    >
                                        <svg class="w-10 h-10 sm:w-12 sm:h-12 transition-colors duration-200" 
                                             :class="(hoverRating >= {{ $i }} || (hoverRating == 0 && {{ $answers[$index] ?? 0 }} >= {{ $i }})) ? 'text-amber-400 drop-shadow-md' : 'text-gray-200'"
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </button>
                                @endfor
                            </div>
                        @else
                            <textarea wire:model.live="answers.{{ $index }}" rows="3" 
                                class="w-full bg-gray-50 border {{ $errors->has('answers.'.$index) ? 'border-red-500 bg-red-50' : 'border-gray-200' }} text-gray-900 text-[14px] rounded-xl focus:ring-rose-500 focus:border-rose-500 block p-3.5 transition-all shadow-sm placeholder-gray-400" 
                                placeholder="Type your answer here..."></textarea>
                        @endif

                        @error("answers.$index") <span class="text-xs text-rose-500 font-bold mt-2 block">{{ $message }}</span> @enderror
                    </div>
                @endforeach
            </div>

            <!-- Optional Details -->
            <div class="space-y-5">
                <div class="pt-4 border-t border-gray-100">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-4">Optional Details</p>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="customer_name" class="block text-[13px] font-bold text-gray-700 mb-1.5">Name</label>
                            <input type="text" wire:model.live.debounce.400ms="customer_name" id="customer_name" 
                                class="w-full bg-gray-50 border {{ $errors->has('customer_name') ? 'border-red-500 bg-red-50' : 'border-gray-200' }} text-gray-900 text-[14px] rounded-xl focus:ring-rose-500 focus:border-rose-500 block px-3.5 py-2.5 transition-all shadow-sm placeholder-gray-400" 
                                placeholder="Juan Dela Cruz" x-on:input="restrictInput($event, 'name')">
                            @error('customer_name') <span class="text-xs text-rose-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="contact_number" class="block text-[13px] font-bold text-gray-700 mb-1.5">Contact Number</label>
                            <input type="tel" wire:model.live.debounce.400ms="contact_number" id="contact_number" 
                                class="w-full bg-gray-50 border {{ $errors->has('contact_number') ? 'border-red-500 bg-red-50' : 'border-gray-200' }} text-gray-900 text-[14px] rounded-xl focus:ring-rose-500 focus:border-rose-500 block px-3.5 py-2.5 transition-all shadow-sm placeholder-gray-400" 
                                placeholder="09xxxxxxxxx" x-on:input="restrictInput($event, 'phone')">
                            @error('contact_number') <span class="text-xs text-rose-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-8">
                <button type="submit" class="w-full relative inline-flex items-center justify-center px-6 py-4 overflow-hidden font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-700 transition-all shadow-md active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-rose-200 group">
                    <span wire:loading.remove wire:target="submit" class="flex items-center gap-2">
                        Submit Feedback
                        <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </span>
                    <span wire:loading wire:target="submit" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Sending...
                    </span>
                </button>
            </div>
        </form>
    @endif
</div>
