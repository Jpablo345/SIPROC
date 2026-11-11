<x-slot name="header">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="page-eyebrow">Dashboard</p>
            <h2 class="page-title">Producción Científica</h2>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('publicaciones.index') }}" wire:navigate
                class="btn btn-outline">
                <x-icon name="plus" class="h-4 w-4" />
                Registrar publicación
            </a>
        </div>
    </div>
</x-slot>

<div class="relative">
    <div class="pointer-events-none absolute -left-16 -top-12 h-40 w-40 rounded-full bg-siproc-rose blur-2xl"></div>
    <div class="pointer-events-none absolute right-10 top-32 h-56 w-56 rounded-full bg-siproc-sand blur-3xl"></div>

    <div class="relative grid grid-cols-1 gap-6">

        {{-- ── Stats ───────────────────────────────────────────────────────── --}}
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">

            {{-- Tarjeta 1: Publicaciones totales --}}
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Publicaciones totales</p>
                <div class="mt-3 flex items-end justify-between">
                    <span class="text-3xl font-semibold text-siproc-ink">{{ $totalPublications }}</span>
                    <div class="flex flex-col items-end gap-1">
                        <span class="rounded-full bg-siproc-rose px-3 py-1 text-xs font-semibold text-siproc-red-dark">
                            {{ $totalArticles }} art.
                        </span>
                        <span class="rounded-full bg-siproc-sand px-3 py-1 text-xs font-semibold text-siproc-red-dark">
                            {{ $totalBooks }} lib.
                        </span>
                    </div>
                </div>
            </div>

            {{-- Tarjeta 2: Investigadores --}}
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Investigadores</p>
                <div class="mt-3 flex items-end justify-between">
                    <span class="text-3xl font-semibold text-siproc-ink">{{ $totalResearchers }}</span>
                    <span class="rounded-full bg-siproc-rose px-3 py-1 text-xs font-semibold text-siproc-red-dark">
                        {{ $totalGroups }} {{ $totalGroups === 1 ? 'grupo' : 'grupos' }}
                    </span>
                </div>
            </div>

            {{-- Tarjeta 3: Artículos científicos --}}
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Artículos científicos</p>
                <div class="mt-3 flex items-end justify-between">
                    <span class="text-3xl font-semibold text-siproc-ink">{{ $totalArticles }}</span>
                    @if ($totalPublications > 0)
                        <span class="rounded-full bg-siproc-rose px-3 py-1 text-xs font-semibold text-siproc-red-dark">
                            {{ round($totalArticles / $totalPublications * 100) }}% del total
                        </span>
                    @endif
                </div>
            </div>

            {{-- Tarjeta 4: Libros --}}
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Libros</p>
                <div class="mt-3 flex items-end justify-between">
                    <span class="text-3xl font-semibold text-siproc-ink">{{ $totalBooks }}</span>
                    @if ($totalPublications > 0)
                        <span class="rounded-full bg-siproc-sand px-3 py-1 text-xs font-semibold text-siproc-red-dark">
                            {{ round($totalBooks / $totalPublications * 100) }}% del total
                        </span>
                    @endif
                </div>
            </div>

            {{-- Tarjeta 5: Eventos registrados --}}
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Eventos registrados</p>
                <div class="mt-3 flex items-end justify-between">
                    <span class="text-3xl font-semibold text-siproc-ink">{{ $totalEvents }}</span>
                </div>
            </div>

        </section>

        {{-- ── Recientes + Por grupos ───────────────────────────────────────── --}}
        <section class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">

            <div class="card">
                <div class="flex items-center justify-between">
                    <h3 class="section-title">Publicaciones recientes</h3>
                    <a href="{{ route('publicaciones.index') }}" wire:navigate
                        class="btn btn-sm btn-outline">
                        Ver todas
                    </a>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($recentPublications as $pub)
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:gap-3 rounded-xl border border-siproc-line bg-siproc-blush p-4">
                            <span
                                class="max-w-full shrink-0 self-start truncate rounded-full bg-siproc-red px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-white sm:max-w-[45%]">
                                {{ $pub['type'] }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-siproc-ink">{{ $pub['title'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $pub['detail'] }}</p>
                            </div>
                        </div>
                    @empty
                        <div
                            class="rounded-xl border border-dashed border-siproc-line table-empty">
                            No hay publicaciones registradas todavía.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <h3 class="section-title">Producción por grupos</h3>

                <div class="mt-4 space-y-3">
                    @forelse ($productionByGroup as $group)
                        <div
                            class="flex items-center justify-between rounded-xl border border-siproc-line bg-white px-4 py-3">
                            <span class="truncate text-sm font-semibold text-siproc-ink">{{ $group['name'] }}</span>
                            <span
                                class="ml-3 shrink-0 rounded-full bg-siproc-rose px-2 py-0.5 text-xs font-semibold text-siproc-red-dark">
                                {{ $group['count'] }}
                            </span>
                        </div>
                    @empty
                        <div
                            class="rounded-xl border border-dashed border-siproc-line table-empty">
                            No hay grupos registrados.
                        </div>
                    @endforelse
                </div>

                @if ($totalPublications > 0)
                    <div class="mt-6 alert">
                        {{ $totalPublications }} publicaciones en total
                    </div>
                @endif
            </div>

        </section>

        <section class="card">
            <h3 class="section-title">Producción por tipo</h3>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($productionByType as $type)
                    <div class="flex items-center justify-between rounded-xl border border-siproc-line bg-white px-4 py-3">
                        <span class="min-w-0 truncate text-sm font-semibold text-siproc-ink">
                            {{ $type['name'] }}
                            @unless ($type['is_system'])
                                <span class="ml-1 text-[10px] font-normal uppercase tracking-wider text-slate-400">configurable</span>
                            @endunless
                        </span>
                        <span class="ml-3 shrink-0 rounded-full bg-siproc-rose px-2 py-0.5 text-xs font-semibold text-siproc-red-dark">
                            {{ $type['count'] }}
                        </span>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-siproc-line table-empty sm:col-span-2 lg:col-span-4">
                        No hay tipos de publicación registrados.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
