import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Comic Sans MS"', 'cursive', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'nursery-green': {
                    light: '#A8D8B9',
                    DEFAULT: '#82C3A3',
                    dark: '#5EAE8E',
                },
                'nursery-pastel': {
                    yellow: '#FDFD96',
                    pink: '#FFB7C5',
                    blue: '#87CEEB',
                },
            },
        },
    },

    plugins: [forms],
};
