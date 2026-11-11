{{--
    Modal de confirmación global. Reemplaza al confirm() del navegador.

    Uso desde cualquier botón dentro de un componente Livewire:
        x-on:click="$dispatch('confirmar', {
            message: '¿Seguro que deseas eliminar este grupo?',
            confirmLabel: 'Eliminar',            // opcional
            action: () => $wire.delete(id)
        })"
--}}
<div x-data="{ open: false, title: '', message: '', confirmLabel: 'Eliminar', action: null }"
    x-on:confirmar.window="
        title = $event.detail.title ?? 'Confirmar acción';
        message = $event.detail.message ?? '';
        confirmLabel = $event.detail.confirmLabel ?? 'Eliminar';
        action = $event.detail.action ?? null;
        open = true;
    "
    x-on:keydown.escape.window="open = false" x-show="open" x-cloak
    class="fixed inset-0 z-[9998] flex items-center justify-center p-4" role="dialog" aria-modal="true"
    aria-labelledby="confirm-dialog-title">
    <div class="absolute inset-0 bg-siproc-ink/50" x-show="open" x-transition.opacity @click="open = false"></div>

    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" x-show="open" x-trap.noscroll="open"
        x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-siproc-rose text-siproc-red">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <div class="min-w-0">
                <h3 id="confirm-dialog-title" class="section-title" x-text="title"></h3>
                <p class="mt-1 text-sm text-slate-600" x-text="message"></p>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="btn btn-outline" @click="open = false">Cancelar</button>
            <button type="button" class="btn btn-primary" x-text="confirmLabel"
                @click="open = false; if (action) action()"></button>
        </div>
    </div>
</div>
