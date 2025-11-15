{{-- 
/**
 * @fileoverview Layout base que aplica metadatos SEO, carga de assets y estilos globales.
 */
--}}
@php
    $normalizedMeta = array_merge(config('seo.defaults', []), $meta);
    $metaKeywords = is_string($normalizedMeta['keywords'] ?? null)
        ? $normalizedMeta['keywords']
        : implode(', ', $normalizedMeta['keywords'] ?? []);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth antialiased" style="overflow-x: hidden;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $normalizedMeta['description'] }}">
    <meta name="keywords" content="{{ $metaKeywords }}">
    <meta name="theme-color" content="#001A35">
    <meta name="robots" content="index, follow">
        <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta property="og:title" content="{{ $normalizedMeta['title'] }}">
    <meta property="og:description" content="{{ $normalizedMeta['description'] }}">
    <meta property="og:type" content="website">
    @if(!empty($normalizedMeta['image']))
        <meta property="og:image" content="{{ $normalizedMeta['image'] }}">
        <meta name="twitter:image" content="{{ $normalizedMeta['image'] }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $normalizedMeta['title'] }}">
    <meta name="twitter:description" content="{{ $normalizedMeta['description'] }}">
    @if(!empty($normalizedMeta['canonicalUrl']))
        <link rel="canonical" href="{{ $normalizedMeta['canonicalUrl'] }}">
    @endif
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/icon-192x192.png">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
    <title>{{ $normalizedMeta['title'] }}</title>
    @vite(['resources/Css/App.css', 'resources/Js/App.js'])
    @stack('styles')
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        (function () {
            const matcher = window.matchMedia('(prefers-color-scheme: dark)');
            const applyTheme = (isDark) => {
                document.documentElement.classList.toggle('dark', Boolean(isDark));
            };

            const storedTheme = localStorage.getItem('theme');
            const shouldEnableDark = storedTheme === 'dark';
            applyTheme(shouldEnableDark);

            matcher.addEventListener('change', (event) => {
                if (!localStorage.getItem('theme')) {
                    applyTheme(event.matches);
                }
            });
        })();
    </script>
</head>
<body class="h-full" style="overflow-x: hidden; max-width: 100vw;">
    <div
        class="min-h-full"
        style="overflow-x: hidden; max-width: 100vw;"
        @if (session()->has('status') || session()->has('message'))
            data-flash-container="true"
            data-flash-status="{{ session('status') }}"
            data-flash-message="{{ e(session('message')) }}"
            data-flash-title="{{ e(session('title', '')) }}"
        @endif
    >
        {{ $slot }}
    </div>
    @stack('scripts')
</body>
</html>

