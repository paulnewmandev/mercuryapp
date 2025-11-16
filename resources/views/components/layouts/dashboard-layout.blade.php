{{-- 
/**
 * @fileoverview Layout para el dashboard con sidebar y topbar reutilizables.
 */
--}}
<x-layouts.base-layout :meta="$meta">
    <div class="flex min-h-screen bg-surface transition-colors" style="overflow-x: hidden; max-width: 100vw;">
        <x-navigation.sidebar />
        <div class="flex flex-1 flex-col md:ml-0" data-content-area style="overflow-x: hidden; max-width: 100%;">
            <div
                data-sidebar-overlay
                class="fixed inset-0 z-30 hidden bg-black/40 backdrop-blur-sm md:hidden"
            ></div>
            <x-navigation.topbar />
            <main class="flex-1 overflow-y-auto overflow-x-hidden px-6 py-8" style="max-width: 100vw; box-sizing: border-box;">
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.base-layout>

