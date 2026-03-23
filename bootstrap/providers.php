<?php

use App\Providers\AppServiceProvider;
use App\Providers\NostrAuthServiceProvider;

return [
    AppServiceProvider::class,
    // App\Providers\FolioServiceProvider::class, // Disabled - laravel/folio package removed during Laravel 12 upgrade
    NostrAuthServiceProvider::class,
];
