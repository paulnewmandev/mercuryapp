{{-- Formulario para solicitar el envío del código OTP --}}
<x-layouts.auth-layout :meta="['title' => 'Recuperar contraseña']">
    <div class="rounded-lg border border-surface bg-surface-elevated p-8 shadow-2xl transition-colors">
        <h2 class="text-2xl font-semibold text-heading">Restablecer contraseña</h2>
        <p class="mt-2 text-sm text-secondary">
            Ingresa el correo asociado a tu cuenta y te enviaremos un código de verificación.
        </p>

        @if (session('status'))
            <div class="mt-6 rounded-xl border border-primary bg-primary-soft px-4 py-3 text-sm text-primary">
                {{ session('status') }}
            </div>
        @endif

        <form class="mt-8 space-y-6" method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="space-y-2">
                <label class="text-sm font-medium text-heading" for="email">Correo electrónico</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="w-full rounded-xl border {{ $errors->has('email') ? 'border-red-500 focus:ring-red-400 focus:border-red-500' : 'border-surface focus:border-primary focus:ring-primary' }} px-4 py-3 text-sm text-heading outline-none transition focus:ring-2"
                    placeholder="usuario@mercuryapp.com"
                >
                @error('email')
                    <p class="text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full rounded-xl bg-primary px-6 py-3 text-sm font-semibold transition hover:bg-primary-strong focus:outline-none focus:ring-2 focus:ring-primary"
            >
                Enviar código
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-sm font-medium text-primary hover:text-primary-strong">
                Volver al inicio de sesión
            </a>
        </div>
    </div>
</x-layouts.auth-layout>

