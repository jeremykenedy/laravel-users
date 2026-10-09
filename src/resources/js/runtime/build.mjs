import { fileURLToPath } from 'node:url';
import { build } from 'vite';
import vue from '@vitejs/plugin-vue';
import { svelte } from '@sveltejs/vite-plugin-svelte';

const directory = fileURLToPath(new URL('.', import.meta.url));
const runtimes = process.argv.slice(2);
for (const runtime of runtimes.length ? runtimes : ['livewire', 'vue', 'react', 'svelte']) {
    if (!['livewire', 'vue', 'react', 'svelte'].includes(runtime)) throw new Error('Unknown native runtime.');
    await build({
        configFile: false,
        root: directory,
        plugins: runtime === 'vue' ? [vue()] : runtime === 'svelte' ? [svelte({ configFile: false })] : [],
        define: { 'process.env.NODE_ENV': JSON.stringify('production') },
        build: {
            outDir: fileURLToPath(new URL('../../assets/', import.meta.url)),
            emptyOutDir: false,
            target: 'es2022',
            minify: 'terser',
            terserOptions: { format: { quote_style: 1 } },
            license: { fileName: `runtime-${runtime}.licenses.json` },
            lib: {
                entry: fileURLToPath(new URL(runtime === 'livewire' ? './livewire.js' : `./${runtime}/index.js`, import.meta.url)),
                name: 'LaravelUsers' + runtime[0].toUpperCase() + runtime.slice(1),
                formats: ['iife'],
                fileName: () => `runtime-${runtime}.js`,
            },
        },
    });
}
