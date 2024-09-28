<?php

use Illuminate\Support\Facades\Route;

Route::get('/nostr/profile/{key}', \App\Http\Controllers\Api\Nostr\GetProfile::class);
