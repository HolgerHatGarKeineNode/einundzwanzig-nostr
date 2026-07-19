import {defineConfig} from 'vite';
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // Chat-Insel: bewusst ein eigener Entry. Der Import von
                // @einundzwanzig/group hat Seiteneffekte (welshman-Singletons,
                // localStorage, IndexedDB) und darf nicht auf jeder Vereinsseite
                // laufen — nur dort, wo ein Antragsraum gerendert wird.
                'resources/js/group-chat.js',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            // Die Session-API des Packages liegt nicht in dessen exports-Map und
            // waere per Deep-Import blockiert. Wir brauchen genau eine Funktion
            // daraus (loginWithExtension), um die welshman-Session mit dem
            // bereits per NIP-07 eingeloggten Vereinsnutzer zu fuellen — ohne den
            // NIP-98-Handoff und den Redirect, die die nostrAuth-Komponente des
            // Packages zwingend mitbringt.
            //
            // Bewusst als Alias hier statt als Aenderung an der exports-Map des
            // Packages: einundzwanzig-group bleibt dadurch unberuehrt. Beim
            // Bundling greift der Alias vor der exports-Aufloesung.
            '@einundzwanzig/group/session': '/node_modules/@einundzwanzig/group/js/session.ts',
        },
    },
    build: {
        rollupOptions: {
            output: {
                // welshman ist gross (~950 KB) und aendert sich fast nie — eigener,
                // cache-stabiler Chunk, damit ein gewoehnliches Deploy nicht das
                // ganze SDK neu ausliefert.
                //
                // nostr-tools gehoert hier bewusst NICHT hinein, obwohl es im
                // Vorbild-Repo mit drin ist: Der Verein nutzt nostr-tools bereits
                // fuer Login und Zaps (resources/js/nostrLogin.js, nostrZap.js).
                // Gruppiert man beides, zieht app.js den welshman-Chunk mit — und
                // jede Vereinsseite laedt das komplette Chat-SDK. Am gebauten
                // Bundle nachgemessen, nicht vermutet.
                manualChunks(id) {
                    // nostr-tools zuerst und in einen EIGENEN Chunk: Es wird von
                    // beiden Seiten gebraucht (Verein: Login/Zaps, Insel: ueber
                    // welshman). Ohne diese Regel zieht Rollup es als geteilten
                    // Code in den welshman-Chunk — und app.js importiert damit
                    // das komplette Chat-SDK auf jeder Seite.
                    if (id.includes('/node_modules/nostr-tools/')) {
                        return 'nostr-tools';
                    }
                    if (id.includes('/@welshman/')) {
                        return 'welshman';
                    }
                },
            },
        },
    },
    server: {
        cors: true,
        watch: {
            ignored: [
                '**/storage/**',
                '**/bootstrap/cache/**',
                '**/.idea/**',
                '**/.junie/**',
                '**/.fleet/**',
                '**/.vscode/**',
            ],
        },
    },
});
