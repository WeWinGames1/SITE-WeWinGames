import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import fs from 'fs';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts', 'resources/css/app.css'],
            ssr: 'resources/js/ssr.ts',
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
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
        dedupe: ['qs', '@inertiajs/core']
    },
    server: {
        host: 'localhost',
        port: 5173,
        strictPort: false,
        hmr: {
            host: 'localhost'
        }
    },
    build: {
        commonjsOptions: {
            include: [/node_modules/],
            transformMixedEsModules: true
        },
        rollupOptions: {
            output: {
                manualChunks: {
                    'qs': ['qs']
                }
            },
            maxParallelFileOps: 2
        },
        // Reduce chunk size to process fewer files at once
        chunkSizeWarningLimit: 1000,
        // Disable source maps to reduce file operations
        sourcemap: false
    },
    optimizeDeps: {
        include: ['qs', '@inertiajs/core', '@inertiajs/vue3'],
        force: true,
        esbuildOptions: {
            // Limit concurrent file operations
            logLevel: 'error',
            loader: {
                '.js': 'jsx',
                '.ts': 'tsx'
            }
        }
    },
    esbuild: {
        // Reduce memory usage
        logLevel: 'error',
        drop: ['console', 'debugger']
    }
});
