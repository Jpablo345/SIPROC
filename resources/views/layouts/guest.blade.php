<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" href="{{ asset('img/logoufps.png') }}?v=1" type="image/png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=open-sans:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{--
    Pantallas de acceso (login, registro, recuperar contraseña).
    Siempre en modo claro: el logo a color del manual UFPSO va sobre fondo blanco.
--}}
@php
    $foto = config('siproc.foto_login');
    $hayFoto = $foto && is_file(public_path($foto));
    $cifras = [
        ['valor' => \App\Models\Publication::count(), 'texto' => 'publicaciones'],
        ['valor' => \App\Models\Researcher::count(), 'texto' => 'investigadores'],
        ['valor' => \App\Models\ResearchGroup::count(), 'texto' => 'grupos'],
    ];
@endphp

{{-- data-tema-fijo: el script del tema no aplica el modo oscuro aquí, aunque se llegue sin recargar (wire:navigate). --}}
<body class="bg-white font-sans text-siproc-ink antialiased" data-tema-fijo="claro">
    <script>document.documentElement.classList.remove('dark');</script>
    <div class="grid min-h-screen lg:grid-cols-[1.1fr_1fr]">

        {{--
            Panel institucional, fijo en pantallas grandes: solo se desplaza el formulario.
            Lleva el nombre del sistema; el de la universidad ya lo dice el logo del formulario.
            El manual no permite el logo sobre rojo, por eso aquí no va.
        --}}
        <aside class="relative hidden flex-col overflow-hidden bg-siproc-red-deep p-12 text-white lg:sticky lg:top-0 lg:flex lg:h-screen">
            <div class="absolute inset-0 opacity-[.06]"
                style="background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);background-size:48px 48px"></div>

            <div class="relative my-auto max-w-lg">
                @if ($hayFoto)
                    <img src="{{ asset($foto) }}" alt="Campus de la UFPS Seccional Ocaña"
                        class="mb-10 aspect-[16/9] max-h-[40vh] w-full rounded-2xl object-cover shadow-2xl">
                @endif
                <p class="font-display text-sm font-bold uppercase tracking-[0.3em] text-white/70">SIPROC</p>
                <h1 class="mt-3 font-display text-4xl font-bold leading-tight">Sistema de Producción Científica</h1>
                <p class="mt-4 text-lg text-white/80">
                    Registro y seguimiento de artículos, libros, eventos y demás productos de investigación de la Seccional Ocaña.
                </p>
            </div>

            <div class="relative grid grid-cols-3 gap-6 border-t border-white/20 pt-6">
                @foreach ($cifras as $cifra)
                    <div>
                        <p class="font-display text-3xl font-bold">{{ number_format($cifra['valor'], 0, ',', '.') }}</p>
                        <p class="text-sm text-white/70">{{ $cifra['texto'] }}</p>
                    </div>
                @endforeach
            </div>
        </aside>

        {{-- Formulario --}}
        <main class="flex flex-col items-center justify-center px-6 py-10 sm:px-10">
            <div class="w-full max-w-md">
                {{-- Logo oficial a 80 px de alto: el mínimo digital del manual --}}
                <a href="{{ route('login') }}" wire:navigate class="inline-block">
                    <img src="{{ asset('img/logo_horizontal_full_color.png') }}"
                        alt="Universidad Francisco de Paula Santander Ocaña" class="h-20 w-auto">
                </a>

                {{-- Solo en pantallas pequeñas, donde no se ve el panel rojo con el nombre del sistema --}}
                <div class="mt-8 border-l-4 border-siproc-red pl-4 lg:hidden">
                    <p class="font-display text-xl font-bold tracking-wide">SIPROC</p>
                    <p class="text-sm text-gray-500">Sistema de Producción Científica</p>
                </div>

                <div class="mt-8">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>
</body>

</html>
