<?php

use App\Livewire\Forms\NotificationForm;
use Livewire\Volt\Component;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Key\Key;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;
use WireUi\Actions\Notification as WireNotification;

use function Laravel\Folio\{middleware, name};
use function Livewire\Volt\{state, mount, on, computed, form, usesFileUploads};

name('news');

form(NotificationForm::class);

usesFileUploads();

state([
    'file',
    'news' => fn()
        => \App\Models\Notification::query()
        ->orderBy('created_at', 'desc')
        ->get(),
    'isAllowed' => false,
    'canEdit' => false,
    'currentPubkey' => null,
    'currentPleb' => null,
]);

mount(function () {
    if (\App\Support\NostrAuth::check()) {
        $this->currentPubkey = \App\Support\NostrAuth::pubkey();
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', $this->currentPubkey)->first();
        if (in_array($this->currentPleb->npub, config('einundzwanzig.config.current_board'), true)) {
            $this->canEdit = true;
        }
        $this->isAllowed = true;
    }
});

on([
    'nostrLoggedIn' => function ($pubkey) {
        $this->currentPubkey = $pubkey;
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', $pubkey)->first();
        if (in_array($this->currentPleb->npub, config('einundzwanzig.config.current_board'), true)) {
            $this->canEdit = true;
        }
        $this->isAllowed = true;
    },
    'nostrLoggedOut' => function () {
        $this->isAllowed = false;
        $this->currentPubkey = null;
        $this->currentPleb = null;
    },
]);

$save = function () {
    $this->form->validate();

    $this->validate([
        'file' => 'required|file|mimes:pdf|max:1024',
    ]);

    $notification = \App\Models\Notification::query()
        ->orderBy('created_at', 'desc')
        ->create([
            'einundzwanzig_pleb_id' => $this->currentPleb->id,
            'category' => $this->form->category,
            'name' => $this->form->name,
            'description' => $this->form->description,
        ]);

    $notification
        ->addMedia($this->file->getRealPath())
        ->usingName($this->file->getClientOriginalName())
        ->toMediaCollection('pdf');

    $this->form->reset();
    $this->file = null;

    $this->news = \App\Models\Notification::query()
        ->orderBy('created_at', 'desc')
        ->get();
};

$delete = function ($id) {
    $notification = new WireNotification($this);
    $notification->confirm([
        'title' => 'Post löschen',
        'message' => 'Bist du sicher, dass du diesen Post löschen möchtest?',
        'accept' => [
            'label' => 'Ja, löschen',
            'method' => 'deleteNow',
            'params' => $id,
        ],
    ]);
};

$deleteNow = function ($id) {
    $notification = \App\Models\Notification::query()->find($id);
    $notification->delete();
    $this->news = \App\Models\Notification::query()
        ->orderBy('created_at', 'desc')
        ->get();
};

?>

<x-layouts.app
    :seo="new \RalphJSmit\Laravel\SEO\Support\SEOData(title: 'News', description: 'Die News des Vereins.')"
