<x-slot name="header">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-[#9c1c1c]">Dashboard</p>
            <h2 class="text-2xl font-semibold text-[#2b2323]">Produccion Cientifica</h2>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('publicaciones.index') }}" wire:navigate
                class="rounded-full border border-[#9c1c1c]/30 px-4 py-2 text-xs font-semibold text-[#7a1515] hover:bg-[#f2d3d3]">
                + Registrar publicacion
            </a>
        </div>
    </div>
</x-slot>

<div class="relative">
    <div class="pointer-events-none absolute -left-16 -top-12 h-40 w-40 rounded-full bg-[#f2d3d3] blur-2xl"></div>
    <div class="pointer-events-none absolute right-10 top-32 h-56 w-56 rounded-full bg-[#f7e2d2] blur-3xl"></div>

    <div class="relative grid gap-6">

        {{-- ── Stats ───────────────────────────────────────────────────────── --}}
        {{-- ── Stats ───────────────────────────────────────────────────────── --}}
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">

            {{-- Tarjeta 1: Publicaciones totales --}}
            <div class="rounded-2xl border border-white/60 bg-white/80 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Publicaciones totales</p>
                <div class="mt-3 flex items-end justify-between">
                    <span class="text-3xl font-semibold text-[#2b2323]">{{ $totalPublications }}</span>
                    <div class="flex flex-col items-end gap-1">
                        <span class="rounded-full bg-[#f2d3d3] px-3 py-1 text-xs font-semibold text-[#7a1515]">
                            {{ $totalArticles }} art.
                        </span>
                        <span class="rounded-full bg-[#f7e2d2] px-3 py-1 text-xs font-semibold text-[#7a1515]">
                            {{ $totalBooks }} lib.
                        </span>
                    </div>
                </div>
            </div>

            {{-- Tarjeta 2: Investigadores --}}
            <div class="rounded-2xl border border-white/60 bg-white/80 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Investigadores</p>
                <div class="mt-3 flex items-end justify-between">
                    <span class="text-3xl font-semibold text-[#2b2323]">{{ $totalResearchers }}</span>
                    <span class="rounded-full bg-[#f2d3d3] px-3 py-1 text-xs font-semibold text-[#7a1515]">
                        {{ $totalGroups }} {{ $totalGroups === 1 ? 'grupo' : 'grupos' }}
                    </span>
                </div>
            </div>

            {{-- Tarjeta 3: Artículos científicos --}}
            <div class="rounded-2xl border border-white/60 bg-white/80 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Articulos cientificos</p>
                <div class="mt-3 flex items-end justify-between">
                    <span class="text-3xl font-semibold text-[#2b2323]">{{ $totalArticles }}</span>
                    @if ($totalPublications > 0)
                        <span class="rounded-full bg-[#f2d3d3] px-3 py-1 text-xs font-semibold text-[#7a1515]">
                            {{ round($totalArticles / $totalPublications * 100) }}% del total
                        </span>
                    @endif
                </div>
            </div>

            {{-- Tarjeta 4: Libros --}}
            <div class="rounded-2xl border border-white/60 bg-white/80 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Libros</p>
                <div class="mt-3 flex items-end justify-between">
                    <span class="text-3xl font-semibold text-[#2b2323]">{{ $totalBooks }}</span>
                    @if ($totalPublications > 0)
                        <span class="rounded-full bg-[#f7e2d2] px-3 py-1 text-xs font-semibold text-[#7a1515]">
                            {{ round($totalBooks / $totalPublications * 100) }}% del total
                        </span>
                    @endif
                </div>
            </div>

            {{-- Tarjeta 5: Eventos registrados --}}
            <div class="rounded-2xl border border-white/60 bg-white/80 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Eventos registrados</p>
                <div class="mt-3 flex items-end justify-between">
                    <span class="text-3xl font-semibold text-[#2b2323]">{{ $totalEvents }}</span>
                </div>
            </div>

        </section>

        {{-- ── Recientes + Por grupos ───────────────────────────────────────── --}}
        <section class="grid gap-6 lg:grid-cols-[2fr_1fr]">

            <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-[#2b2323]">Publicaciones recientes</h3>
                    <a href="{{ route('publicaciones.index') }}" wire:navigate
                        class="rounded-full border border-[#9c1c1c]/30 px-3 py-1 text-xs font-semibold text-[#7a1515] hover:bg-[#f2d3d3]">
                        Ver todas
                    </a>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($recentPublications as $pub)
                        <div class="flex items-start gap-3 rounded-xl border border-[#f0dede] bg-[#fff7f7] p-4">
                            <span
                                class="shrink-0 rounded-full bg-[#9c1c1c] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-white">
                                {{ $pub['type'] }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-[#2b2323]">{{ $pub['title'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $pub['detail'] }}</p>
                            </div>
                        </div>
                    @empty
                        <div
                            class="rounded-xl border border-dashed border-[#f0dede] px-4 py-6 text-center text-sm text-slate-500">
                            No hay publicaciones registradas todavia.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-[#2b2323]">Produccion por grupos</h3>

                <div class="mt-4 space-y-3">
                    @forelse ($productionByGroup as $group)
                        <div
                            class="flex items-center justify-between rounded-xl border border-[#f0dede] bg-white px-4 py-3">
                            <span class="truncate text-sm font-semibold text-[#2b2323]">{{ $group['name'] }}</span>
                            <span
                                class="ml-3 shrink-0 rounded-full bg-[#f2d3d3] px-2 py-0.5 text-xs font-semibold text-[#7a1515]">
                                {{ $group['count'] }}
                            </span>
                        </div>
                    @empty
                        <div
                            class="rounded-xl border border-dashed border-[#f0dede] px-4 py-6 text-center text-sm text-slate-500">
                            No hay grupos registrados.
                        </div>
                    @endforelse
                </div>

                @if ($totalPublications > 0)
                    <div class="mt-6 rounded-xl bg-[#f7e2d2] px-4 py-3 text-xs font-semibold text-[#7a1515]">
                        {{ $totalPublications }} publicaciones en total
                    </div>
                @endif
            </div>

        </section>

        <section class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-[#2b2323]">Produccion por tipo</h3>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($productionByType as $type)
                    <div class="flex items-center justify-between rounded-xl border border-[#f0dede] bg-white px-4 py-3">
                        <span class="truncate text-sm font-semibold text-[#2b2323]">
                            {{ $type['name'] }}
                            @unless ($type['is_system'])
                                <span class="ml-1 text-[10px] font-normal uppercase tracking-wider text-slate-400">configurable</span>
                            @endunless
                        </span>
                        <span class="ml-3 shrink-0 rounded-full bg-[#f2d3d3] px-2 py-0.5 text-xs font-semibold text-[#7a1515]">
                            {{ $type['count'] }}
                        </span>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-[#f0dede] px-4 py-6 text-center text-sm text-slate-500 sm:col-span-2 lg:col-span-4">
                        No hay tipos de publicación registrados.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>

{{-- ── Archivo original (resources/views/dashboard.blade.php)
─────────────────────────────────────────────────────────────── --}}