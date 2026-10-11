import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        host: '127.0.0.1',
        port: 5173,
        proxy: {
            '/api': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
            '/_boost': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
            '/altcha-challenge': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
            '/altcha-verify': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
            '/metamask': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
        },
    },
});
