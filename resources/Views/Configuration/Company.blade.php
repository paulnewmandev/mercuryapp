{{--
/**
 * @fileoverview Página de perfil corporativo para la empresa asociada al usuario autenticado.
 */
--}}

@php
    use Illuminate\Support\Str;

    $logoUrl = $company->logo_url ?: '/theme-images/logo/icon-256x256.png';
    $logoFileName = $company->logo_url ? Str::afterLast($company->logo_url, '/') : null;
    $certificateFileName = $company->digital_url ? Str::afterLast($company->digital_url, '/') : null;
    $isEditing = old('_editing') === '1' || $errors->isNotEmpty();
@endphp

<x-layouts.dashboard-layout :meta="$meta ?? []">
    <div data-company-profile data-initial-state="{{ $isEditing ? 'editing' : 'preview' }}">
        <x-ui.breadcrumb :title="gettext('Mi empresa')" :items="$breadcrumbItems" />

        @if ($errors->any() && ! $isEditing)
            <div class="mb-6 rounded-xl border border-red-500 bg-red-50 px-4 py-3 text-sm text-red-600">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section data-company-preview class="{{ $isEditing ? 'hidden' : '' }}">
            <div class="rounded-2xl border border-surface bg-surface-elevated p-6 shadow-sm">
                <div class="mb-6 flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex flex-1 flex-col items-center gap-6 xl:flex-row xl:items-center">
                        <div class="h-20 w-20 overflow-hidden rounded-full border border-surface">
                            <img src="{{ $logoUrl }}" alt="{{ gettext('Logo de la empresa') }}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                        <div class="text-center xl:text-left">
                            <h1 class="text-2xl font-semibold text-heading">{{ $company->name }}</h1>
                            <p class="mt-1 text-sm text-secondary">{{ $company->legal_name }}</p>
                        </div>
                        <div class="flex items-center gap-2 xl:ml-auto">
                            @if ($company->website)
                                <a
                                    href="{{ $company->website }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-surface text-secondary transition hover:border-primary hover:text-primary"
                                >
                                    <i class="fa-solid fa-globe"></i>
                                </a>
                            @endif
                            @if ($company->email)
                                <a
                                    href="mailto:{{ $company->email }}"
                                    class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-surface text-secondary transition hover:border-primary hover:text-primary"
                                >
                                    <i class="fa-regular fa-envelope"></i>
                                </a>
                            @endif
                            @if ($company->phone_number)
                                <a
                                    href="tel:{{ $company->phone_number }}"
                                    class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-surface text-secondary transition hover:border-primary hover:text-primary"
                                >
                                    <i class="fa-solid fa-phone"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                    <button
                        type="button"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-surface px-4 py-3 text-sm font-semibold text-secondary transition hover:border-primary hover:text-primary xl:w-auto"
                        data-company-edit-toggle
                    >
                        {{ gettext('Editar') }}
                    </button>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="space-y-4">
                        <h4 class="text-lg font-semibold text-heading">{{ gettext('Información general') }}</h4>
                        <dl class="grid gap-4">
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Nombre comercial') }}</dt>
                                <dd class="text-sm font-medium text-heading">{{ $company->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Razón social') }}</dt>
                                <dd class="text-sm font-medium text-heading">{{ $company->legal_name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Correo electrónico') }}</dt>
                                <dd class="text-sm font-medium text-heading">{{ $company->email ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Teléfono de contacto') }}</dt>
                                <dd class="text-sm font-medium text-heading">{{ $company->phone_number ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="space-y-4">
                        <h4 class="text-lg font-semibold text-heading">{{ gettext('Datos fiscales y dirección') }}</h4>
                        <dl class="grid gap-4">
                            <div>
                                <dt class="text-xsuppercase tracking-wide text-secondary">{{ gettext('Tipo de régimen') }}</dt>
                                <dd class="text-sm font-medium text-heading">{{ $company->type_tax ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Número fiscal') }}</dt>
                                <dd class="text-sm font-medium text-heading">{{ $company->number_tax ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Sitio web') }}</dt>
                                <dd class="text-sm font-medium text-heading">{{ $company->website ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Dirección') }}</dt>
                                <dd class="text-sm font-medium text-heading">{{ $company->address ?? '—' }}</dd>
                            </div>
                            @if ($company->digital_url)
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Certificado digital') }}</dt>
                                    <dd class="text-sm font-medium text-heading">{{ gettext('Configurado') }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Ambiente SRI') }}</dt>
                                <dd class="text-sm font-medium text-heading">
                                    {{ ($company->sri_environment ?? 'development') === 'production' ? gettext('Producción') : gettext('Desarrollo') }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </section>

        <section data-company-edit class="{{ $isEditing ? '' : 'hidden' }}">
            <form
                method="POST"
                action="{{ route('configuration.company.update') }}"
                enctype="multipart/form-data"
                class="rounded-2xl border border-surface bg-surface-elevated shadow-sm"
            >
                @csrf
                @method('PATCH')
                <input type="hidden" name="_editing" value="1">

                <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-surface">
                    <h3 class="text-base font-medium text-heading">{{ gettext('Detalles') }}</h3>
                </div>

                <div class="space-y-6 px-5 py-6 sm:px-6">
                    <div class="grid gap-3 md:grid-cols-2">
                            <label class="block text-sm">
                                <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Nombre comercial') }}</span>
                                <input
                                    type="text"
                                    name="name"
                                    value="{{ old('name', $company->name) }}"
                                    class="w-full rounded-xl border {{ $errors->has('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 text-sm outline-none transition focus:ring-2"
                                >
                            @error('name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            </label>
                            <label class="block text-sm">
                                <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Razón social') }}</span>
                                <input
                                    type="text"
                                    name="legal_name"
                                    value="{{ old('legal_name', $company->legal_name) }}"
                                    class="w-full rounded-xl border {{ $errors->has('legal_name') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 text-sm outline-none transition focus:ring-2"
                                >
                            @error('legal_name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            </label>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                            <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Tipo de régimen') }}</span>
                            @php
                                $selectedTypeTax = old('type_tax', $company->type_tax);
                            @endphp
                            <div
                                class="relative"
                                data-select
                                data-select-name="type_tax"
                                data-select-invalid="{{ $errors->has('type_tax') ? 'true' : 'false' }}"
                            >
                                <input type="hidden" name="type_tax" value="{{ $selectedTypeTax }}">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-xl border {{ $errors->has('type_tax') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} bg-surface px-4 py-3 text-sm font-medium text-heading outline-none transition focus:ring-2"
                                    data-select-trigger
                                    data-select-placeholder="{{ gettext('Selecciona un tipo de régimen') }}"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                                >
                                    <span data-select-value class="truncate">{{ $selectedTypeTax ?: gettext('Selecciona un tipo de régimen') }}</span>
                                    <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                </button>
                                <div
                                    class="invisible absolute left-0 right-0 z-40 mt-2 w-full origin-top scale-95 transform rounded-xl border border-surface bg-surface-elevated shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100"
                                    data-select-dropdown
                                    role="listbox"
                                    hidden
                                >
                                    <div class="max-h-60 overflow-y-auto py-2">
                                        @foreach ($taxRegimeTypes as $type)
                                            <button
                                                type="button"
                                                class="group flex w-full items-center justify-between px-4 py-2 text-left text-sm text-heading transition hover:bg-primary-soft/20 data-[selected=true]:bg-primary-soft/30 data-[selected=true]:text-primary"
                                                data-select-option
                                                data-value="{{ $type }}"
                                                data-label="{{ $type }}"
                                                role="option"
                                            >
                                                <span class="truncate">{{ $type }}</span>
                                                <i class="fa-solid fa-circle-check text-primary opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                            </button>
                                        @endforeach
                                    </div>
                        </div>
                    </div>
                            @error('type_tax')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            </label>
                            <label class="block text-sm">
                                <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Número fiscal') }}</span>
                                <input
                                    type="text"
                                    name="number_tax"
                                    value="{{ old('number_tax', $company->number_tax) }}"
                                    class="w-full rounded-xl border {{ $errors->has('number_tax') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 text-sm outline-none transition focus:ring-2"
                                >
                            @error('number_tax')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            </label>
                    </div>

                    <div class="grid gap-3 md:grid-cols-3">
                            <label class="block text-sm">
                                <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Sitio web') }}</span>
                                <input
                                    type="text"
                                    name="website"
                                    value="{{ old('website', $company->website) }}"
                                    class="w-full rounded-xl border {{ $errors->has('website') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 text-sm outline-none transition focus:ring-2"
                                >
                            @error('website')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Correo electrónico') }}</span>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email', $company->email) }}"
                                class="w-full rounded-xl border {{ $errors->has('email') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 text-sm outline-none transition focus:ring-2"
                            >
                            @error('email')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            </label>
                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Teléfono de contacto') }}</span>
                            <input
                                type="text"
                                name="phone_number"
                                value="{{ old('phone_number', $company->phone_number) }}"
                                class="w-full rounded-xl border {{ $errors->has('phone_number') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 text-sm outline-none transition focus:ring-2"
                            >
                            @error('phone_number')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </label>
                    </div>

                            <label class="block text-sm">
                                <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Dirección') }}</span>
                                <textarea
                                    name="address"
                                    rows="3"
                                    class="w-full rounded-xl border {{ $errors->has('address') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 text-sm outline-none transition focus:ring-2"
                                >{{ old('address', $company->address) }}</textarea>
                        @error('address')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                            </label>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <span class="block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Logo') }}</span>
                            <div
                                class="group relative flex cursor-pointer flex-col items-center justify-center gap-4 rounded-2xl border border-dashed border-surface bg-surface px-6 py-8 text-center transition hover:border-primary hover:bg-primary-soft/10 focus-within:border-primary"
                                data-dropzone
                                data-accept=".jpg,.jpeg,.png,.bmp,.gif,image/jpeg,image/png,image/bmp,image/gif"
                                data-max-size="10"
                                role="button"
                                tabindex="0"
                                aria-label="{{ gettext('Seleccionar logo de la empresa') }}"
                            >
                                <input type="file" name="logo" id="logo" accept=".jpg,.jpeg,.png,.bmp,.gif,image/jpeg,image/png,image/bmp,image/gif" class="hidden">
                                <div data-dropzone-icon class="flex h-16 w-16 items-center justify-center rounded-full bg-primary-soft text-primary {{ $company->logo_url ? 'hidden' : '' }}">
                                    <i class="fa-regular fa-image text-2xl"></i>
                                </div>
                                <img
                                    src="{{ $logoUrl }}"
                                    alt="{{ gettext('Logo de la empresa') }}"
                                    class="h-16 w-16 rounded-full object-cover {{ $company->logo_url ? '' : 'hidden' }}"
                                    data-dropzone-preview
                                    data-dropzone-initial="{{ $logoUrl }}"
                                >
                                <div class="space-y-3 text-xs text-secondary">
                                    <p class="leading-relaxed" data-dropzone-placeholder>{{ gettext('Arrastra y suelta archivos JPG, JPEG, PNG, BMP o GIF (máx. 10 MB) o usa el botón para seleccionar.') }}</p>
                                    <div class="flex flex-wrap items-center justify-center gap-2">
                                        <span data-dropzone-filename data-default="{{ $logoFileName ?? '' }}" class="font-medium text-secondary">{{ $logoFileName ?? gettext('Ningún archivo seleccionado') }}</span>
                                    </div>
                                    <span data-dropzone-feedback class="hidden text-xs text-red-500"></span>
                                </div>
                            </div>
                            @error('logo')
                                <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <span class="block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Firma digital (.p12)') }}</span>
                            <div
                                class="group relative flex cursor-pointer flex-col items-center justify-center gap-4 rounded-2xl border border-dashed border-surface bg-surface px-6 py-8 text-center transition hover:border-primary hover:bg-primary-soft/10 focus-within:border-primary"
                                data-dropzone
                                data-accept=".p12"
                                data-max-size="10"
                                role="button"
                                tabindex="0"
                                aria-label="{{ gettext('Seleccionar firma digital en formato .p12') }}"
                            >
                                <input type="file" name="digital_certificate" id="digital_certificate" accept=".p12" class="hidden">
                                <div data-dropzone-icon class="flex h-16 w-16 items-center justify-center rounded-full bg-primary-soft text-primary">
                                    <i class="fa-solid fa-file-signature text-2xl"></i>
                                </div>
                                <div class="space-y-3 text-xs text-secondary">
                                    <p class="leading-relaxed" data-dropzone-placeholder>{{ gettext('Arrastra y suelta tu archivo .p12 (máx. 10 MB) o usa el botón para seleccionar.') }}</p>
                                    <div class="flex flex-wrap items-center justify-center gap-2">
                                        <span data-dropzone-filename data-default="{{ $certificateFileName ?? '' }}" class="font-medium text-secondary">{{ $certificateFileName ?? gettext('Ningún archivo seleccionado') }}</span>
                                    </div>
                                    <span data-dropzone-feedback class="hidden text-xs text-red-500"></span>
                                </div>
                            </div>
                            @error('digital_certificate')
                                <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">
                                {{ gettext('Contraseña del certificado .p12') }}
                            </span>
                            <input
                                type="password"
                                name="digital_signature_password"
                                value=""
                                placeholder="{{ gettext('Ingresa la contraseña del certificado') }}"
                                class="w-full rounded-xl border {{ $errors->has('digital_signature_password') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 text-sm outline-none transition focus:ring-2"
                            >
                            <p class="mt-1 text-xs text-secondary">
                                {{ gettext('Deja vacío si no deseas cambiar la contraseña actual') }}
                            </p>
                            @error('digital_signature_password')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">
                                {{ gettext('Ambiente SRI') }}
                            </span>
                            @php
                                $selectedSriEnvironment = old('sri_environment', $company->sri_environment ?? 'development');
                                $sriEnvironments = [
                                    'development' => gettext('Desarrollo (Pruebas)'),
                                    'production' => gettext('Producción'),
                                ];
                            @endphp
                            <div
                                class="relative"
                                data-select
                                data-select-name="sri_environment"
                                data-select-invalid="{{ $errors->has('sri_environment') ? 'true' : 'false' }}"
                            >
                                <input type="hidden" name="sri_environment" value="{{ $selectedSriEnvironment }}">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-xl border {{ $errors->has('sri_environment') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} bg-surface px-4 py-3 text-sm font-medium text-heading outline-none transition focus:ring-2"
                                    data-select-trigger
                                    data-select-placeholder="{{ gettext('Selecciona un ambiente') }}"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                                >
                                    <span data-select-value class="truncate">{{ $sriEnvironments[$selectedSriEnvironment] ?? gettext('Selecciona un ambiente') }}</span>
                                    <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                </button>
                                <div
                                    class="invisible absolute left-0 right-0 z-40 mt-2 w-full origin-top scale-95 transform rounded-xl border border-surface bg-surface-elevated shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100"
                                    data-select-dropdown
                                    role="listbox"
                                    hidden
                                >
                                    <div class="max-h-60 overflow-y-auto py-2">
                                        @foreach ($sriEnvironments as $value => $label)
                                            <button
                                                type="button"
                                                class="group flex w-full items-center justify-between px-4 py-2 text-left text-sm text-heading transition hover:bg-primary-soft/20 data-[selected=true]:bg-primary-soft/30 data-[selected=true]:text-primary"
                                                data-select-option
                                                data-value="{{ $value }}"
                                                data-label="{{ $label }}"
                                                role="option"
                                                {{ $selectedSriEnvironment === $value ? 'data-selected=true' : '' }}
                                            >
                                                <span class="truncate">{{ $label }}</span>
                                                <i class="fa-solid fa-circle-check text-primary opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-secondary">
                                {{ gettext('Selecciona el ambiente del SRI según tu configuración') }}
                            </p>
                            @error('sri_environment')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </label>
                    </div>

                    <input type="hidden" name="theme_color" value="{{ old('theme_color', $company->theme_color ?? '#001A35') }}">
                </div>

                <div class="border-t border-surface px-5 py-4 sm:px-6 sm:py-5 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        class="inline-flex min-w-[140px] justify-center rounded-xl border border-surface px-6 py-2 text-sm font-semibold text-secondary transition hover:border-primary hover:text-primary"
                        data-company-edit-cancel
                    >
                        {{ gettext('Cancelar') }}
                    </button>
                    <button
                        type="submit"
                        class="inline-flex min-w-[140px] justify-center rounded-xl bg-primary px-6 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong"
                        data-company-submit
                        data-saving-text="{{ gettext('Guardando...') }}"
                    >
                        {{ gettext('Guardar') }}
                    </button>
                </div>
            </form>
        </section>
    </div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const profileForm = document.querySelector('[data-company-profile] form');
            if (profileForm) {
                profileForm.addEventListener('submit', () => {
                    const submitButton = profileForm.querySelector('[data-company-submit]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.classList.add('opacity-70', 'cursor-not-allowed');
                        if (submitButton.dataset.savingText) {
                            submitButton.dataset.originalText = submitButton.textContent.trim();
                            submitButton.textContent = submitButton.dataset.savingText;
                        }
                    }

                    profileForm.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach((element) => {
                        if (element instanceof HTMLInputElement) {
                            if (element.type === 'file') {
                                const zone = element.closest('[data-dropzone]');
                                if (zone) {
                                    zone.classList.add('pointer-events-none', 'opacity-70');
                                }
                            } else {
                                element.readOnly = true;
                            }
                        } else if (element instanceof HTMLTextAreaElement) {
                            element.readOnly = true;
                        } else if (element instanceof HTMLSelectElement) {
                            element.classList.add('pointer-events-none', 'opacity-70');
                            element.setAttribute('aria-disabled', 'true');
                            element.tabIndex = -1;
                        }
                    });

                    profileForm.querySelectorAll('[data-select]').forEach((select) => {
                        select.classList.add('pointer-events-none', 'opacity-70');
                        const trigger = select.querySelector('[data-select-trigger]');
                        if (trigger) {
                            trigger.setAttribute('aria-disabled', 'true');
                            trigger.tabIndex = -1;
                        }
                    });

                    profileForm.querySelectorAll('button').forEach((button) => {
                        if (button !== submitButton) {
                            button.disabled = true;
                            button.classList.add('opacity-70', 'cursor-not-allowed');
                        }
                    });
                }, { once: true });
            }

            @if(session('status'))
                Swal.fire({
                    title: @json(gettext('Datos actualizados')),
                    text: @json(session('status')),
                    icon: 'success',
                    confirmButtonText: @json(gettext('Aceptar')),
                    confirmButtonColor: '#001A35',
                });
            @endif
        });
    </script>
@endpush

</x-layouts.dashboard-layout>
