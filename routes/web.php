<?php

use App\Livewire\Association\Election\Admin as ElectionAdmin;
use App\Livewire\Association\Election\Index as ElectionIndex;
use App\Livewire\Association\Election\Show as ElectionShow;
use App\Livewire\Association\Members\Admin as MembersAdmin;
use App\Livewire\Association\News\Index as NewsIndex;
use App\Livewire\Association\Profile;
use App\Livewire\Association\ProjectSupport\Form\Create as ProjectSupportCreate;
use App\Livewire\Association\ProjectSupport\Form\Edit as ProjectSupportEdit;
use App\Livewire\Association\ProjectSupport\Index as ProjectSupportIndex;
use App\Livewire\Association\ProjectSupport\Show as ProjectSupportShow;
use App\Livewire\Changelog;
use App\Livewire\EinundzwanzigFeed\Index as EinundzwanzigFeedIndex;
use App\Livewire\Meetups\Grid as MeetupsGrid;
use App\Livewire\Meetups\Mockup as MeetupsMockup;
use App\Livewire\Meetups\Table as MeetupsTable;
use App\Livewire\Meetups\Worldmap as MeetupsWorldmap;
use App\Livewire\Welcome;
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
Route::livewire('/association/profile', Profile::name('association.profile'));

Route::livewire('/association/election', ElectionIndex::class)->name('association.elections');
Route::livewire('/association/election/{election:year}', ElectionShow::class)->name('association.election');
Route::livewire('/association/election/admin/{election:year}', ElectionAdmin::class)->name('association.election.admin');

Route::livewire('/association/members/admin', MembersAdmin::class)->name('association.members.admin');

Route::livewire('/association/news', NewsIndex::class)->name('association.news');

Route::livewire('/association/project-support', ProjectSupportIndex::class)->name('association.projectSupport');
Route::livewire('/association/project-support/create', ProjectSupportCreate::class)->name('association.projectSupport.create');
Route::livewire('/association/project-support/{projectProposal:slug}', ProjectSupportShow::class)->name('association.projectSupport.item');
Route::livewire('/association/project-support/edit/{projectProposal:slug}', ProjectSupportEdit::class)->name('association.projectSupport.edit');

// Einundzwanzig Feed
Route::livewire('/einundzwanzig-feed', EinundzwanzigFeedIndex::class)->name('einundzwanzig-feed');

// Meetups
Route::livewire('/meetups/grid', MeetupsGrid::class)->name('meetups.grid');
Route::livewire('/meetups/mockup', MeetupsMockup::class)->name('meetups.mockup');
Route::livewire('/meetups/table', MeetupsTable::class)->name('meetups.table');
Route::livewire('/meetups/worldmap', MeetupsWorldmap::class)->name('meetups.worldmap');

// Other pages
Route::livewire('/changelog', Changelog::class)->name('changelog');
Route::livewire('/welcome', Welcome::class)->name('welcome');
