<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

Route::redirect('/', '/association/profile');

Route::get('dl/{media}', function (Media $media, Request $request) {
    return response()->download($media->getPath(), $media->name);
})
    ->name('dl')
    ->middleware('signed');

Route::post('logout', function () {
    \App\Support\NostrAuth::logout();
    Session::flush();
    return redirect('/');
})->name('logout');
