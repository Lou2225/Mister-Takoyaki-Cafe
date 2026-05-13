@extends('layouts.app')

@push('scripts')
@endpush

@section('content')

<div class="p-10 flex flex-col items-center justify-center text-center min-h-[50vh]">
    <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
    </div>
    
    <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $title }}</h1>
    
    <p class="text-gray-500 max-w-sm mb-6">
        This module is currently under construction. Stay tuned, exciting features for <strong>{{ strtolower($title) }}</strong> are coming soon!
    </p>

    <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white rounded-xl text-sm font-semibold hover:bg-gray-800 transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Return to dashboard
    </a>
</div>

@endsection
