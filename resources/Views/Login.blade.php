{{-- 
/**
 * @fileoverview Vista de login reutilizando el AuthLayout y componentes tailwind.
 */
--}}

<x-layouts.auth-layout :meta="$meta ?? []">
    <div class="rounded-lg border border-surface bg-surface-elevated p-8 shadow-2xl transition-colors">
        <h2 class="text-2xl font-semibold text-heading">Iniciar sesión</h2>
        <p class="mt-2 text-sm text-secondary">
            Accede para continuar gestionando tus órdenes y equipos.
        </p>

        @if (session('status'))
            <div class="mt-6 flex items-center gap-3 rounded-xl border border-primary bg-primary-soft px-4 py-3 text-sm text-primary">
                <i class="fa-regular fa-circle-check text-lg"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif
        <form class="mt-8 space-y-6" method="POST" action="{{ route('login.attempt') }}">
            @csrf
            <div class="space-y-2">
                <label class="text-sm font-medium text-heading" for="email">Correo electrónico</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    value="{{ old('email') }}"
                    class="w-full rounded-xl border {{ $errors->has('email') ? 'border-red-500 focus:ring-red-400 focus:border-red-500' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 text-sm text-heading outline-none transition focus:ring-2"
                    placeholder="usuario@mercuryapp.com"
                >
                @error('email')
                    <p class="text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-heading" for="password">Contraseña</label>
                <div class="relative">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        class="w-full rounded-xl border {{ $errors->has('password') ? 'border-red-500 focus:ring-red-400 focus:border-red-500' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 pr-11 text-sm text-heading outline-none transition focus:ring-2"
                        placeholder="••••••••"
                        data-password-field
                    >
                    <button
                        type="button"
                        class="absolute inset-y-0 right-3 inline-flex items-center text-secondary transition hover:text-primary"
                        aria-label="Mostrar u ocultar contraseña"
                        aria-pressed="false"
                        data-toggle-password="password"
                    >
                        <i data-icon="show" class="fa-regular fa-eye text-base"></i>
                        <i data-icon="hide" class="fa-regular fa-eye-slash text-base" style="display:none;"></i>
                    </button>
                </div>
                @error('password')
                    <p class="text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-secondary">
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                    class="rounded border-surface text-primary focus:ring-primary"
                    @checked(old('remember'))
                >
                <span>Recordarme</span>
            </label>
            <button
                type="submit"
                class="w-full rounded-xl bg-primary px-6 py-3 text-sm font-semibold transition hover:bg-primary-strong focus:outline-none focus:ring-2 focus:ring-primary"
            >
                Ingresar
            </button>
        </form>
        <div class="mt-6 text-center">
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary hover:text-primary-strong">
                ¿Olvidaste tu contraseña?
            </a>
        </div>
    </div>
</x-layouts.auth-layout>

