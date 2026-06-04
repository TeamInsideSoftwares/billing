import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '192.168.1.169',
        watch: {
            usePolling: true,
            debounce: 1000,
            ignored: [
                '**/storage/framework/views/**',
                '**/node_modules/**',
                '**/storage/**',
                '**/vendor/**',
                '**/.git/**',
            ],
        },

    },
});
