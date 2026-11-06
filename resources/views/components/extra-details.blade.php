{{-- Campos dinámicos de una publicación (requiere type.fields cargado). --}}
@props(['publication'])

@php($details = $publication->extra_details)

@if ($details === [])
    —
@else
    <dl {{ $attributes->merge(['class' => 'space-y-0.5 whitespace-normal text-xs']) }}>
        @foreach ($details as $detail)
            <div>
                <dt class="inline font-semibold text-slate-700">{{ $detail['label'] }}:</dt>
                <dd class="inline text-slate-600">{{ $detail['value'] }}</dd>
            </div>
        @endforeach
    </dl>
@endif
