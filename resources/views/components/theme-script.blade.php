{{--
    Preferencias visuales que se aplican antes de pintar la página, para evitar destellos:

    1. Tema claro u oscuro. Se guarda en localStorage ("siproc-tema": "claro" u "oscuro");
       sin preferencia se sigue la del sistema operativo.
       window.siprocTema.alternar() / .establecer('claro'|'oscuro'|'sistema') / .preferencia()

    2. Ajustes de accesibilidad ("siproc-a11y"), aplicados como clases a11y-* en <html>.
       Los estilos de cada clase están al final de resources/css/app.css.
       window.siprocA11y.leer() / .guardar(estado) / .aplicar(estado) / .predeterminado()
--}}
<script>
    (() => {
        const raiz = document.documentElement;
        const leerLS = (clave) => { try { return localStorage.getItem(clave); } catch (e) { return null; } };
        const guardarLS = (clave, valor) => { try { valor === null ? localStorage.removeItem(clave) : localStorage.setItem(clave, valor); } catch (e) {} };

        // ─── Tema ───────────────────────────────────────────────────────────
        const claveTema = 'siproc-tema';
        const sistemaOscuro = () => window.matchMedia('(prefers-color-scheme: dark)').matches;
        const preferencia = () => leerLS(claveTema) || 'sistema';
        const oscuro = () => preferencia() === 'sistema' ? sistemaOscuro() : preferencia() === 'oscuro';
        // Las pantallas de acceso (login, registro) marcan <body data-tema-fijo="claro">: el logo oficial va sobre blanco.
        const temaFijo = () => document.body?.dataset.temaFijo;
        const aplicarTema = () => raiz.classList.toggle('dark', temaFijo() ? temaFijo() === 'oscuro' : oscuro());
        const avisarTema = () => window.dispatchEvent(new CustomEvent('siproc-tema', { detail: { oscuro: oscuro(), preferencia: preferencia() } }));

        window.siprocTema = {
            esOscuro: oscuro,
            preferencia,
            establecer(valor) {
                guardarLS(claveTema, valor === 'sistema' ? null : valor);
                aplicarTema();
                avisarTema();
            },
            alternar() { this.establecer(oscuro() ? 'claro' : 'oscuro'); },
        };

        // ─── Accesibilidad ──────────────────────────────────────────────────
        const claveA11y = 'siproc-a11y';
        const predeterminado = () => ({
            texto: 0,          // 0 = 100 %, 1 = 112 %, 2 = 125 %, 3 = 137 %, 4 = 150 %
            contraste: false,
            color: 'normal',   // normal | gris | baja | alta
            fuente: false,     // fuente de lectura fácil (Atkinson Hyperlegible)
            espaciado: false,  // espaciado de texto según WCAG 1.4.12
            guia: false,       // guía de lectura que sigue al puntero
            enlaces: false,
            foco: false,       // foco del teclado muy visible
            cursor: false,
            movimiento: false, // reducir animaciones
            imagenes: false,   // ocultar imágenes
        });

        const leer = () => {
            let estado = null;
            try { estado = JSON.parse(leerLS(claveA11y) || 'null'); } catch (e) {}

            // Migra los ajustes del widget anterior ("siproc-accesibilidad"), si existen.
            const viejo = (() => { try { return JSON.parse(leerLS('siproc-accesibilidad') || 'null'); } catch (e) { return null; } })();
            if (! estado && viejo) {
                estado = {
                    texto: ({ 1: 1, 2: 2, 3: 4 })[viejo.texto] ?? 0,
                    contraste: !! viejo.contraste,
                    color: ({ 1: 'baja', 2: 'gris', 3: 'alta' })[viejo.saturacion] ?? 'normal',
                    fuente: !! viejo.dislexia,
                    espaciado: !! (viejo.espaciado || viejo.lineHeight),
                    enlaces: !! viejo.enlaces,
                    imagenes: !! viejo.imagenes,
                };
                guardarLS(claveA11y, JSON.stringify(estado));
                guardarLS('siproc-accesibilidad', null);
            }

            return { ...predeterminado(), ...(estado || {}) };
        };

        const cargarFuente = () => {
            if (document.getElementById('siproc-fuente-legible')) return;
            const enlace = document.createElement('link');
            enlace.id = 'siproc-fuente-legible';
            enlace.rel = 'stylesheet';
            enlace.href = 'https://fonts.bunny.net/css?family=atkinson-hyperlegible:400,700&display=swap';
            document.head.appendChild(enlace);
        };

        const aplicarA11y = (estado) => {
            [...raiz.classList].filter((c) => c.startsWith('a11y-')).forEach((c) => raiz.classList.remove(c));
            if (estado.texto > 0) raiz.classList.add('a11y-texto-' + estado.texto);
            if (estado.color !== 'normal') raiz.classList.add('a11y-color-' + estado.color);
            ['contraste', 'fuente', 'espaciado', 'enlaces', 'foco', 'cursor', 'movimiento', 'imagenes'].forEach((clave) => {
                if (estado[clave]) raiz.classList.add('a11y-' + clave);
            });
            if (estado.fuente) cargarFuente();
        };

        window.siprocA11y = {
            leer,
            predeterminado,
            aplicar: aplicarA11y,
            guardar(estado) {
                guardarLS(claveA11y, JSON.stringify(estado));
                aplicarA11y(estado);
                window.dispatchEvent(new CustomEvent('siproc-a11y', { detail: estado }));
            },
            reducirMovimiento: () => raiz.classList.contains('a11y-movimiento') || window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        };

        const aplicarTodo = () => { aplicarTema(); aplicarA11y(leer()); };
        aplicarTodo();
        // Al navegar con wire:navigate, Livewire puede reemplazar los atributos de <html>.
        document.addEventListener('livewire:navigated', aplicarTodo);
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (preferencia() === 'sistema') { aplicarTema(); avisarTema(); }
        });
    })();
</script>
