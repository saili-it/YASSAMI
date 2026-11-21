import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Polices téléchargées au build puis servies localement (@fonts dans le layout).
            fonts: [
                bunny('Barlow Condensed', {
                    alias: 'barlow',
                    weights: [600, 700, 800],
                    styles: ['normal', 'italic'],
                    preload: [{ weight: 800 }],
                }),
                bunny('Inter', {
                    alias: 'inter',
                    weights: [400, 500, 600, 700],
                    preload: [{ weight: 400 }, { weight: 600 }],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5180,
        strictPort: true,
        // Sinon laravel-vite-plugin écrit « http://0.0.0.0:5180 » dans public/hot.
        hmr: { host: 'localhost' },
        watch: {
            ignored: ['**/storage/framework/views/**'],
            // Les volumes Docker n'émettent pas d'événements inotify.
            usePolling: !!process.env.DOCKER,
        },
    },
});
