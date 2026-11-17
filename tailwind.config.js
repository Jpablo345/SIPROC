import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    // El modo oscuro se activa con la clase "dark" en <html> (botón del menú lateral).
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/livewire/livewire/src/Features/SupportPagination/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/**/*.php',
    ],

    theme: {
        extend: {
            // Manual de identidad visual UFPSO (M-GC-DMT-002 Rev. C, página 17):
            // Helvetica Neue como fuente principal y Open Sans como complementaria.
            // En pantalla se usa Open Sans; los títulos piden Helvetica Neue si está instalada.
            fontFamily: {
                sans: ['"Open Sans"', ...defaultTheme.fontFamily.sans],
                display: ['"Helvetica Neue"', 'Helvetica', '"Open Sans"', 'Arial', ...defaultTheme.fontFamily.sans],
            },
            // Usar siempre estos nombres en vez de códigos hex sueltos.
            colors: {
                siproc: {
                    red: {
                        DEFAULT: '#D21116', // rojo institucional (Pantone 18-1664 TCX): botones y acentos
                        dark: '#A51F22',    // secundario: hover y texto rojo sobre fondo claro
                        deep: '#801418',    // secundario: menú lateral oscuro y panel del login
                        darkest: '#640611', // secundario más oscuro
                        light: '#D72128',   // secundario: bordes de acciones peligrosas
                    },
                    ink: {
                        DEFAULT: '#111827', // texto principal y títulos
                        dark: '#000000',
                    },
                    cream: '#F4F5F7', // fondo de la aplicación
                    line: '#E5E7EB',  // bordes y divisores
                    rose: '#FDECEC',  // tinte rojo suave: insignias y hover de botones
                    blush: '#F9FAFB', // encabezados de tabla y filas en hover
                    sand: '#FEF3E8',  // tinte naranja suave para avisos secundarios
                },
                // Colores por facultad (página 16). Se usan en gráficas e insignias.
                fac: {
                    ing: { DEFAULT: '#165082', 500: '#0B74BB', 400: '#518AC8', 300: '#80ADDC', 200: '#1DAFE5' },
                    agr: { DEFAULT: '#44B86C', dark: '#3A6E4F' },
                    adm: { DEFAULT: '#62538A', light: '#7161AA' },
                    edu: { DEFAULT: '#F69144', dark: '#E56B39' },
                },
            },
        },
    },

    plugins: [forms],
};
