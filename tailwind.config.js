import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Segoe UI', 'Tahoma', 'Verdana', 'Arial', ...defaultTheme.fontFamily.sans],
                mono: ['Consolas', 'Lucida Console', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                nav: {
                    DEFAULT: '#1d3a6e',
                    d: '#162d57',
                    l: '#2c4d85',
                    text: '#c8d4e8',
                },
                erp: {
                    blue: { DEFAULT: '#3a72c4', d: '#2a5ca0', l: '#5a8cd4', soft: '#e8f0fc' },
                    bg: '#f0f3f5',
                    surface: { DEFAULT: '#ffffff', 2: '#f7f8fa', 3: '#eef1f4' },
                    bdr: { DEFAULT: '#c5cdd5', l: '#dde2e8', d: '#9aa5b2' },
                    ink: { DEFAULT: '#1a2230', 2: '#3d4a5c', 3: '#5a6878', 4: '#7a8a9c' },
                    green: { DEFAULT: '#3a7a3a', soft: '#e8f4e0' },
                    red: { DEFAULT: '#b03030', soft: '#fce4e4' },
                    orange: { DEFAULT: '#c87020', soft: '#fdefd8' },
                    yellow: { DEFAULT: '#a08020', soft: '#fef8d8' },
                },
            },
            borderRadius: {
                sm: '2px',
                md: '3px',
            },
        },
    },
    plugins: [forms],
};
