<x-layouts.app title="{{ __('Wahlen') }}">
    <div>
        @if($isAllowed)
            <div class="relative flex h-full">
                 @foreach($elections as $election)
                    <div class="w-full sm:w-1/3 p-4" wire:key="election-{{ $loop->index }}">
                        <div class="shadow-lg rounded-lg overflow-hidden">
                            {{ $election['year'] }}
                        </div>
                        <div class="shadow-lg rounded-lg overflow-hidden">
                            <x-textarea wire:model="elections.{{ $loop->index }}.candidates" rows="25"
                                        label="candidates" placeholder=""/>
                        </div>
                        <div class="py-2">
                            <x-button label="Speichern" wire:click="saveElection({{ $loop->index }})" wire:loading.attr="disabled"/>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
                <div class="bg-white dark:bg-[#1B1B1B] shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-200">Einstellungen</h3>
                        <p class="mt-1 max-w">
                            Du bist nicht berechtigt, die Einstellungen zu bearbeiten.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
