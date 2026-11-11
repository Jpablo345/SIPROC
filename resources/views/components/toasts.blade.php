{{--
    Avisos flotantes de toda la aplicación.

    Desde Livewire (PHP):  $this->dispatch('notify', type: 'success', message: 'Grupo creado.');
    Desde Alpine:          $dispatch('notify', { type: 'error', message: '...' })
    Tipos: success, error, info. Los mensajes de session('status') llegan aquí con <x-flash-status />.
    El aviso de permisos insuficientes (evento permission-denied) también se muestra aquí.
--}}
<div x-data="siprocToasts()" x-on:notify.window="add($event.detail)"
    x-on:permission-denied.window="add({ type: 'error', title: 'Acceso denegado', message: $event.detail.message })"
    class="pointer-events-none fixed inset-x-4 top-4 z-[9999] flex flex-col items-end gap-3 sm:left-auto sm:right-6 sm:top-6"
    aria-live="polite">
    <template x-for="toast in items" :key="toast.id">
        <div x-show="toast.visible" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-end="opacity-0"
            :role="toast.type === 'error' ? 'alert' : 'status'"
            class="pointer-events-auto w-full max-w-sm rounded-xl border bg-white p-4 shadow-2xl"
            :class="toast.type === 'error' ? 'border-siproc-red-light/50' : 'border-siproc-line'">
            <div class="flex items-start gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                    :class="{
                        'bg-emerald-100 text-emerald-700': toast.type === 'success',
                        'bg-siproc-rose text-siproc-red': toast.type === 'error',
                        'bg-siproc-sand text-siproc-red-dark': toast.type === 'info',
                    }">
                    <svg x-show="toast.type === 'success'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    <svg x-show="toast.type === 'error'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <svg x-show="toast.type === 'info'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-siproc-ink" x-text="toast.title"></p>
                    <p class="mt-0.5 text-sm text-slate-600" x-text="toast.message"></p>
                </div>
                <button type="button" @click="remove(toast.id)" class="text-slate-400 hover:text-slate-600"
                    aria-label="Cerrar aviso">✕</button>
            </div>
        </div>
    </template>
</div>

<script>
    function siprocToasts() {
        const titles = { success: 'Listo', error: 'No se pudo completar', info: 'Aviso' };

        return {
            items: [],
            add(detail) {
                const type = titles[detail.type] ? detail.type : 'success';
                const id = Date.now() + Math.random();
                this.items.push({ id, type, title: detail.title ?? titles[type], message: detail.message ?? '', visible: true });
                setTimeout(() => this.remove(id), detail.timeout ?? (type === 'error' ? 7000 : 4000));
            },
            remove(id) {
                const toast = this.items.find(t => t.id === id);
                if (! toast) return;
                toast.visible = false;
                setTimeout(() => this.items = this.items.filter(t => t.id !== id), 200);
            },
        };
    }
</script>
