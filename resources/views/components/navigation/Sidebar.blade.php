{{--
/**
 * @fileoverview Sidebar principal reutilizable para la sección de dashboard.
 */
--}}

@php
    use Illuminate\Support\Str;

    $sections = config('navigation.sidebar', []);
@endphp

<aside
    data-sidebar
    data-sidebar-state="expanded"
    class="fixed inset-y-0 left-0 z-40 w-72 transform bg-[#0B1628] text-slate-100 transition-transform duration-300 ease-in-out lg:relative"
>
    <div class="flex h-full flex-col">
        <div class="flex items-center justify-between px-6 py-6">
            <div class="flex items-center gap-3">
                <img src="/theme-images/logo/icon-96x96.png" alt="MercuryApp" class="h-9 w-9 rounded-lg object-cover" loading="lazy">
                <span class="sidebar-brand-text text-lg font-semibold tracking-tight text-white">MercuryApp</span>
            </div>
            <button
                type="button"
                class="inline-flex rounded-lg border border-white/10 p-2 text-slate-200 transition hover:border-white/40 hover:text-white lg:hidden"
                data-sidebar-close
                aria-label="{{ gettext('Cerrar menú lateral') }}"
            >
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <nav class="flex-1 space-y-6 overflow-y-auto px-4 pb-6">
            @foreach ($sections as $section)
                @php
                    $hasChildren = !empty($section['children']);
                    $sectionIsActive = $hasChildren
                        ? collect($section['children'])->contains(fn ($child) => request()->routeIs($child['route'] ?? ''))
                        : request()->routeIs($section['route'] ?? '');
                    $sectionId = Str::slug($section['label']);
                @endphp

                <div class="space-y-2" data-sidebar-section>
                    @if ($hasChildren)
                        <button
                            type="button"
                            class="sidebar-section-header group flex w-full items-center gap-3 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide transition {{ $sectionIsActive ? 'text-white' : 'text-slate-400 hover:text-white' }}"
                            data-sidebar-collapse-trigger
                            data-target="sidebar-group-{{ $sectionId }}"
                            aria-expanded="{{ $sectionIsActive ? 'true' : 'false' }}"
                        >
                            <span @class([
                                'inline-flex h-8 w-8 items-center justify-center transition-colors',
                                'text-white' => $sectionIsActive,
                                'text-slate-300 group-hover:text-white' => ! $sectionIsActive,
                            ])>
                                <i class="text-lg {{ $section['icon'] ?? 'fa-solid fa-circle' }}"></i>
                            </span>
                            <span class="sidebar-section-title flex-1">{{ gettext($section['label']) }}</span>
                            <i class="fa-solid fa-chevron-down text-xs transition" data-sidebar-collapse-icon style="transform: rotate({{ $sectionIsActive ? '0' : '-90' }}deg);"></i>
                        </button>
                        <div
                            id="sidebar-group-{{ $sectionId }}"
                            class="sidebar-collapse-group space-y-1 pl-6 {{ $sectionIsActive ? '' : 'hidden' }}"
                            data-sidebar-collapse-panel
                        >
                            @foreach ($section['children'] as $child)
                                @php
                                    $isActive = request()->routeIs($child['route']);
                                    $childIcon = $child['icon'] ?? null;
                                @endphp
                                @php($childIconClass = $childIcon ? 'text-base ' . $childIcon : 'fa-solid fa-circle text-[6px]')
                                <a
                                    href="{{ \Illuminate\Support\Facades\Route::has($child['route']) ? route($child['route']) : '#' }}"
                                    class="sidebar-nav-item-child group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition {{ $isActive ? 'bg-primary text-white shadow-lg' : 'text-slate-300 hover:bg-[#132640] hover:text-white' }}"
                                >
                                    <span @class([
                                        'sidebar-nav-item-icon transition-colors',
                                        'text-white' => $isActive,
                                        'text-slate-300 group-hover:text-primary' => ! $isActive,
                                    ])>
                                        <i class="{{ $childIconClass }}"></i>
                                    </span>
                                    <span class="sidebar-nav-item-text">{{ gettext($child['label']) }}</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <a
                            href="{{ isset($section['route']) ? route($section['route']) : '#' }}"
                            class="sidebar-nav-item group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition {{ $sectionIsActive ? 'bg-primary text-white shadow-lg' : 'text-slate-300 hover:bg-[#132640] hover:text-white' }}"
                        >
                            <span @class([
                                'sidebar-nav-item-icon transition-colors',
                                'text-white' => $sectionIsActive,
                                'text-slate-300 group-hover:text-primary' => ! $sectionIsActive,
                            ])>
                                <i class="text-lg {{ $section['icon'] ?? 'fa-solid fa-circle' }}"></i>
                            </span>
                            <span class="sidebar-nav-item-text">{{ gettext($section['label']) }}</span>
                        </a>
                    @endif
                </div>
            @endforeach
        </nav>
    </div>
</aside>

