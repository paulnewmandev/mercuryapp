@props(['unreadCount' => 0])

@php
    $chatUrl = '/chat';
    try {
        if (Route::has('chat.index')) {
            $chatUrl = route('chat.index');
        }
    } catch (\Exception $e) {
        // Si hay error, usar URL por defecto
        $chatUrl = '/chat';
    }
@endphp

<button
    onclick="window.location.href='{{ $chatUrl }}'"
    class="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-primary shadow-lg transition-all hover:scale-110 hover:shadow-xl"
    aria-label="Abrir chat"
>
    <i class="fa-solid fa-comments text-white text-xl"></i>
    @if($unreadCount > 0)
        <span class="absolute -top-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white">
            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
        </span>
    @endif
</button>

