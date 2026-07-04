@props([
    'id',
    'title' => '',
    'maxWidth' => 'md',
    'show' => 'false'
])

@php
$maxWidthClass = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
][$maxWidth] ?? 'max-w-md';
@endphp

<div 
    x-data="{ open: {{ $show }} }"
    x-show="open"
    x-effect="document.body.style.overflow = open ? 'hidden' : ''"
    x-on:close-modal.window="if ($event.detail === '{{ $id }}') open = false"
    x-on:open-modal.window="if ($event.detail === '{{ $id }}') open = true"
    x-on:keydown.escape.window="open = false"
    class="fixed inset-0 z-[1060] overflow-y-auto"
    style="display: none;"
>
    <!-- Backdrop Overlay -->
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm"
        x-on:click="open = false"
    ></div>

    <!-- Modal Content Box Wrapper (Centered) -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div 
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full {{ $maxWidthClass }} overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800"
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                    {{ $title }}
                </h3>
                <button 
                    x-on:click="open = false" 
                    type="button" 
                    class="rounded-lg p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="mt-4">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
