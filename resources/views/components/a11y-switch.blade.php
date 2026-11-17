{{-- Interruptor del panel de accesibilidad. Va dentro de un <label>, que le da el nombre accesible. --}}
@props(['clave'])

<button type="button" role="switch" :aria-checked="estado.{{ $clave }} ? 'true' : 'false'" @click.prevent="alternar('{{ $clave }}')"
    class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 items-center rounded-full transition focus:outline-none focus-visible:ring-2 focus-visible:ring-siproc-red focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
    :class="estado.{{ $clave }} ? 'bg-siproc-red' : 'bg-gray-300 dark:bg-gray-600'">
    <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition"
        :class="estado.{{ $clave }} ? 'translate-x-5' : 'translate-x-0.5'"></span>
</button>
