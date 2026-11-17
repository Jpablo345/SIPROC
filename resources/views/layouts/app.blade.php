<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" href="{{ asset('img/logoufps.png') }}?v=1" type="image/png">

    {{-- Open Sans: fuente complementaria del manual de identidad UFPSO --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=open-sans:400,500,600,700&display=swap" rel="stylesheet" />

    <x-theme-script />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased">
    {{-- Avisos flotantes. Va primero para que escuche los avisos que emiten las páginas al cargar. --}}
    <x-toasts />

    <div class="min-h-screen">
        <div class="flex min-h-screen flex-col lg:flex-row">
            <livewire:layout.navigation />

            <div class="flex min-h-screen min-w-0 flex-1 flex-col">
                @if (isset($header))
                    <header class="sticky top-0 z-30 border-b border-siproc-line bg-siproc-cream/80 backdrop-blur dark:border-gray-800 dark:bg-gray-950/80">
                        <div class="mx-auto w-full max-w-7xl px-6 py-4 lg:px-10">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <main class="flex-1 px-6 py-6 lg:px-10 lg:py-8">
                    <div class="mx-auto w-full max-w-7xl">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </div>

    {{-- Búsqueda global (Ctrl+K) --}}
    <livewire:busqueda-global />

    {{-- Widget de accesibilidad --}}
    <x-accessibility-widget />

    {{-- Modal de confirmación para acciones como eliminar --}}
    <x-confirm-dialog />

    {{-- El título de la pestaña toma el título visible de cada página (.page-title) --}}
    <script data-navigate-once>
        (() => {
            const appName = @js(config('app.name', 'SIPROC'));
            const setTitle = () => {
                const heading = document.querySelector('.page-title');
                const text = heading ? heading.textContent.trim() : '';
                document.title = text ? `${text} · ${appName}` : appName;
            };
            setTitle();
            document.addEventListener('livewire:navigated', setTitle);
        })();
    </script>

</body>

</html>
