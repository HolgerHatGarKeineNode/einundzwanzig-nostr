<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache store that will be used by the
    | framework. This connection is utilized if another isn't explicitly
    | specified when running a cache operation inside the application.
    |
    */

    'default' => env('CACHE_STORE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "array", "database", "file", "memcached",
    |                    "redis", "dynamodb", "octane", "null"
    |
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE'),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, and DynamoDB cache
    | stores, there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),

    /*
    |--------------------------------------------------------------------------
    | Serializable Classes
    |--------------------------------------------------------------------------
    |
    | This option allows you to specify which classes may be unserialized from
    | the cache to help prevent PHP deserialization gadget chain attacks.
    | Set to false to allow no classes to be unserialized from cache.
    |
    | `stdClass` ist bewusst erlaubt — und NUR die. Der Profil-Cache
    | (Einundzwanzig\Group\Nostr\ProfileCache, gelesen von GET /nostr/profiles)
    | legt rohe kind-0-Events als stdClass ab. Mit `false` kam beim Lesen aus dem
    | Datenbank-Store ein __PHP_Incomplete_Class zurück: kein Cache-Miss, sondern
    | ein Treffer voller Attrappen — die Events wären als `{}` beim Browser
    | gelandet, dort an der Signaturprüfung gescheitert, und der Cache hätte den
    | Schaden 24 Stunden lang festgehalten. In Tests fiel das nie auf, weil der
    | Array-Store gar nicht serialisiert.
    |
    | Die Ausnahme ist eng: stdClass hat keine Magic-Methoden (__wakeup,
    | __destruct, __toString) und taugt damit nicht als Glied einer
    | Deserialisierungs-Gadget-Chain — genau davor schützt die Liste. Jede
    | weitere Klasse hier gehört einzeln begründet.
    |
    | WICHTIG, damit hier niemand etwas Falsches annimmt: Die Einstellung gilt
    | PROJEKTWEIT für jeden Cache-Store, nicht nur für den Profil-Cache und nicht
    | nur für `nostr:profile:*`-Schlüssel. Illuminate\Cache\CacheManager::
    | getSerializableClasses() liest ausschließlich diesen globalen Wert und
    | ignoriert die Store-Konfiguration. Wer hier eine Klasse ergänzt, erlaubt
    | sie überall.
    |
    | Der Umweg über die Konfiguration ist nötig, weil ProfileCache im
    | Vendor-Package cached: Der stdClass-Typ entsteht dort vollständig
    | (RelayResponseEvent), und es gibt keinen Haken, an dem wir vor dem
    | Schreiben in ein Array umwandeln könnten. Sobald das Package die Events
    | selbst als Array ablegt, kann dieser Eintrag wieder auf `false`.
    |
    */

    'serializable_classes' => [stdClass::class],

];
