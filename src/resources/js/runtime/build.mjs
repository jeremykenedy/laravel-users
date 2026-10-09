import { fileURLToPath } from 'node:url';
import { readFile } from 'node:fs/promises';
import { build } from 'vite';
import vue from '@vitejs/plugin-vue';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import actions from './icon-actions.json' with { type: 'json' };

const directory = fileURLToPath(new URL('.', import.meta.url));
// The icon source is a fixed package asset, never a caller-supplied path.
// eslint-disable-next-line security/detect-non-literal-fs-filename
const iconSource = await readFile(new URL('../../views/partials/icon.blade.php', import.meta.url), 'utf8');
const iconNames = new Set(Object.values(actions));
const icons = new Map();
for (const [, name, markup] of iconSource.matchAll(/@case\('([^']+)'\)([\s\S]*?)@break/g)) {
    if (!iconNames.has(name)) continue;
    const shapes = [];
    for (const [, tag, source] of markup.matchAll(/<(path|circle|rect)\b([^>]*)\/>/g)) {
        const attributes = new Map();
        for (const [, name, value] of source.matchAll(/\b(d|cx|cy|r|x|y|width|height|rx|fill)="([^"]*)"/g)) attributes.set(name, value);
        shapes.push({ tag, attributes: Object.fromEntries(attributes) });
    }
    icons.set(name, shapes);
}
const runtimes = process.argv.slice(2);
for (const runtime of runtimes.length ? runtimes : ['livewire', 'vue', 'react', 'svelte']) {
    if (!['livewire', 'vue', 'react', 'svelte'].includes(runtime)) throw new Error('Unknown native runtime.');
    await build({
        configFile: false,
        root: directory,
        plugins: runtime === 'vue' ? [vue()] : runtime === 'svelte' ? [svelte({ configFile: false })] : [],
        define: { 'process.env.NODE_ENV': JSON.stringify('production'), __LARAVEL_USERS_ICONS__: JSON.stringify(Object.fromEntries(icons)) },
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
