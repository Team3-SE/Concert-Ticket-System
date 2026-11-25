import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/user.css',        // CSS ฝั่ง User (layouts/user)
                'resources/css/backoffice.css',  // CSS หลังบ้าน Organizer/Admin/Staff (layouts/backoffice, layouts/staff)
                'resources/js/user.js',          // JS ฝั่ง User (layouts/user)
                'resources/js/backoffice.js',    // JS หลังบ้าน (layouts/backoffice, layouts/staff)
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

