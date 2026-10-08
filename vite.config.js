import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',   // หน้า welcome (Tailwind)
                'resources/css/site.css',  // CSS ของทั้งเว็บ (layouts/main)
                'resources/js/app.js',
                'resources/js/site.js',    // JS ของทั้งเว็บ (layouts/main)
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
