<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

Route::redirect('/', '/association/profile');

Route::get('dl/{media}', function (Media $media, Request $request) {
    return response()->download($media->getPath(), $media->name);
})
    ->name('dl')
    ->middleware('signed');
