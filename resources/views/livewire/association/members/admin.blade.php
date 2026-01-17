<x-layouts.app title="{{ __('Mitglieder') }}">
    <div>
        @if($isAllowed)
            <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
                <livewire:einundzwanzig-pleb-table/>
            </div>
        @else
            <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
                <div class="bg-white dark:bg-[#1B1B1B] shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-200">Mitglieder</h3>
                        <p class="mt-1 max-w">
                            Du bist nicht berechtigt, Mitglieder zu bearbeiten.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
