{{-- 
/**
 * @fileoverview Pantalla de inicio de sesión para MercuryApp.
 */
--}}
<x-layouts.auth-layout :meta="$meta">
    <div class="rounded-2xl bg-white/95 p-6 shadow-xl ring-1 ring-black/5 backdrop-blur dark:bg-neutral-900/90 dark:ring-white/10 md:p-8">
        <div class="mb-6 text-center">
            <h2 class="text-2xl font-semibold tracking-tight text-neutral-900 dark:text-white">Inicia sesión</h2>
            <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">Accede a tu cuenta para continuar</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800 dark:border-red-500/40 dark:bg-red-900/30 dark:text-red-200">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-neutral-800 dark:text-neutral-200">Correo electrónico</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    class="block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-neutral-900 placeholder-neutral-400 shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100"
                    placeholder="tucorreo@ejemplo.com"
                >
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between">
                    <label for="password" class="block text-sm font-medium text-neutral-800 dark:text-neutral-200">Contraseña</label>
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-sky-700 hover:text-sky-600 dark:text-sky-400 dark:hover:text-sky-300">¿Olvidaste tu contraseña?</a>
                </div>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-neutral-900 placeholder-neutral-400 shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100"
                    placeholder="••••••••"
                >
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300">
                    <input
                        type="checkbox"
                        name="remember"
                        class="h-4 w-4 rounded border-neutral-300 text-sky-600 focus:ring-sky-500 dark:border-neutral-600 dark:bg-neutral-800"
                        {{ old('remember') ? 'checked' : '' }}
                    >
                    <span>Recordarme</span>
                </label>
            </div>

            <button
                type="submit"
                class="w-full rounded-xl bg-primary px-6 py-3 text-sm font-semibold transition hover:bg-primary-strong focus:outline-none focus:ring-2 focus:ring-primary"
            >
                <span>Ingresar</span>
            </button>
        </form>
    </div>
</x-layouts.auth-layout>


