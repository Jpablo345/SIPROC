{{--
    Envía el mensaje de session('status') a los avisos flotantes (<x-toasts />).
    Los mensajes que empiezan con "No se puede" se muestran como error; el resto, como éxito.
    Para forzar el tipo: session()->flash('status_type', 'info').
--}}
@if (session('status'))
    @php
        $message = session('status');
        $type = session('status_type') ?? (str_starts_with($message, 'No se puede') ? 'error' : 'success');
    @endphp
    <div hidden wire:key="flash-{{ \Illuminate\Support\Str::random(8) }}"
        x-data x-init="$dispatch('notify', { type: @js($type), message: @js($message) })"></div>
@endif
