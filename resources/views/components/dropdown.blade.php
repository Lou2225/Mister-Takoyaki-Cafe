@props(['align' => 'right', 'width' => '56', 'contentClasses' => 'py-1 bg-white', 'containerClasses' => 'inline-block', 'wireKey' => null])

@php
switch ($align) {
    case 'left':
        $alignmentClasses = 'origin-top-left left-0';
        break;
    case 'top':
        $alignmentClasses = 'origin-bottom bottom-full mb-2 left-0';
        break;
    case 'right':
    default:
        $alignmentClasses = 'origin-top-right right-0';
        break;
}

switch ($width) {
    case '56':
        $widthClass = 'w-56';
        break;
    case 'full':
        $widthClass = 'w-full';
        break;
    default:
        $widthClass = 'w-' . $width;
        break;
}
@endphp
<div
     {{ $wireKey ? 'wire:key='.$wireKey : '' }}
     x-data="{ dropdownOpen: false }" 
     :class="dropdownOpen ? 'z-40' : ''"
     @click.away="dropdownOpen = false" 
     @close.stop="dropdownOpen = false" 
     data-has-alpine-state="true"
     {{ $attributes->merge(['class' => 'relative ' . $containerClasses . ' text-left']) }} >
    <div @click="dropdownOpen = !dropdownOpen" x-ref="trigger" class="relative z-0 w-full">
        {{ $trigger }}
    </div>

    <div x-show="dropdownOpen" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="transform opacity-0 -translate-y-1" 
         x-transition:enter-end="transform opacity-100 translate-y-0" 
         x-transition:leave="transition ease-in duration-150" 
         x-transition:leave-start="transform opacity-100 translate-y-0" 
         x-transition:leave-end="transform opacity-0 -translate-y-1" 
         class="absolute z-50 mt-2 {{ $alignmentClasses }} {{ $widthClass }} rounded-lg border border-slate-200 shadow-lg {{ $contentClasses }}" style="display: none;">
        <div class="rounded-lg bg-white w-full">
            {{ $content }}
        </div>
    </div>
</div>
