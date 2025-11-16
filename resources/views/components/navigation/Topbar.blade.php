{{-- 
/**
 * @fileoverview Barra superior con acciones rápidas y buscador global.
 */
--}}

@php
    use App\Models\Notification;

    $user = auth()->user();
    $avatar = $user?->avatar_url ?: '/theme-images/user/owner.jpg';
    $notifications = collect();
    $unreadCount = 0;
    $totalCount = 0;

    if ($user) {
        $notifications = Notification::query()
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at')
            ->where(function ($query) use ($user): void {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->latest('created_at')
            ->limit(6)
            ->get();

        $totalCount = $notifications->count();
        $unreadCount = $notifications->whereNull('read_at')->count();
    }
@endphp

<header class="sticky top-0 z-20 border-b border-surface backdrop-blur" style="background-color: color-mix(in srgb, var(--surface-elevated) 85%, transparent);">
    <div class="flex items-center justify-between gap-6 py-4 pl-4 pr-4 sm:pl-6 sm:pr-6 lg:pl-0 lg:pr-6">
        <div class="flex items-center gap-3">
            <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-secondary transition hover:text-primary ml-2 lg:ml-3"
                data-sidebar-open
                aria-label="{{ gettext('Mostrar menú lateral') }}"
            >
                <i class="fa-solid fa-bars text-lg"></i>
            </button>
        </div>
        <div class="ml-auto flex items-center gap-3">
            <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-transparent text-secondary transition hover:text-primary"
                data-theme-toggle
                aria-label="{{ gettext('Cambiar tema') }}"
                aria-pressed="false"
            >
                <i data-theme-icon="light" class="fa-regular fa-sun text-base" style="display:inline;"></i>
                <i data-theme-icon="dark" class="fa-regular fa-moon text-base" style="display:none;"></i>
            </button>
            {{-- Notificaciones ocultas temporalmente --}}
            <div class="relative hidden" data-dropdown="notifications">
                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-transparent text-secondary transition hover:text-primary"
                    data-dropdown-trigger
                    aria-haspopup="true"
                    aria-expanded="false"
                    aria-label="{{ gettext('Notificaciones') }}"
                >
                    <i class="fa-regular fa-bell text-base"></i>
                    @if($unreadCount > 0)
                        <span class="notification-indicator absolute right-2 top-1 inline-flex h-2.5 w-2.5 rounded-full border-2 border-surface bg-rose-500"></span>
                    @endif
                </button>
                <div
                     data-dropdown-panel
                    class="notifications-dropdown z-40 hidden overflow-hidden rounded-md border border-surface bg-surface-elevated shadow-2xl"
                    hidden
                >
                    <div class="px-5 pt-4 pb-3 text-center sm:px-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-heading sm:text-sm">{{ gettext('Notificaciones') }}</p>
                    </div>
                    <ul class="notification-scroll max-h-80 divide-y divide-surface overflow-y-auto px-0 pb-4 text-xs text-secondary sm:text-sm" data-notification-list>
                        @forelse($notifications as $notification)
                            <li
                                class="notification-item relative flex flex-col gap-2 px-5 py-3 transition {{ $notification->read_at ? 'is-read' : 'is-unread' }} {{ $notification->read_at ? 'cursor-default' : 'cursor-pointer' }}"
                                data-notification-id="{{ $notification->id }}"
                                data-is-read="{{ $notification->read_at ? 'true' : 'false' }}"
                                data-read-url="{{ route('notifications.read', $notification) }}"
                                data-delete-url="{{ route('notifications.destroy', $notification) }}"
                                role="button"
                                tabindex="0"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex flex-1 items-start gap-3">
                                        <span class="notification-dot mt-1 h-2.5 w-2.5 rounded-full bg-primary {{ $notification->read_at ? 'hidden' : '' }}"></span>
                                        <div class="flex-1 space-y-1">
                                            <p data-notification-title class="text-xs text-heading sm:text-sm {{ $notification->read_at ? 'font-medium' : 'font-semibold' }}">
                                                {{ $notification->title ?? gettext('Actualización MercuryApp') }}
                                            </p>
                                            <p data-notification-description class="notification-description text-xs sm:text-sm {{ $notification->read_at ? 'text-secondary' : 'text-heading font-medium' }}">
                                                {{ $notification->description }}
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="notification-delete text-[11px] text-secondary transition hover:text-rose-500 sm:text-xs"
                                        aria-label="{{ gettext('Eliminar notificación') }}"
                                    >
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                                <div class="flex items-center justify-between text-[11px] text-secondary sm:text-xs">
                                    <span class="inline-flex items-center gap-1">
                                        <i class="fa-regular fa-clock"></i>
                                        {{ optional($notification->created_at)->diffForHumans() ?? '' }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        @if(! $notification->read_at)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-primary-soft px-2 py-0.5 text-[11px] font-semibold text-primary">
                                                <i class="fa-solid fa-sparkles"></i>{{ gettext('Nuevo') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="notification-empty px-4 py-6 text-center text-xs text-secondary">
                                {{ gettext('No tienes notificaciones registradas.') }}
                            </li>
                        @endforelse
                    </ul>
                    <div class="border-t border-surface bg-surface-muted/40 px-4 py-2 text-center">
                        <a href="{{ route('notifications.index') }}" class="text-xs font-semibold text-primary hover:text-primary-strong">
                            {{ gettext('Ver todas las notificaciones') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="relative" data-dropdown="user-menu">
                <button
                    type="button"
                    class="inline-flex items-center gap-3 rounded-xl bg-surface-elevated px-3 py-2 text-left text-sm transition hover:border-primary"
                    data-dropdown-trigger
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    <img
                        src="{{ $avatar }}"
                        alt="{{ $user?->first_name }}"
                        class="h-9 w-9 rounded-full object-cover"
                        loading="lazy"
                    >
                    <span class="hidden text-left sm:block">
                        <span class="block text-sm font-semibold text-heading">{{ $user?->first_name }} {{ $user?->last_name }}</span>
                        <span class="block text-xs text-secondary">{{ $user?->role?->display_name ?? 'MercuryApp' }}</span>
                    </span>
                    <i class="fa-solid fa-chevron-down text-xs text-secondary"></i>
                </button>
                <div
                    data-dropdown-panel
                    class="absolute right-0 mt-2 hidden w-64 overflow-hidden rounded-md border border-surface bg-surface-elevated shadow-lg"
                    hidden
                >
                    <div class="flex items-center gap-3 px-4 py-3">
                        <img
                            src="{{ $avatar }}"
                            alt="{{ $user?->first_name }}"
                            class="h-10 w-10 rounded-full object-cover"
                            loading="lazy"
                        >
                        <div>
                            <p class="text-sm font-semibold text-heading">{{ $user?->first_name }} {{ $user?->last_name }}</p>
                            <p class="text-xs text-secondary">{{ $user?->email }}</p>
                        </div>
                    </div>
                    <div class="border-t border-surface"></div>
                    <ul class="divide-y divide-surface text-sm text-secondary">
                        <li>
                            <a href="{{ route('profile.show') }}" class="flex items-center px-4 py-3 transition hover:bg-surface-muted hover:text-heading">
                                <i class="fa-regular fa-user mr-2 text-primary"></i>{{ gettext('Mi perfil') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('configuration.company') }}" class="flex items-center px-4 py-3 transition hover:bg-surface-muted hover:text-heading">
                                <i class="fa-solid fa-building mr-2 text-primary"></i>{{ gettext('Perfil de empresa') }}
                            </a>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="flex w-full items-center px-4 py-3 text-left transition hover:bg-surface-muted hover:text-heading"
                                >
                                    <i class="fa-solid fa-arrow-right-from-bracket mr-2 text-primary"></i>{{ gettext('Cerrar sesión') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>

