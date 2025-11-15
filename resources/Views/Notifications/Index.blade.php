{{-- 
/**
 * @fileoverview Listado de notificaciones para el usuario autenticado.
 */
--}}

@php
    $breadcrumbItems = [
        ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
        ['label' => gettext('Notificaciones')],
    ];
@endphp

<x-layouts.dashboard-layout :meta="['title' => 'Notificaciones']">
    <x-ui.breadcrumb :title="gettext('Notificaciones')" :items="$breadcrumbItems" />
    <section class="space-y-6">
        <div class="rounded-lg border border-surface bg-surface-elevated p-6 shadow-sm">

            <ul class="mt-6 grid gap-4" data-notification-list-page>
                @forelse($notifications as $notification)
                    <li
                        class="notification-item notification-list-item relative flex flex-col gap-4 rounded-md border border-surface bg-surface-elevated p-5 shadow-sm transition hover:border-primary/40 {{ $notification->read_at ? 'is-read opacity-80' : 'is-unread' }}"
                        data-notification-id="{{ $notification->id }}"
                        data-is-read="{{ $notification->read_at ? 'true' : 'false' }}"
                        data-read-url="{{ route('notifications.read', $notification) }}"
                        data-delete-url="{{ route('notifications.destroy', $notification) }}"
                        role="button"
                        tabindex="0"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex flex-1 items-start gap-3">
                                <span class="notification-dot mt-1 h-2.5 w-2.5 rounded-full {{ $notification->read_at ? 'bg-surface-muted' : 'bg-primary' }}"></span>
                                <div class="flex-1 space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 data-notification-title class="text-base text-heading {{ $notification->read_at ? 'font-medium' : 'font-semibold' }}">
                                            {{ $notification->title ?? gettext('MercuryApp') }}
                                        </h4>
                                        @unless($notification->read_at)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-primary-soft px-2 py-0.5 text-[11px] font-semibold text-primary">
                                                <i class="fa-solid fa-sparkles"></i>{{ gettext('Nuevo') }}
                                            </span>
                                        @endunless
                                    </div>
                                    <p data-notification-description class="notification-description text-sm {{ $notification->read_at ? 'text-secondary' : 'text-heading font-medium' }}">
                                        {{ $notification->description }}
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="notification-list-delete -mt-1 text-xs text-secondary transition hover:text-rose-500"
                                aria-label="{{ gettext('Eliminar notificación') }}"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="flex items-center justify-between text-xs text-secondary">
                            <span class="inline-flex items-center gap-2">
                                <i class="fa-regular fa-clock"></i>
                                {{ optional($notification->created_at)->diffForHumans() }}
                            </span>
                            <span class="inline-flex items-center gap-2">
                                <i class="fa-regular fa-user"></i>
                                {{ $notification->user?->first_name ? $notification->user->first_name . ' ' . $notification->user->last_name : gettext('Sistema') }}
                            </span>
                        </div>
                    </li>
                @empty
                    <li class="rounded-2xl border border-dashed border-surface px-6 py-16 text-center text-sm text-secondary">
                        {{ gettext('Aún no tienes notificaciones registradas.') }}
                    </li>
                @endforelse
            </ul>

            <div class="mt-6 border-t border-surface pt-4">
                {{ $notifications->links() }}
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>

