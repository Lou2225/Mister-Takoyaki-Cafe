const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'sans-serif'],
            },
        },
    },

    safelist: [
        {
            pattern: /^(bg|text|ring|from|via|to)-(indigo|rose|emerald|gray|slate)-(50|100|200|300|400|500|600|700|800|900|950)$/,
            variants: ['hover', 'focus'],
        },
        'ring-white/10',
        'ring-rose-500/20',
        'ring-emerald-500/20'
    ],

    plugins: [require('@tailwindcss/forms')],
};
