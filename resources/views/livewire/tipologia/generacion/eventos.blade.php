<?php

use App\Models\Event;
use App\Models\ResearcherEvent;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public ?int $editingId = null;

    public ?string $event_year = null;
    public ?string $event_month = null;
    public string $event_name = '';
    public ?string $event_scope = null;
    public ?string $event_url = null;
    public string $participation_type = 'Ponente';

    public ?int $host_institution_id = null;
    public ?int $origin_institution_id = null;

    // Conferencistas (sincronizado con el selector vía wire:model)
    public array $selectedSpeakers = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[On('institucion-seleccionada-host')]
    public function setHost(int $id): void
    {
        $this->host_institution_id = $id;
    }

    #[On('institucion-seleccionada-origin')]
    public function setOrigin(int $id): void
    {
        $this->origin_institution_id = $id;
    }

    public function edit(int $eventId): void
    {
        $event = Event::with(['hostInstitution', 'originInstitution', 'researcherEvents.researcher.researchGroup'])->findOrFail($eventId);
        $this->authorize('update', $event);

        $this->editingId = $event->event_id;
        $this->event_year = $event->event_year;
        $this->event_month = $event->event_month;
        $this->event_name = $event->event_name;
        $this->event_scope = $event->event_scope;
        $this->event_url = $event->event_url;
        $this->host_institution_id = $event->host_institution_id;
        $this->origin_institution_id = $event->origin_institution_id;

        // Tomar el tipo de participación del primer conferencista
        $firstResearcher = $event->researcherEvents->first();
        $this->participation_type = $firstResearcher?->participation_type ?? 'Ponente';

        // Cargar conferencistas
        $this->selectedSpeakers = $event->researcherEvents
            ->map(fn($re) => [
                'researcher_id' => $re->researcher->researcher_id,
                'name' => trim(implode(' ', array_filter([
                    $re->researcher->name_1,
                    $re->researcher->name_2,
                    $re->researcher->last_name_1,
                    $re->researcher->last_name_2,
                ]))),
                'group' => $re->researcher->researchGroup?->group_name,
            ])
            ->values()
            ->toArray();

        // Cargar instituciones
        if ($event->host_institution_id) {
            $this->dispatch(
                'cargar-institucion-host',
                id: $event->hostInstitution->institution_id,
                name: $event->hostInstitution->institution_name
            );
        }

        if ($event->origin_institution_id) {
            $this->dispatch(
                'cargar-institucion-origin',
                id: $event->originInstitution->institution_id,
                name: $event->originInstitution->institution_name
            );
        }
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        if ($this->editingId) {
            $this->authorize('update', Event::findOrFail($this->editingId));
        } else {
            $this->authorize('create', Event::class);
        }
        $this->validate([
            'event_name' => ['required', 'string', 'max:255'],
            'event_year' => ['nullable', 'regex:/^\d{4}$/'],
            'event_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'event_scope' => ['nullable', 'in:Nacional,Internacional'],
            'event_url' => ['nullable', 'url:http,https', 'max:255'],
            'host_institution_id' => ['nullable', 'integer', 'exists:institution,institution_id'],
            'origin_institution_id' => ['nullable', 'integer', 'exists:institution,institution_id'],
            // Mismos valores que el CHECK ck_participation_type de researcher_event
            'participation_type' => ['required', 'in:Ponente,Asistente,Organizador'],
            'selectedSpeakers' => ['array'],
            'selectedSpeakers.*.researcher_id' => ['required', 'integer', 'distinct', 'exists:researcher,researcher_id'],
        ]);

        $data = [
            'event_name' => trim($this->event_name),
            'event_year' => $this->event_year,
            'event_month' => $this->event_month,
            'event_scope' => $this->event_scope,
            'event_url' => $this->event_url ? trim($this->event_url) : null,
            'host_institution_id' => $this->host_institution_id,
            'origin_institution_id' => $this->origin_institution_id,
        ];

        DB::transaction(function () use ($data) {
            if ($this->editingId) {
                Event::where('event_id', $this->editingId)->update($data);
                $eventId = $this->editingId;
            } else {
                $event = Event::create($data);
                $eventId = $event->event_id;
            }

            // Sincronizar conferencistas en la misma transacción
            ResearcherEvent::where('event_id', $eventId)->delete();
            foreach ($this->selectedSpeakers as $speaker) {
                ResearcherEvent::create([
                    'event_id' => $eventId,
                    'researcher_id' => $speaker['researcher_id'],
                    'presentation_title' => null,
                    'participation_type' => $this->participation_type,
                ]);
            }
        });

        session()->flash('status', $this->editingId ? 'Evento actualizado.' : 'Evento creado.');
        $this->resetForm();
    }

    public function delete(int $eventId): void
    {
        //Event::where('event_id', $eventId)->delete();
        $event = Event::findOrFail($eventId);
        $this->authorize('delete', $event);
        $event->delete();

        session()->flash('status', 'Evento eliminado.');
        $this->resetPage();
    }

    public function getEventsProperty()
    {
        $term = trim($this->search);

        return Event::query()
            ->with(['hostInstitution', 'originInstitution', 'researchers', 'researcherEvents'])
            ->when($term !== '', function ($query) use ($term) {
                $like = '%' . $term . '%';
                $query->where('event_name', 'ilike', $like)
                    ->orWhereRaw('CAST(event_year AS TEXT) LIKE ?', [$like])
                    ->orWhere('event_scope', 'ilike', $like)
                    ->orWhereHas('hostInstitution', fn($q) => $q->where('institution_name', 'ilike', $like))
                    ->orWhereHas('originInstitution', fn($q) => $q->where('institution_name', 'ilike', $like))
                    ->orWhereHas(
                        'researchers',
                        fn($q) => $q
                            ->where('name_1', 'ilike', $like)
                            ->orWhere('last_name_1', 'ilike', $like)
                    );
            })
            ->orderByDesc('event_id')
            ->paginate(10);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->event_year = null;
        $this->event_month = null;
        $this->event_name = '';
        $this->event_scope = null;
        $this->event_url = null;
        $this->participation_type = 'Ponente';
        $this->host_institution_id = null;
        $this->origin_institution_id = null;
        $this->selectedSpeakers = [];
        $this->dispatch('resetear-institucion');
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">

    {{-- Formulario --}}
    <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-[#2b2323]">
                    {{ $this->editingId ? 'Editar evento' : 'Nuevo evento' }}
                </h3>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">
                    Datos generales del evento
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($this->editingId)
                    <button type="button" wire:click="cancelEdit"
                        class="rounded-full border border-[#9c1c1c]/30 px-4 py-2 text-xs font-semibold text-[#7a1515] hover:bg-[#f2d3d3]">
                        Cancelar
                    </button>
                @endif
                <button type="button" wire:click="save"
                    class="rounded-full bg-[#9c1c1c] px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[#7a1515]">
                    {{ $this->editingId ? 'Guardar cambios' : 'Crear evento' }}
                </button>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-xl bg-[#f7e2d2] px-4 py-3 text-xs font-semibold text-[#7a1515]">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label :value="'Año'" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input wire:model.defer="event_year" type="text" class="mt-2 block w-full" placeholder="2024" />
                <x-input-error :messages="$errors->get('event_year')" class="mt-2" />
            </div>
            <div>
                <x-input-label :value="'Mes'" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <select wire:model.defer="event_month"
                    class="mt-2 block w-full rounded-md border-red-200 bg-white text-zinc-900 shadow-sm transition-colors focus:border-red-600 focus:ring-red-600">
                    <option value="">Sin definir</option>
                    @foreach(['1' => 'Enero', '2' => 'Febrero', '3' => 'Marzo', '4' => 'Abril', '5' => 'Mayo', '6' => 'Junio', '7' => 'Julio', '8' => 'Agosto', '9' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'] as $num => $nombre)
                        <option value="{{ $num }}">{{ $nombre }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('event_month')" class="mt-2" />
            </div>
            <div class="md:col-span-2">
                <x-input-label :value="'Nombre del evento'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input wire:model.defer="event_name" type="text" class="mt-2 block w-full" />
                <x-input-error :messages="$errors->get('event_name')" class="mt-2" />
            </div>

            {{-- Tipo de participación --}}
            <div class="md:col-span-2">
                <x-input-label :value="'Tipo de participación'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <select wire:model.defer="participation_type"
                    class="mt-2 block w-full rounded-md border-red-200 bg-white text-zinc-900 shadow-sm transition-colors focus:border-red-600 focus:ring-red-600">
                    <option value="Ponente">Ponencia</option>
                    <option value="Asistente">Asistencia</option>
                    <option value="Organizador">Organizador</option>

                </select>
                <x-input-error :messages="$errors->get('participation_type')" class="mt-2" />
                <p class="mt-1 text-xs text-slate-500">Este tipo de participación aplicará a todos los conferencistas
                    del evento.</p>
            </div>

            {{-- Institución destino --}}
            <div>
                <livewire:eventos.institucion-selector target="host" label="Institución destino" />
            </div>

            {{-- Institución origen --}}
            <div>
                <livewire:eventos.institucion-selector target="origin" label="Institución origen" />
            </div>

            <div>
                <x-input-label :value="'Ámbito'"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <select wire:model.defer="event_scope"
                    class="mt-2 block w-full rounded-md border-red-200 bg-white text-zinc-900 shadow-sm transition-colors focus:border-red-600 focus:ring-red-600">
                    <option value="">Sin definir</option>
                    <option value="Nacional">Nacional</option>
                    <option value="Internacional">Internacional</option>
                </select>
                <x-input-error :messages="$errors->get('event_scope')" class="mt-2" />
            </div>
            <div>
                <x-input-label :value="'URL'" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-600" />
                <x-text-input wire:model.defer="event_url" type="url" class="mt-2 block w-full"
                    placeholder="https://" />
                <x-input-error :messages="$errors->get('event_url')" class="mt-2" />
            </div>
        </div>
    </div>

    {{-- Conferencistas --}}
    <livewire:eventos.conferencista-selector wire:model="selectedSpeakers" />

    {{-- Tabla --}}
    <div class="rounded-2xl border border-white/60 bg-white/80 p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-[#2b2323]">Eventos registrados</h3>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#9c1c1c]">
                    {{ $this->events->total() }} registros
                </p>
            </div>
            <div class="w-full sm:w-72">
                <x-text-input wire:model.live="search" type="search" placeholder="Buscar por nombre, institución..."
                    class="w-full" />
            </div>
        </div>

        <div class="mt-6 overflow-x-auto rounded-2xl border border-[#f0dede]">
            <table class="min-w-full divide-y divide-[#f0dede] text-sm">
                <thead class="bg-[#fff7f7] text-left text-xs font-semibold uppercase tracking-[0.2em] text-[#7a1515]">
                    <tr>
                        <th class="px-4 py-3">Año</th>
                        <th class="px-4 py-3">Mes</th>
                        <th class="px-4 py-3">Nombre del evento</th>
                        <th class="px-4 py-3">Ámbito</th>
                        <th class="px-4 py-3">Inst. destino</th>
                        <th class="px-4 py-3">Inst. origen</th>
                        <th class="px-4 py-3">Conferencistas</th>
                        <th class="px-4 py-3">Tipo participación</th>
                        <th class="px-4 py-3">URL</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0dede] bg-white">
                    @php
                        $meses = [
                            1 => 'Enero',
                            2 => 'Febrero',
                            3 => 'Marzo',
                            4 => 'Abril',
                            5 => 'Mayo',
                            6 => 'Junio',
                            7 => 'Julio',
                            8 => 'Agosto',
                            9 => 'Septiembre',
                            10 => 'Octubre',
                            11 => 'Noviembre',
                            12 => 'Diciembre'
                        ];
                    @endphp
                    @forelse ($this->events as $event)
                        @php
                            $conferencistas = $event->researchers->map(
                                fn($r) =>
                                    trim("{$r->name_1} {$r->name_2} {$r->last_name_1} {$r->last_name_2}")
                            )->filter()->implode(' - ');

                            $tiposParticipacion = $event->researcherEvents->pluck('participation_type')
                                ->filter()->unique()->implode(', ');
                        @endphp
                        <tr wire:key="event-{{ $event->event_id }}" class="hover:bg-[#fff7f7]">
                            <td class="px-4 py-3 text-slate-600">{{ $event->event_year ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $meses[$event->event_month] ?? '—' }}</td>
                            <td class="px-4 py-3 font-semibold text-[#2b2323]">{{ $event->event_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $event->event_scope ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $event->hostInstitution?->institution_name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $event->originInstitution?->institution_name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $conferencistas ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $tiposParticipacion ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                @if ($event->event_url)
                                    <a href="{{ $event->event_url }}" target="_blank" class="text-blue-600 hover:underline">Ver
                                        enlace</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="edit({{ $event->event_id }})"
                                        class="rounded-full border border-[#9c1c1c]/30 px-3 py-1 text-xs font-semibold text-[#7a1515] hover:bg-[#f2d3d3]">
                                        Editar
                                    </button>
                                    <button type="button" wire:click="delete({{ $event->event_id }})"
                                        onclick="confirm('¿Seguro que deseas eliminar este evento?') || event.stopImmediatePropagation()"
                                        class="rounded-full border border-[#d77a7a]/40 px-3 py-1 text-xs font-semibold text-[#9c1c1c] hover:bg-[#f9dede]">
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-6 text-center text-sm text-slate-500">
                                No hay eventos registrados todavía.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->events->links() }}
        </div>
    </div>

</div>