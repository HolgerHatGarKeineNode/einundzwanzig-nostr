<?php

use App\Livewire\Forms\ProjectProposalForm;
use Livewire\Volt\Component;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Key\Key;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

use function Laravel\Folio\{middleware};
use function Laravel\Folio\name;
use function Livewire\Volt\{state, mount, on, computed, form, usesFileUploads};

name('association.projectSupport.create');

form(ProjectProposalForm::class);

state([
    'image',
    'isAllowed' => false,
    'currentPubkey' => null,
    'currentPleb' => null,
]);

usesFileUploads();

on([
    'nostrLoggedIn' => function ($pubkey) {
        $this->currentPubkey = $pubkey;
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', $pubkey)->first();
        if ($this->currentPleb->association_status->value < 3) {
            return $this->js('alert("Du bist hierzu nicht berechtigt.")');
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

    $projectProposal = \App\Models\ProjectProposal::query()->create([
        ...$this->form->all(),
        'einundzwanzig_pleb_id' => $this->currentPleb->id,
    ]);
    if ($this->image) {
        $this->validate([
            'image' => 'image|max:1024',
        ]);
        $projectProposal
            ->addMedia($this->image->getRealPath())
            ->toMediaCollection('main');
    }

    return redirect()->route('association.projectSupport');
};

?>

<x-layouts.app title="Neuer Vorschlag für eine Unterstützung">
    @volt
    <div x-cloak x-show="isAllowed" class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto"
         x-data="nostrDefault(@this)">
        <form class="space-y-8 divide-y divide-gray-700 pb-24">
            <div class="space-y-8 divide-y divide-gray-700 sm:space-y-5">
                <div class="mt-6 sm:mt-5 space-y-6 sm:space-y-5">

                    <x-input.group :for=" md5('image')" :label="__('Bild')">
                        <div class="py-4">
                            @if ($image && method_exists($image, 'temporaryUrl') && str($image->getMimeType())->contains(['image/jpeg','image/jpg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp']))
                                <div class="text-gray-200">{{ __('Preview') }}:</div>
                                <img class="h-48 object-contain" src="{{ $image->temporaryUrl() }}">
                            @endif
                            @if (isset($projectProposal) && $projectProposal->getFirstMediaUrl('main'))
                                <div class="text-gray-200">{{ __('Current picture') }}:</div>
                                <img class="h-48 object-contain" src="{{ $projectProposal->getFirstMediaUrl('main') }}">
                            @endif
                        </div>
                        <input class="text-gray-200" type="file" wire:model="image">
                        @error('image') <span class="text-red-500">{{ $message }}</span> @enderror
                    </x-input.group>

                    <x-input.group :for="md5('form.name')" :label="__('Name')">
                        <x-input autocomplete="off" wire:model.debounce="form.name"
                                 :placeholder="__('Name')"/>
                    </x-input.group>

                    <x-input.group :for="md5('form.website')" :label="__('Webseite des Projekts')">
                        <x-input autocomplete="off" wire:model.debounce="form.website"
                                 :placeholder="__('Website')"
                                 description="Eine valide URL beginnt immer mit https://"
                        />
                    </x-input.group>

                    <x-input.group :for="md5('form.name')" :label="__('Beabsichtigte Unterstützung in Sats')">
                        <x-input type="number" autocomplete="off" wire:model.debounce="form.support_in_sats"
                                 :placeholder="__('Beabsichtigte Unterstützung in Sats')"/>
                    </x-input.group>

                    <x-input.group :for="md5('form.description')">
                        <x-slot name="label">
                            <div>
                                {{ __('Beschreibung') }}
                            </div>
                            <div
                                class="text-amber-500 text-xs py-2">{{ __('Bitte verfasse einen ausführlichen und verständlichen Antragstext, damit die Abstimmung über eine mögliche Förderung erfolgen kann.') }}</div>
                        </x-slot>
                        <div
                            class="text-amber-500 text-xs py-2">{{ __('Für Bilder in Markdown verwende bitte z.B. Imgur oder einen anderen Anbieter.') }}</div>
                        <x-input.simple-mde model="form.description"/>
                        @error('form.description') <span
                            class="text-red-500 py-2">{{ $message }}</span> @enderror
                    </x-input.group>

                    <x-input.group :for="md5('save')" label="">
                        <x-button secondary :href="route('association.projectSupport')">
                            <i class="fa fa-thin fa-arrow-left"></i>
                            {{ __('Zurück') }}
                        </x-button>
                        <x-button primary wire:click="save">
                            <i class="fa fa-thin fa-save"></i>
                            {{ __('Save') }}
                        </x-button>
                    </x-input.group>
                </div>
            </div>
        </form>
    </div>
    @endvolt
</x-layouts.app>
