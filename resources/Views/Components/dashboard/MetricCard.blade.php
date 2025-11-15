{{-- 
/**
 * @fileoverview Tarjeta de métrica reutilizable para resúmenes clave.
 */
--}}
@props([
    'label' => '',
    'value' => '',
    'trend' => '',
])

<article class="flex flex-col rounded-2xl border border-surface bg-surface-elevated p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
    <span class="text-sm font-medium text-secondary">{{ $label }}</span>
    <span class="mt-3 text-3xl font-semibold text-heading">{{ $value }}</span>
    @if($trend)
        <span class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-emerald-500">
            <i class="fa-solid fa-arrow-trend-up text-xs"></i>
            {{ $trend }}
        </span>
    @endif
</article>

