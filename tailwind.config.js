import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/livewire/livewire/src/Features/SupportPagination/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Space Grotesk"', ...defaultTheme.fontFamily.sans],
            },
            // Paleta institucional. Usar siempre estos nombres en vez de códigos hex sueltos.
            colors: {
                siproc: {
                    red: {
                        DEFAULT: '#9c1c1c', // color principal: botones, enlaces, menú lateral
                        dark: '#7a1515',    // hover y texto sobre fondos claros
                        light: '#d77a7a',   // bordes de acciones peligrosas
                    },
                    ink: {
                        DEFAULT: '#2b2323', // texto principal y títulos
                        dark: '#1f1a1a',
                    },
                    cream: '#f6efe9', // fondo de la aplicación
                    line: '#f0dede',  // bordes y divisores
                    rose: '#f2d3d3',  // insignias y hover suave
                    blush: '#fff7f7', // fondo de encabezados de tabla y filas en hover
                    sand: '#f7e2d2',  // avisos y acentos secundarios
                },
            },
        },
    },

    plugins: [forms],
};
