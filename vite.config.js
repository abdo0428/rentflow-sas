import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { readFileSync, writeFileSync } from 'node:fs';
import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        {
            name: 'rentflow-pwa',
            apply: 'build',
            closeBundle() {
                execFileSync('php', ['artisan', 'pwa:assets', '--no-interaction'], { stdio: 'inherit', windowsHide: true });
                const manifest = JSON.parse(readFileSync('public/build/manifest.json', 'utf8'));
                const assets = new Set(['/offline.html', '/manifest.json', '/icons/icon-192.png', '/icons/icon-512.png']);
                for (const entry of Object.values(manifest)) {
                    for (const file of [entry.file, ...(entry.css || []), ...(entry.assets || [])]) {
                        if (file && /^assets\/[a-zA-Z0-9._-]+\.(css|js|woff2?|png|svg)$/.test(file)) assets.add('/build/' + file);
                    }
                }
                const paths = [...assets].sort();
                const source = readFileSync('resources/js/service-worker.js', 'utf8');
                const checksum = createHash('sha256').update(source);
                for (const path of paths) checksum.update(path).update(readFileSync('public' + path));
                const hash = checksum.digest('hex').slice(0, 16);
                writeFileSync('public/sw.js', source.replace('__CACHE_VERSION__', hash).replace('__STATIC_ASSETS__', JSON.stringify(paths)));
            },
        },
    ],
});
