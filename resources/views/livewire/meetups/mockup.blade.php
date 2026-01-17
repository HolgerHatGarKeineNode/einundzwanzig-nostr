<x-layouts.app title="{{ __('Mockup') }}">
    <div class="relative" x-data="nostrApp(@this)">
        <div class="flex items-center space-x-2 mt-12">
            <div>
                <x-input wire:model.live.debounce="title" label="Title"/>
            </div>
            <div>
                <x-textarea wire:model.live.debounce="description" label="Description"/>
            </div>
            <div>
                <x-button wire:click="save" label="Save"/>
            </div>
        </div>
        <h1 class="text-2x font-bold py-6">Meetups</h1>
        <ul class="border-t border-white space-y-4 divide-y divide-white">
            @foreach($events as $event)
                <li>
                    <div class="flex items">
                        <div class="flex items-center space-x-2">
                            <div>
                                Name: {{ collect($event['tags'])->firstWhere(0, 'title')[1] }}
                            </div>
                            <div>
                                Beschreibung: {{ $event['content'] }}
                            </div>

                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</x-layouts.app>
