import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            outDir: 'public',
            buildBase: '/',
            registerType: 'autoUpdate',
            injectRegister: 'script',
            manifest: {
                name: 'SecondBrain',
                short_name: 'SecondBrain',
                description: 'Task and Pomodoro Manager',
                theme_color: '#0B0D14',
                background_color: '#0B0D14',
                display: 'standalone',
                icons: [
                    {
                        src: 'icon-192x192.png',
                        sizes: '192x192',
                        type: 'image/png'
                    },
                    {
                        src: 'icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png'
                    }
                ]
            }
        })
    ],
});
