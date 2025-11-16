{{--
/**
 * @fileoverview Página de perfil personal para el usuario autenticado.
 */
--}}

@php
    $isEditing = old('_editing') === '1' || $errors->isNotEmpty();
    $isEditingPassword = old('_editing_password') === '1' || $errors->has('new_password');
    $hasAvatar = $user->avatar_url && file_exists(public_path(ltrim($user->avatar_url, '/')));
    $avatarUrl = $hasAvatar ? $user->avatar_url : null;
@endphp

<x-layouts.dashboard-layout :meta="$meta ?? []">
    <div data-user-profile>
        <x-ui.breadcrumb :title="gettext('Mi perfil')" :items="$breadcrumbItems" />

        @if ($errors->any() && ! $isEditing && ! $isEditingPassword)
            <div class="mb-6 rounded-xl border border-red-500 bg-red-50 px-4 py-3 text-sm text-red-600">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Vista previa --}}
        <section data-user-preview class="{{ $isEditing || $isEditingPassword ? 'hidden' : '' }}">
            <div class="rounded-2xl border border-surface bg-surface-elevated p-6 shadow-sm">
                <div class="mb-6 flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex flex-1 flex-col items-center gap-6 xl:flex-row xl:items-center">
                        <div class="h-20 w-20 overflow-hidden rounded-full border border-surface flex items-center justify-center bg-primary-soft">
                            @if($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="{{ gettext('Avatar del usuario') }}" class="h-full w-full object-cover" loading="lazy">
                            @else
                                <i class="fa-regular fa-user text-2xl text-primary"></i>
                            @endif
                        </div>
                        <div class="text-center xl:text-left">
                            <h1 class="text-2xl font-semibold text-heading">{{ $user->display_name }}</h1>
                            <p class="mt-1 text-sm text-secondary">{{ $user->email }}</p>
                            @if($user->role)
                                <p class="mt-1 text-xs text-secondary">{{ $user->role->display_name }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-surface px-4 py-3 text-sm font-semibold text-secondary transition hover:border-primary hover:text-primary xl:w-auto"
                            data-user-edit-toggle
                        >
                            {{ gettext('Editar perfil') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-surface px-4 py-3 text-sm font-semibold text-secondary transition hover:border-primary hover:text-primary xl:w-auto"
                            data-user-password-toggle
                        >
                            {{ gettext('Cambiar contraseña') }}
                        </button>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="space-y-4">
                        <h4 class="text-lg font-semibold text-heading">{{ gettext('Información personal') }}</h4>
                        <dl class="grid gap-4">
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Nombre') }}</dt>
                                <dd class="text-sm font-medium text-heading">{{ $user->first_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Apellido') }}</dt>
                                <dd class="text-sm font-medium text-heading">{{ $user->last_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Correo electrónico') }}</dt>
                                <dd class="text-sm font-medium text-heading">{{ $user->email }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Número de documento') }}</dt>
                                <dd class="text-sm font-medium text-heading">{{ $user->document_number ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Teléfono') }}</dt>
                                <dd class="text-sm font-medium text-heading">{{ $user->phone_number ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="space-y-4">
                        <h4 class="text-lg font-semibold text-heading">{{ gettext('Información adicional') }}</h4>
                        <dl class="grid gap-4">
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Fecha de nacimiento') }}</dt>
                                <dd class="text-sm font-medium text-heading">{{ $user->date_of_birth ? $user->date_of_birth->format('d/m/Y') : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Género') }}</dt>
                                <dd class="text-sm font-medium text-heading">
                                    @if($user->gender)
                                        @if($user->gender === 'M')
                                            {{ gettext('Masculino') }}
                                        @elseif($user->gender === 'F')
                                            {{ gettext('Femenino') }}
                                        @else
                                            {{ gettext('Otro') }}
                                        @endif
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Estado') }}</dt>
                                <dd class="text-sm font-medium text-heading">{{ $user->status_label }}</dd>
                            </div>
                            @if($user->company)
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-secondary">{{ gettext('Empresa') }}</dt>
                                    <dd class="text-sm font-medium text-heading">{{ $user->company->name }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>
        </section>

        {{-- Formulario de edición --}}
        <section data-user-edit class="{{ $isEditing ? '' : 'hidden' }}">
            <form
                method="POST"
                action="{{ route('profile.update') }}"
                enctype="multipart/form-data"
                class="rounded-2xl border border-surface bg-surface-elevated shadow-sm"
            >
                @csrf
                @method('PATCH')
                <input type="hidden" name="_editing" value="1">

                <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-surface">
                    <h3 class="text-base font-medium text-heading">{{ gettext('Datos personales') }}</h3>
                </div>

                <div class="space-y-6 px-5 py-6 sm:px-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <span class="block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Avatar') }}</span>
                            <div
                                class="group relative flex cursor-pointer flex-col items-center justify-center gap-4 rounded-2xl border border-dashed border-surface bg-surface px-6 py-8 text-center transition hover:border-primary hover:bg-primary-soft/10 focus-within:border-primary"
                                data-dropzone
                                data-accept=".jpg,.jpeg,.png,.bmp,.gif,image/jpeg,image/png,image/bmp,image/gif"
                                data-max-size="5"
                                role="button"
                                tabindex="0"
                                aria-label="{{ gettext('Seleccionar avatar') }}"
                            >
                                <input type="file" name="avatar" id="avatar" accept=".jpg,.jpeg,.png,.bmp,.gif,image/jpeg,image/png,image/bmp,image/gif" class="hidden">
                                <div data-dropzone-icon class="flex h-16 w-16 items-center justify-center rounded-full bg-primary-soft text-primary {{ $avatarUrl ? 'hidden' : '' }}">
                                    <i class="fa-regular fa-user text-2xl"></i>
                                </div>
                                @if($avatarUrl)
                                    <img
                                        src="{{ $avatarUrl }}"
                                        alt="{{ gettext('Avatar del usuario') }}"
                                        class="h-16 w-16 rounded-full object-cover"
                                        data-dropzone-preview
                                        data-dropzone-initial="{{ $avatarUrl }}"
                                    >
                                @else
                                    <img
                                        src=""
                                        alt="{{ gettext('Avatar del usuario') }}"
                                        class="h-16 w-16 rounded-full object-cover hidden"
                                        data-dropzone-preview
                                        data-dropzone-initial=""
                                    >
                                @endif
                                <div class="space-y-3 text-xs text-secondary">
                                    <p class="leading-relaxed" data-dropzone-placeholder>{{ gettext('Arrastra y suelta archivos JPG, JPEG, PNG, BMP o GIF (máx. 5 MB) o usa el botón para seleccionar.') }}</p>
                                    <div class="flex flex-wrap items-center justify-center gap-2">
                                        <span data-dropzone-filename class="font-medium text-secondary">{{ gettext('Ningún archivo seleccionado') }}</span>
                                    </div>
                                    <span data-dropzone-feedback class="hidden text-xs text-red-500"></span>
                                </div>
                            </div>
                            @error('avatar')
                                <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Nombre') }}</span>
                            <input
                                type="text"
                                name="first_name"
                                value="{{ old('first_name', $user->first_name) }}"
                                required
                                class="w-full rounded-xl border {{ $errors->has('first_name') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 text-sm outline-none transition focus:ring-2"
                            >
                            @error('first_name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Apellido') }}</span>
                            <input
                                type="text"
                                name="last_name"
                                value="{{ old('last_name', $user->last_name) }}"
                                required
                                class="w-full rounded-xl border {{ $errors->has('last_name') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 text-sm outline-none transition focus:ring-2"
                            >
                            @error('last_name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </label>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Correo electrónico') }}</span>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email', $user->email) }}"
                                required
                                class="w-full rounded-xl border {{ $errors->has('email') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 text-sm outline-none transition focus:ring-2"
                            >
                            @error('email')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Teléfono') }}</span>
                            <input
                                type="text"
                                name="phone_number"
                                value="{{ old('phone_number', $user->phone_number) }}"
                                class="w-full rounded-xl border {{ $errors->has('phone_number') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 text-sm outline-none transition focus:ring-2"
                            >
                            @error('phone_number')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </label>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Número de documento') }}</span>
                            <input
                                type="text"
                                name="document_number"
                                value="{{ old('document_number', $user->document_number) }}"
                                class="w-full rounded-xl border {{ $errors->has('document_number') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 text-sm outline-none transition focus:ring-2"
                            >
                            @error('document_number')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Fecha de nacimiento') }}</span>
                            <input
                                type="date"
                                name="date_of_birth"
                                value="{{ old('date_of_birth', $user->date_of_birth ? $user->date_of_birth->format('Y-m-d') : '') }}"
                                class="w-full rounded-xl border {{ $errors->has('date_of_birth') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 text-sm outline-none transition focus:ring-2"
                            >
                            @error('date_of_birth')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </label>
                    </div>

                    <label class="block text-sm">
                        <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Género') }}</span>
                        @php
                            $selectedGender = old('gender', $user->gender);
                            $genders = [
                                'M' => gettext('Masculino'),
                                'F' => gettext('Femenino'),
                                'O' => gettext('Otro'),
                            ];
                        @endphp
                        <div
                            class="relative"
                            data-select
                            data-select-name="gender"
                            data-select-invalid="{{ $errors->has('gender') ? 'true' : 'false' }}"
                        >
                            <input type="hidden" name="gender" value="{{ $selectedGender }}">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border {{ $errors->has('gender') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} bg-surface px-4 py-3 text-sm font-medium text-heading outline-none transition focus:ring-2"
                                data-select-trigger
                                data-select-placeholder="{{ gettext('Selecciona un género') }}"
                                aria-haspopup="listbox"
                                aria-expanded="false"
                            >
                                <span data-select-value class="truncate">{{ $selectedGender ? $genders[$selectedGender] ?? gettext('Selecciona un género') : gettext('Selecciona un género') }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                            </button>
                            <div
                                class="invisible absolute left-0 right-0 z-40 mt-2 w-full origin-top scale-95 transform rounded-xl border border-surface bg-surface-elevated shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100"
                                data-select-dropdown
                                role="listbox"
                                hidden
                            >
                                <div class="max-h-60 overflow-y-auto py-2">
                                    <button
                                        type="button"
                                        class="group flex w-full items-center justify-between px-4 py-2 text-left text-sm text-heading transition hover:bg-primary-soft/20 data-[selected=true]:bg-primary-soft/30 data-[selected=true]:text-primary"
                                        data-select-option
                                        data-value=""
                                        data-label="{{ gettext('No especificado') }}"
                                        role="option"
                                        {{ !$selectedGender ? 'data-selected=true' : '' }}
                                    >
                                        <span class="truncate">{{ gettext('No especificado') }}</span>
                                        <i class="fa-solid fa-circle-check text-primary opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                    </button>
                                    @foreach ($genders as $value => $label)
                                        <button
                                            type="button"
                                            class="group flex w-full items-center justify-between px-4 py-2 text-left text-sm text-heading transition hover:bg-primary-soft/20 data-[selected=true]:bg-primary-soft/30 data-[selected=true]:text-primary"
                                            data-select-option
                                            data-value="{{ $value }}"
                                            data-label="{{ $label }}"
                                            role="option"
                                            {{ $selectedGender === $value ? 'data-selected=true' : '' }}
                                        >
                                            <span class="truncate">{{ $label }}</span>
                                            <i class="fa-solid fa-circle-check text-primary opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @error('gender')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </label>
                </div>

                <div class="border-t border-surface px-5 py-4 sm:px-6 sm:py-5 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        class="inline-flex min-w-[140px] justify-center rounded-xl border border-surface px-6 py-2 text-sm font-semibold text-secondary transition hover:border-primary hover:text-primary"
                        data-user-edit-cancel
                    >
                        {{ gettext('Cancelar') }}
                    </button>
                    <button
                        type="submit"
                        class="inline-flex min-w-[140px] justify-center rounded-xl bg-primary px-6 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong"
                        data-user-submit
                        data-saving-text="{{ gettext('Guardando...') }}"
                    >
                        {{ gettext('Guardar') }}
                    </button>
                </div>
            </form>
        </section>

        {{-- Formulario de cambio de contraseña --}}
        <section data-user-password class="{{ $isEditingPassword ? '' : 'hidden' }}">
            <form
                method="POST"
                action="{{ route('profile.update-password') }}"
                class="rounded-2xl border border-surface bg-surface-elevated shadow-sm"
            >
                @csrf
                @method('PATCH')
                <input type="hidden" name="_editing_password" value="1">

                <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-surface">
                    <h3 class="text-base font-medium text-heading">{{ gettext('Cambiar contraseña') }}</h3>
                </div>

                <div class="space-y-6 px-5 py-6 sm:px-6">
                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Nueva contraseña') }}</span>
                            <div class="relative">
                                <input
                                    type="password"
                                    name="new_password"
                                    id="new_password"
                                    required
                                    minlength="8"
                                    class="w-full rounded-xl border {{ $errors->has('new_password') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 pr-12 text-sm outline-none transition focus:ring-2"
                                >
                                <button
                                    type="button"
                                    data-password-toggle="new_password"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-secondary transition hover:text-primary"
                                    aria-label="{{ gettext('Mostrar contraseña') }}"
                                >
                                    <i class="fa-regular fa-eye" data-password-icon></i>
                                </button>
                            </div>
                            @error('new_password')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-secondary">{{ gettext('Mínimo 8 caracteres') }}</p>
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-secondary">{{ gettext('Confirmar nueva contraseña') }}</span>
                            <div class="relative">
                                <input
                                    type="password"
                                    name="new_password_confirmation"
                                    id="new_password_confirmation"
                                    required
                                    minlength="8"
                                    class="w-full rounded-xl border {{ $errors->has('new_password_confirmation') ? 'border-red-500 focus:border-red-500 focus:ring-red-400' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 pr-12 text-sm outline-none transition focus:ring-2"
                                >
                                <button
                                    type="button"
                                    data-password-toggle="new_password_confirmation"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-secondary transition hover:text-primary"
                                    aria-label="{{ gettext('Mostrar contraseña') }}"
                                >
                                    <i class="fa-regular fa-eye" data-password-icon></i>
                                </button>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="border-t border-surface px-5 py-4 sm:px-6 sm:py-5 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        class="inline-flex min-w-[140px] justify-center rounded-xl border border-surface px-6 py-2 text-sm font-semibold text-secondary transition hover:border-primary hover:text-primary"
                        data-user-password-cancel
                    >
                        {{ gettext('Cancelar') }}
                    </button>
                    <button
                        type="submit"
                        class="inline-flex min-w-[140px] justify-center rounded-xl bg-primary px-6 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong"
                        data-user-password-submit
                        data-saving-text="{{ gettext('Guardando...') }}"
                    >
                        {{ gettext('Actualizar contraseña') }}
                    </button>
                </div>
            </form>
        </section>
    </div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const profileContainer = document.querySelector('[data-user-profile]');
            if (!profileContainer) return;

            // Toggle edición de perfil
            const editToggle = profileContainer.querySelector('[data-user-edit-toggle]');
            const editCancel = profileContainer.querySelector('[data-user-edit-cancel]');
            const previewSection = profileContainer.querySelector('[data-user-preview]');
            const editSection = profileContainer.querySelector('[data-user-edit]');

            if (editToggle) {
                editToggle.addEventListener('click', () => {
                    previewSection?.classList.add('hidden');
                    editSection?.classList.remove('hidden');
                });
            }

            if (editCancel) {
                editCancel.addEventListener('click', () => {
                    editSection?.classList.add('hidden');
                    previewSection?.classList.remove('hidden');
                });
            }

            // Toggle cambio de contraseña
            const passwordToggle = profileContainer.querySelector('[data-user-password-toggle]');
            const passwordCancel = profileContainer.querySelector('[data-user-password-cancel]');
            const passwordSection = profileContainer.querySelector('[data-user-password]');

            if (passwordToggle) {
                passwordToggle.addEventListener('click', () => {
                    previewSection?.classList.add('hidden');
                    passwordSection?.classList.remove('hidden');
                });
            }

            if (passwordCancel) {
                passwordCancel.addEventListener('click', () => {
                    passwordSection?.classList.add('hidden');
                    previewSection?.classList.remove('hidden');
                });
            }

            // Manejo de formularios
            const profileForm = profileContainer.querySelector('[data-user-edit] form');
            if (profileForm) {
                profileForm.addEventListener('submit', () => {
                    const submitButton = profileForm.querySelector('[data-user-submit]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.classList.add('opacity-70', 'cursor-not-allowed');
                        if (submitButton.dataset.savingText) {
                            submitButton.dataset.originalText = submitButton.textContent.trim();
                            submitButton.textContent = submitButton.dataset.savingText;
                        }
                    }
                }, { once: true });
            }

            const passwordForm = profileContainer.querySelector('[data-user-password] form');
            if (passwordForm) {
                passwordForm.addEventListener('submit', () => {
                    const submitButton = passwordForm.querySelector('[data-user-password-submit]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.classList.add('opacity-70', 'cursor-not-allowed');
                        if (submitButton.dataset.savingText) {
                            submitButton.dataset.originalText = submitButton.textContent.trim();
                            submitButton.textContent = submitButton.dataset.savingText;
                        }
                    }
                }, { once: true });
            }

            // Toggle mostrar/ocultar contraseña
            const passwordToggleButtons = profileContainer.querySelectorAll('[data-password-toggle]');
            passwordToggleButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const inputId = button.dataset.passwordToggle;
                    const input = document.getElementById(inputId);
                    const icon = button.querySelector('[data-password-icon]');
                    
                    if (input && icon) {
                        if (input.type === 'password') {
                            input.type = 'text';
                            icon.classList.remove('fa-eye');
                            icon.classList.add('fa-eye-slash');
                            button.setAttribute('aria-label', @json(gettext('Ocultar contraseña')));
                        } else {
                            input.type = 'password';
                            icon.classList.remove('fa-eye-slash');
                            icon.classList.add('fa-eye');
                            button.setAttribute('aria-label', @json(gettext('Mostrar contraseña')));
                        }
                    }
                });
            });

            @if(session('status'))
                Swal.fire({
                    title: @json(gettext('Actualización exitosa')),
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

