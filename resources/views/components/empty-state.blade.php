@props([
    'title' => 'No records found',
    'description' => 'Try adjusting your search or filters to find what you are looking for.',
    'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    'columnSpan' => 5,
    'compact' => false
])

<div class="w-full flex flex-col items-center justify-center {{ $compact ? 'py-6 px-4' : 'py-20 animate-fadeIn' }}">
    <div class="{{ $compact ? 'w-12 h-12 mb-3' : 'w-20 h-20 mb-6' }} rounded-full bg-gray-50 flex items-center justify-center border border-gray-100 shadow-sm transition-transform hover:scale-110 duration-500">
        <svg class="{{ $compact ? 'w-6 h-6' : 'w-10 h-10' }} text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icon }}" />
        </svg>
    </div>
    <h3 class="{{ $compact ? 'text-[13px]' : 'text-[15px]' }} font-black text-gray-900 mb-1 tracking-tight">{{ $title }}</h3>
    @if($description)
        <p class="{{ $compact ? 'text-[11px]' : 'text-[12px]' }} text-gray-400 font-medium max-w-[280px] text-center leading-relaxed italic">
            {{ $description }}
        </p>
    @endif
    
    @isset($action)
        <div class="{{ $compact ? 'mt-3' : 'mt-6' }}">
            {{ $action }}
        </div>
    @endisset
</div>
