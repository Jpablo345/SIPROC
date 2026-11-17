// Gráficas del dashboard y de las fichas (Chart.js).
// Uso en Blade: <x-chart :config="[...]" />, que llama a siprocChart(config) desde Alpine.
import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';

Chart.register(ArcElement, BarController, BarElement, CategoryScale, DoughnutController, Legend, LinearScale, Tooltip);

const tema = () => {
    const oscuro = document.documentElement.classList.contains('dark');
    return {
        texto: oscuro ? '#9ca3af' : '#6b7280',
        rejilla: oscuro ? 'rgba(255,255,255,0.08)' : '#f1f1f1',
        borde: oscuro ? '#111827' : '#ffffff',
    };
};

const opciones = (config) => {
    const colores = tema();
    const esDona = config.type === 'doughnut';
    const ejes = esDona ? {} : {
        x: {
            beginAtZero: true,
            grid: { display: !!config.horizontal, color: colores.rejilla },
            ticks: { color: colores.texto, precision: 0 },
            border: { display: false },
        },
        y: {
            beginAtZero: true,
            grid: { display: !config.horizontal, color: colores.rejilla },
            ticks: { color: colores.texto, precision: 0 },
            border: { display: false },
        },
    };

    return {
        indexAxis: config.horizontal ? 'y' : 'x',
        maintainAspectRatio: false,
        cutout: esDona ? '64%' : undefined,
        // Sin animación si el usuario pidió reducir movimiento (panel de accesibilidad o sistema operativo).
        animation: { duration: window.siprocA11y?.reducirMovimiento() ? 0 : 400 },
        plugins: {
            legend: {
                display: !!config.legend,
                position: 'bottom',
                labels: { color: colores.texto, boxWidth: 10, boxHeight: 10, padding: 12 },
            },
            tooltip: { padding: 10, cornerRadius: 8 },
        },
        scales: ejes,
    };
};

// La instancia de Chart.js se guarda fuera del estado de Alpine: si Alpine la vuelve reactiva,
// sus referencias internas generan un ciclo infinito ("Maximum call stack size exceeded").
window.siprocChart = (config) => {
    let chart = null;
    let alCambiarTema = null;

    const crear = (canvas) => {
        chart = new Chart(canvas, {
            type: config.type,
            data: {
                labels: config.labels,
                datasets: config.datasets.map((dataset) => ({
                    borderRadius: config.type === 'bar' ? 6 : 0,
                    maxBarThickness: config.horizontal ? 22 : 42,
                    borderWidth: config.type === 'doughnut' ? 2 : 0,
                    borderColor: tema().borde,
                    ...dataset,
                })),
            },
            options: opciones(config),
        });
    };

    return {
        init() {
            Chart.defaults.font.family = '"Open Sans", system-ui, sans-serif';
            const canvas = this.$refs.canvas;
            crear(canvas);

            // Al cambiar entre modo claro y oscuro se vuelve a dibujar con los colores del tema:
            // Chart.js guarda en caché los colores ya resueltos y no los actualiza en el sitio.
            alCambiarTema = () => {
                chart?.destroy();
                crear(canvas);
            };
            window.addEventListener('siproc-tema', alCambiarTema);
        },

        destroy() {
            window.removeEventListener('siproc-tema', alCambiarTema);
            chart?.destroy();
            chart = null;
        },
    };
};
