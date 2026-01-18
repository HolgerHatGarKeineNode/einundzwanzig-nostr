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

// Association Routes
Route::livewire('/association/profile', 'association.profile')->name('association.profile');

Route::livewire('/association/election', 'association.election.index')->name('association.elections');
Route::livewire('/association/election/{election:year}', 'association.election.show')->name('association.election');
Route::livewire('/association/election/admin/{election:year}', 'association.election.admin')->name('association.election.admin');

Route::livewire('/association/members/admin', 'association.members.admin')->name('association.members.admin');

Route::livewire('/association/news', 'association.news')->name('association.news');

Route::livewire('/association/project-support', 'association.project-support.index')->name('association.projectSupport');
Route::livewire('/association/project-support/create', 'association.project-support.form.create')->name('association.projectSupport.create');
Route::livewire('/association/project-support/{projectProposal:slug}', 'association.project-support.show')->name('association.projectSupport.item');
Route::livewire('/association/project-support/edit/{projectProposal:slug}', 'association.project-support.form.edit')->name('association.projectSupport.edit');

// Other pages
Route::livewire('/changelog', 'changelog')->name('changelog');
Route::livewire('/welcome', 'welcome')->name('welcome');
