import { defineConfig } from 'vite';

export default defineConfig({
    publicDir: false,
    build: {
        outDir: 'public/vendor/server-console',
        emptyOutDir: true,
        cssCodeSplit: false,
        lib: {
            entry: 'resources/js/server-console.js',
            formats: ['es'],
            fileName: () => 'server-console.js',
            cssFileName: 'server-console',
        },
        rollupOptions: {
            output: {
                assetFileNames: (asset) => asset.name?.endsWith('.css') ? 'server-console.css' : '[name][extname]',
            },
        },
    },
});
