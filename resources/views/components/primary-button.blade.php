@props(['type' => 'submit', 'variant' => 'solid'])

@php
    $theme = auth()->user()?->getRoleTheme() ?? [
        'primary'    => 'indigo',
        'bg_button'  => 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500',
        'border'     => 'border-indigo-600',
        'text'       => 'text-indigo-600',
        'hover_bg'   => 'bg-indigo-50',
    ];
    
    $primaryColor = $theme['primary'];
    $bgButton = $theme['bg_button'];
    $borderColor = $theme['border'];
    $textColor = $theme['text'];
    $hoverBg = $theme['hover_bg'];
    
    $baseClasses = "inline-flex items-center px-4 py-2 rounded-xl font-black text-[12px] shadow-sm transition-all duration-200 transform active:scale-95 disabled:opacity-25 focus:outline-none focus:ring-2 focus:ring-offset-2";
    
    if ($variant === 'solid') {
        $variantClasses = "{$bgButton} text-white border-none";
    } else {
        $variantClasses = "bg-white border-2 {$borderColor} {$textColor} hover:{$hoverBg} focus:ring-{$primaryColor}-500";
    }
@endphp

<button {{ $attributes->merge(['type' => $type, 'class' => "{$baseClasses} {$variantClasses}"]) }}>
    {{ $slot }}
</button>

