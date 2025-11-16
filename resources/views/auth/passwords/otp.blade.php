{{-- Formulario para ingresar el código OTP --}}
<x-layouts.auth-layout :meta="['title' => 'Verificar código']">
    <div class="rounded-lg border border-surface bg-surface-elevated p-8 shadow-2xl transition-colors">
        <h2 class="text-2xl font-semibold text-heading">Verificar código</h2>
        <p class="mt-2 text-sm text-secondary">
            Escribe el código de 6 dígitos que enviamos a <span class="font-semibold text-heading">{{ $email }}</span>.
        </p>

        @if (session('status'))
            <div class="mt-6 flex items-center gap-3 rounded-xl border border-primary bg-primary-soft px-4 py-3 text-sm text-primary">
                <i class="fa-regular fa-circle-check text-lg"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <form class="mt-8 space-y-6" method="POST" action="{{ route('password.otp.verify') }}" id="otp-form">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="space-y-3">
                <label class="text-sm font-medium text-heading" for="code">Código de verificación</label>
                <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3" id="otp-inputs">
                    @for ($i = 0; $i < 6; $i++)
                        <input
                            type="text"
                            maxlength="1"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            class="h-12 w-12 min-w-[48px] rounded-xl border {{ $errors->has('code') ? 'border-red-500 focus:ring-red-400 focus:border-red-500' : 'border-surface focus:border-primary focus:ring-primary' }} text-center text-lg font-semibold outline-none transition focus:ring-2 flex-shrink-0"
                            data-otp-index="{{ $i }}"
                        >
                    @endfor
                </div>
                <input type="hidden" name="code" id="code" value="{{ old('code') }}">

                @error('code')
                    <p class="text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full rounded-xl bg-primary px-6 py-3 text-sm font-semibold transition hover:bg-primary-strong focus:outline-none focus:ring-2 focus:ring-primary"
            >
                Validar código
            </button>
        </form>

        <div class="mt-6 text-center">
            <div class="flex flex-col items-center gap-2">
                <button
                    type="button"
                    class="text-sm font-medium text-primary hover:text-primary-strong disabled:text-secondary"
                    id="resend-button"
                    data-resend-url="{{ route('password.email') }}"
                    data-email="{{ $email }}"
                    data-csrf="{{ csrf_token() }}"
                >
                    No he recibido el código (<span id="resend-countdown">00:30</span>)
                </button>
                <span id="resend-feedback" class="text-xs text-secondary"></span>
            </div>
            <div class="mt-6">
                <a href="{{ route('login') }}" class="text-sm font-medium text-primary hover:text-primary-strong">
                    Volver al inicio de sesión
                </a>
            </div>
        </div>
    </div>
</x-layouts.auth-layout>