>
    @volt
    <div>
        @if($isAllowed)
            <div class="px-4 sm:px-6 lg:px-8 py-8 md:py-0 w-full max-w-9xl mx-auto">

                <div class="xl:flex">

                    <!-- Left + Middle content -->
                    <div class="md:flex flex-1">

                        <!-- Left content -->
                        <div class="w-full md:w-60 mb-8 md:mb-0">
                            <div
                                class="md:sticky md:top-16 md:h-[calc(100dvh-64px)] md:overflow-x-hidden md:overflow-y-auto no-scrollbar">
                                <div class="md:py-8">

                                    <div class="flex justify-between items-center md:block">

                                        <!-- Title -->
                                        <header class="mb-6">
                                            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">
                                                News</h1>
                                        </header>

                                    </div>


                                    <!-- Links -->
                                    <div
                                        class="flex flex-nowrap overflow-x-scroll no-scrollbar md:block md:overflow-auto px-4 md:space-y-3 -mx-4">
                                        <!-- Group 1 -->
                                        <div>
                                            <div
                                                class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase mb-3 md:sr-only">
                                                Menu
                                            </div>
                                            <ul class="flex flex-nowrap md:block mr-3 md:mr-0">
                                                @foreach(\App\Enums\NewsCategory::selectOptions() as $category)
                                                    <li class="mr-0.5 md:mr-0 md:mb-0.5"
                                                        wire:key="category_{{ $category['value'] }}">
                                                        <div
                                                            class="flex items-center px-2.5 py-2 rounded-lg whitespace-nowrap bg-white dark:bg-gray-800">
                                                            <i class="fa-sharp-duotone fa-solid fa-{{ $category['icon'] }} shrink-0 fill-current text-amber-500 mr-2"></i>
                                                            <span
                                                                class="text-sm font-medium text-amber-500">{{ $category['label'] }}</span>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Middle content -->
                        <div class="flex-1 md:ml-8 xl:mx-4 2xl:mx-8">
                            <div class="md:py-8">

                                <div class="space-y-2">
                                    @forelse($news as $post)
                                        <article wire:key="post_{{ $post->id }}"
                                                 class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-5">
                                            <div class="flex flex-start space-x-4">
                                                <!-- Avatar -->
                                                <div class="shrink-0 mt-1.5">
                                                    <img class="w-8 h-8 rounded-full"
                                                         src="{{ $post->einundzwanzigPleb->profile->picture }}"
                                                         width="32" height="32"
                                                         alt="{{ $post->einundzwanzigPleb->profile->name }}">
                                                </div>
                                                <!-- Content -->
                                                <div class="grow">
                                                    <!-- Title -->
                                                    <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-2">
                                                        {{ $post->name }}
                                                    </h2>
                                                    <p class="mb-6">
                                                        {{ $post->description }}
                                                    </p>
                                                    <!-- Footer -->
                                                    <footer class="flex flex-wrap text-sm">
                                                        <div
                                                            class="flex items-center after:block after:content-['·'] last:after:content-[''] after:text-sm after:text-gray-400 dark:after:text-gray-600 after:px-2">
                                                            <div
                                                                class="font-medium text-amber-500 hover:text-amber-600 dark:hover:text-amber-400">
                                                                <div class="flex items-center">
                                                                    <svg class="mr-2 fill-current" width="16"
                                                                         height="16"
                                                                         xmlns="http://www.w3.org/2000/svg">
                                                                        <path
                                                                            d="M15.686 5.708 10.291.313c-.4-.4-.999-.4-1.399 0s-.4 1 0 1.399l.6.6-6.794 3.696-1-1C1.299 4.61.7 4.61.3 5.009c-.4.4-.4 1 0 1.4l1.498 1.498 2.398 2.398L.6 14.001 2 15.4l3.696-3.697L9.692 15.7c.5.5 1.199.2 1.398 0 .4-.4.4-1 0-1.4l-.999-.998 3.697-6.695.6.6c.599.6 1.199.2 1.398 0 .3-.4.3-1.1-.1-1.499Zm-7.193 6.095L4.196 7.507l6.695-3.697 1.298 1.299-3.696 6.694Z"></path>
                                                                    </svg>
                                                                    {{ $post->einundzwanzigPleb->profile->name }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div
                                                            class="flex items-center after:block after:content-['·'] last:after:content-[''] after:text-sm after:text-gray-400 dark:after:text-gray-600 after:px-2">
                                                            <span
                                                                class="text-gray-500">{{ $post->created_at->format('d.m.Y') }}</span>
                                                        </div>
                                                    </footer>
                                                </div>
                                            </div>
                                            <div class="mt-2 flex justify-end w-full space-x-2">
                                                <x-button
                                                    xs
                                                    target="_blank"
                                                    :href="url()->temporarySignedRoute('dl', now()->addMinutes(30), ['media' => $post->getFirstMedia('pdf')])"
                                                    label="Öffnen"
                                                    primary icon="cloud-arrow-down"/>
                                                @if($canEdit)
                                                    <x-button
                                                        xs
                                                        wire:click="delete({{ $post->id }})"
                                                        label="Löschen"
                                                        negative icon="trash"/>
                                                @endif
                                            </div>
                                        </article>
                                    @empty
                                        <article class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-5">
                                            <p>Keine News vorhanden.</p>
                                        </article>
                                    @endforelse
                                </div>

                            </div>
                        </div>

                    </div>

                    <!-- Right content -->
                    <div class="w-full mt-8 sm:mt-0 xl:w-72">
                        <div
                            class="lg:sticky lg:top-16 lg:h-[calc(100dvh-64px)] lg:overflow-x-hidden lg:overflow-y-auto no-scrollbar">
                            <div class="md:py-8">

                                <!-- Blocks -->
                                <div class="space-y-4">

                                    @if($canEdit)
                                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                                            <div
                                                class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase mb-4">
                                                News anlegen
                                            </div>
                                            <div class="mt-4 flex flex-col space-y-2">
                                                <div>
                                                    <input class="text-gray-200" type="file" wire:model="file">
                                                    @error('file') <span
                                                        class="text-red-500">{{ $message }}</span> @enderror
                                                </div>
                                                <div>
                                                    <x-native-select
                                                        wire:model="form.category"
                                                        label="Kategorie"
                                                        placeholder="Wähle Kategorie"
                                                        :options="\App\Enums\NewsCategory::selectOptions()"
                                                        option-label="label" option-value="value"
                                                    />
                                                </div>
                                                <div>
                                                    <x-input label="Titel" wire:model="form.name"/>
                                                </div>
                                                <div>
                                                    <x-textarea
                                                        description="optional"
                                                        label="Beschreibung" wire:model="form.description"/>
                                                </div>
                                                <button
                                                    wire:click="save"
                                                    class="btn-sm w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 text-gray-800 dark:text-gray-300">
                                                    Hinzufügen
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        @else
            <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
                <div class="bg-white dark:bg-[#1B1B1B] shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-200">
                            News
                        </h3>
                        <p class="mt-1 max-w">
                            Du bist nicht berechtigt, die News einzusehen.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
    @endvolt
</x-layouts.app>
