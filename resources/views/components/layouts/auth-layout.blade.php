{{-- 
/**
 * @fileoverview Layout para pantallas de autenticación con fondo ilustrado.
 */
--}}
<x-layouts.base-layout :meta="$meta">
    <section class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#001A35]">
        <div class="absolute inset-0">
            <img
                src="/theme-images/shape/grid-01.svg"
                alt="Fondo con patrón"
                class="h-full w-full object-cover opacity-20"
                loading="lazy"
            >
        </div>
        <div class="relative z-10 flex w-full max-w-6xl flex-col items-center px-6 py-12 md:flex-row md:items-center md:justify-between md:gap-16 md:px-16">
            <div class="flex w-full max-w-md flex-col items-center gap-4 text-inverted md:max-w-none md:w-1/2 md:items-start md:gap-6 md:text-left">
                <div class="flex items-center gap-3">
                    <img src="/theme-images/logo/icon-128x128.png" alt="MercuryApp" class="h-14 w-14 rounded-xl object-cover" loading="lazy">
                    <span class="text-3xl font-semibold tracking-tight md:text-4xl">MercuryApp</span>
                </div>
                <div class="hidden w-full md:block">
                    <h1 class="text-5xl font-bold leading-tight text-inverted">
                        Bienvenido de regreso a MercuryApp
                    </h1>
                    <p class="mt-4 max-w-lg text-lg text-inverted-secondary">
                        Gestiona órdenes de reparación, supervisa a tus técnicos y optimiza la experiencia de tus clientes desde una sola plataforma.
                    </p>
                </div>
            </div>
            <div class="mt-10 w-full max-w-md md:mt-0 md:w-1/2 md:max-w-lg">
                {{ $slot }}
            </div>
        </div>
    </section>
</x-layouts.base-layout>

