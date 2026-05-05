@props(['messages'])

@if ($messages)
    <div {{ $attributes->merge(['class' => 'text-[11px] font-medium text-red-500 mt-1.5 flex items-start gap-1.5 animate-in fade-in slide-in-from-top-1 duration-200']) }}>
        <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <ul class="space-y-0.5">
            @foreach ((array) $messages as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
