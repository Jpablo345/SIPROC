{{--
    Gráfica de Chart.js.
    config: ['type' => 'bar'|'doughnut', 'labels' => [...], 'datasets' => [['data' => [...], 'backgroundColor' => ...]],
             'horizontal' => bool, 'legend' => bool]
    label:  descripción breve para lectores de pantalla.
    La clave wire:key cambia con los datos, así Livewire reemplaza la gráfica cuando cambian los filtros.
--}}
@props(['config', 'label' => 'Gráfica', 'height' => 'h-64'])

<div {{ $attributes->merge(['class' => "relative $height"]) }} wire:key="chart-{{ md5(json_encode($config)) }}"
    x-data="siprocChart(@js($config))" role="img" aria-label="{{ $label }}">
    <canvas x-ref="canvas"></canvas>
</div>
