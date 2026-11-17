<div>
    {{-- Encabezado: saludo y resumen del mes, con la acción principal siempre arriba a la derecha --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <p class="text-sm text-slate-500 dark:text-gray-400">{{ $saludo }}</p>
            <h2 class="page-title">Panel general</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                Este mes se registraron
                <b class="whitespace-nowrap text-siproc-ink dark:text-white">{{ $resumenMes['publicaciones'] }} {{ $resumenMes['publicaciones'] === 1 ? 'publicación' : 'publicaciones' }}</b>
                y <b class="whitespace-nowrap text-siproc-ink dark:text-white">{{ $resumenMes['investigadores'] }} {{ $resumenMes['investigadores'] === 1 ? 'investigador' : 'investigadores' }}</b>.
            </p>
        </div>

        <a href="{{ route('publicaciones.index') }}" wire:navigate class="btn btn-primary shrink-0 self-start">
            <x-icon name="plus" class="h-4 w-4" /> Registrar publicación
        </a>
    </div>

    {{-- Filtros en su propia fila, para que no compitan por espacio con el título --}}
    <div class="mt-5 flex flex-wrap items-center gap-2" role="group" aria-label="Filtros del panel">
        <label for="filtro-anio" class="sr-only">Año</label>
        <select id="filtro-anio" wire:model.live="year" class="field py-2 text-sm">
            <option value="">Todos los años</option>
            @foreach ($anios as $anio)
                <option value="{{ $anio }}">{{ $anio }}</option>
            @endforeach
        </select>
        <label for="filtro-grupo" class="sr-only">Grupo</label>
        <select id="filtro-grupo" wire:model.live="group" class="field max-w-[16rem] py-2 text-sm">
            <option value="">Todos los grupos</option>
            @foreach ($grupos as $grupo)
                <option value="{{ $grupo->cod_minciencias }}">{{ $grupo->group_name }}</option>
            @endforeach
        </select>
        @if ($year !== '' || $group !== '')
            <button type="button" wire:click="limpiarFiltros" class="btn btn-outline">
                <x-icon name="x-mark" class="h-4 w-4" /> Quitar filtros
            </button>
        @endif
    </div>

    <div class="mt-6 space-y-6" wire:loading.class="pointer-events-none" wire:target="year,group,limpiarFiltros">
        {{-- Indicadores --}}
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores">
            @foreach ($indicadores as $indicador)
                <div class="card relative overflow-hidden p-5">
                    <div wire:loading.delay wire:target="year,group,limpiarFiltros" class="skeleton absolute inset-0 rounded-none"></div>
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-500 dark:text-gray-400">{{ $indicador['titulo'] }}</p>
                        <span class="rounded-lg p-2 {{ $indicador['color'] }}"><x-icon :name="$indicador['icono']" class="h-5 w-5" /></span>
                    </div>
                    <p class="mt-3 font-display text-4xl font-bold">{{ number_format($indicador['valor'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm">
                        @isset($indicador['pie']['delta'])
                            @php $delta = $indicador['pie']['delta']; @endphp
                            <span class="font-semibold {{ $delta > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($delta < 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-500') }}">
                                {{ $delta > 0 ? '▲' : ($delta < 0 ? '▼' : '=') }} {{ abs($delta) }}
                            </span>
                        @endisset
                        @isset($indicador['pie']['valor'])
                            <span class="font-semibold text-siproc-ink dark:text-white">{{ $indicador['pie']['valor'] }}</span>
                        @endisset
                        <span class="text-slate-500 dark:text-gray-400">{{ $indicador['pie']['texto'] }}</span>
                    </p>
                </div>
            @endforeach
        </section>

        {{-- Producción por año y por tipo --}}
        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="card relative xl:col-span-2">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="section-title">Producción por año</h3>
                    <span class="text-xs text-slate-500 dark:text-gray-400">Publicaciones por año de publicación</span>
                </div>
                <div class="relative mt-4">
                    <div wire:loading.delay wire:target="year,group,limpiarFiltros" class="skeleton absolute inset-0 z-10"></div>
                    @if ($graficaAnios)
                        <x-chart :config="$graficaAnios" label="Gráfica de barras con las publicaciones por año" />
                    @else
                        <x-empty-state icon="chart-bar" message="Todavía no hay publicaciones con año registrado." class="h-64" />
                    @endif
                </div>
            </div>

            <div class="card relative">
                <h3 class="section-title">Por tipo de producto</h3>
                <div class="relative mt-4">
                    <div wire:loading.delay wire:target="year,group,limpiarFiltros" class="skeleton absolute inset-0 z-10"></div>
                    @if ($graficaTipos)
                        <x-chart :config="$graficaTipos" label="Gráfica circular con la distribución de publicaciones por tipo" height="h-72" />
                    @else
                        <x-empty-state message="No hay publicaciones con estos filtros." class="h-72" />
                    @endif
                </div>
            </div>
        </section>

        {{-- Producción por grupo y actividad reciente --}}
        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="card relative xl:col-span-2">
                <h3 class="section-title">Producción por grupo</h3>
                <div class="relative mt-4">
                    <div wire:loading.delay wire:target="year,group,limpiarFiltros" class="skeleton absolute inset-0 z-10"></div>
                    @if ($graficaGrupos)
                        <x-chart :config="$graficaGrupos" label="Gráfica de barras con las publicaciones de cada grupo de investigación" height="h-72" />
                    @else
                        <x-empty-state icon="user-group" message="Ningún grupo tiene publicaciones con estos filtros." class="h-72" />
                    @endif
                </div>
            </div>

            <div class="card">
                <h3 class="section-title">Actividad reciente</h3>
                @if ($actividad->isEmpty())
                    <x-empty-state icon="clock" message="Aquí aparecerá lo que se registre en el sistema." />
                @else
                    <ul class="mt-4 space-y-4 text-sm">
                        @foreach ($actividad as $evento)
                            <li class="flex gap-3">
                                <x-avatar :name="$evento['quien'] ?? 'Sistema'" size="sm" />
                                <div class="min-w-0">
                                    <p class="text-slate-700 dark:text-gray-300">
                                        <b class="text-siproc-ink dark:text-white">{{ $evento['quien'] ?? 'Alguien' }}</b>
                                        {{ $evento['accion'] }}
                                        @if ($evento['url'])
                                            <a href="{{ $evento['url'] }}" wire:navigate class="font-medium text-siproc-red-dark hover:underline dark:text-red-300">“{{ \Illuminate\Support\Str::limit($evento['que'], 70) }}”</a>
                                        @else
                                            <span class="font-medium">“{{ \Illuminate\Support\Str::limit($evento['que'], 70) }}”</span>
                                        @endif
                                    </p>
                                    <p class="mt-0.5 text-xs text-slate-400 dark:text-gray-500">{{ $evento['cuando']->diffForHumans() }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        {{-- Artículos por cuartil --}}
        <section class="card">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="section-title">Artículos por cuartil de la revista</h3>
                <span class="text-xs text-slate-500 dark:text-gray-400">{{ $cuartiles['total'] }} {{ $cuartiles['total'] === 1 ? 'artículo' : 'artículos' }}</span>
            </div>
            @if ($cuartiles['total'] > 0)
                <div class="mt-4 flex h-4 overflow-hidden rounded-full" role="img"
                    aria-label="Distribución de artículos por cuartil: {{ $cuartiles['items']->map(fn ($c) => $c['categoria'].' '.$c['total'])->implode(', ') }}">
                    @foreach ($cuartiles['items'] as $c)
                        <div class="{{ $c['clase'] }}" style="width: {{ $c['porcentaje'] }}%"></div>
                    @endforeach
                </div>
                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                    @foreach ($cuartiles['items'] as $c)
                        <span class="flex items-center gap-2 text-slate-600 dark:text-gray-300">
                            <span class="h-3 w-3 rounded-sm {{ $c['clase'] }}"></span>
                            {{ $c['categoria'] }} · <b>{{ $c['total'] }}</b>
                        </span>
                    @endforeach
                </div>
            @else
                <x-empty-state icon="newspaper" message="No hay artículos con estos filtros." class="py-6" />
            @endif
        </section>
    </div>
</div>
