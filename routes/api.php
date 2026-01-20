<?php

use App\Http\Controllers\Api\GetPaidMembers;
use App\Http\Controllers\Api\Nostr\GetProfile;
use Illuminate\Support\Facades\Route;

Route::get('/nostr/profile/{key}', GetProfile::class);
Route::get('/members/{year}', GetPaidMembers::class);
