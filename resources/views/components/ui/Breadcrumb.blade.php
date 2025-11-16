{{-- 
/**
 * @fileoverview Componente reutilizable para breadcrumbs dentro del dashboard.
 */
--}}

@props([
    'title' => '',
    'items' => [],
])

<div class="flex flex-wrap items-center justify-between gap-3 pb-6">
    <h2 class="text-xl font-semibold text-heading">{{ $title }}</h2>
    @if(!empty($items))
        <nav
            aria-label="{{ gettext('Ruta de navegación') }}"
            class="hidden w-full sm:flex sm:w-auto sm:items-center sm:justify-end"
        >
            <ol class="flex items-center gap-1.5 text-sm">
                @foreach($items as $index => $item)
                    <li class="inline-flex items-center gap-1.5">
                        @if(!empty($item['url']) && $index !== count($items) - 1)
                            <a
                                href="{{ $item['url'] }}"
                                class="inline-flex items-center gap-1.5 text-sm text-secondary transition hover:text-primary"
                            >
                                {{ $item['label'] }}
                                <i class="fa-solid fa-chevron-right text-[11px]"></i>
                            </a>
                        @else
                            <span class="text-sm font-medium text-heading">
                                {{ $item['label'] }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif
</div>

