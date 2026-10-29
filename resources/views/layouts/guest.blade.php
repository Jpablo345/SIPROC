<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" href="{{ asset('img/logoufps.png') }}?v=1" type="image/png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-gray-900 antialiased bg-red-700">

    <header class="w-full bg-white shadow-md  border-zinc-200 sticky top-0 z-50">
        <div class="flex flex-col md:flex-row items-stretch min-h-[100px]">
            <a href="/" wire:navigate class="block">
                <div class="bg-white py-4 px-6 flex items-center border-r border-zinc-200">
                    <img src="{{ asset('img/logoufps.png') }}" alt="UFPS" class="h-16 w-auto">
                    <div class="ml-4 flex flex-col justify-center">
                        <h1 class="text-zinc-800 font-bold text-sm sm:text-base  leading-tight tracking-tight">
                            Universidad Francisco <br> de Paula Santander
                        </h1>
                        <p class="text-[10px] text-zinc-500 font-medium italic">Ocaña - Colombia</p>
                        <p class="text-[8px] text-zinc-400 uppercase tracking-tighter">Vigilada Mineducación</p>
                    </div>
                </div>
            </a>


            <div class="bg-red-600 flex-grow flex items-center justify-end px-10">
                <span class="text-white font-black text-6xl tracking-[0.2em] select-none">
                    SIPROC
                </span>
            </div>
        </div>
    </header>

    <div class="min-h-[calc(100vh-100px)] flex flex-col sm:justify-center items-center pt-6 sm:pt-0">

        <div class="w-full sm:max-w-md mt-6">
            {{ $slot }}
        </div>

        <div class="mt-8 text-zinc-400 text-xs font-bold uppercase tracking-widest pb-8">
            UFPSO
        </div>
    </div>
</body>

</html>