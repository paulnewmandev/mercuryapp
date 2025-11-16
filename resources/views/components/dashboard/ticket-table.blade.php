{{-- 
/**
 * @fileoverview Tabla compacta para mostrar tickets recientes en el dashboard.
 */
--}}
@props([
    'tickets' => [],
])

<section class="rounded-2xl border border-surface bg-surface-elevated p-6 shadow-sm transition-colors">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-heading">{{ gettext('Latest tickets') }}</h2>
            <p class="text-sm text-secondary">Seguimiento rápido de las órdenes más recientes.</p>
        </div>
        <a href="#tickets" class="text-sm font-medium text-primary hover:text-primary-strong">
            {{ gettext('View all') }}
        </a>
    </div>
    <div class="mt-6 overflow-x-auto">
        <table class="min-w-full divide-y divide-surface text-sm">
            <thead class="bg-surface-muted text-left text-xs font-semibold uppercase tracking-wide text-secondary">
                <tr>
                    <th class="px-4 py-3">{{ gettext('Ticket ID') }}</th>
                    <th class="px-4 py-3">{{ gettext('Status') }}</th>
                    <th class="px-4 py-3">{{ gettext('Customer') }}</th>
                    <th class="px-4 py-3">{{ gettext('Device') }}</th>
                    <th class="px-4 py-3 text-right">{{ gettext('Updated') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface text-secondary">
                @foreach($tickets as $ticket)
                    <tr class="transition hover:bg-surface-muted">
                        <td class="px-4 py-4 font-semibold text-heading">{{ $ticket['id'] }}</td>
                        <td class="px-4 py-4">
                            <span class="inline-flex rounded-full bg-primary-soft px-3 py-1 text-xs font-semibold">
                                {{ ucfirst(str_replace('_', ' ', $ticket['status'])) }}
                            </span>
                        </td>
                        <td class="px-4 py-4">{{ $ticket['customer'] }}</td>
                        <td class="px-4 py-4">{{ $ticket['device'] }}</td>
                        <td class="px-4 py-4 text-right text-secondary">{{ $ticket['updated_at'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

