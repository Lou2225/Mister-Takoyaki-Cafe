import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
            ],
            refresh: [
                'resources/views/**',
                'app/Http/Livewire/**',
                'app/View/Components/**',
                'routes/**',
                'lang/**'
            ],
        }),
    ],
    server: {
        host: 'localhost',
    },
});
