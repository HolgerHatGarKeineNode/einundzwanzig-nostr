<x-layouts.app title="{{ __('Projektförderung anlegen') }}">
    <div>
        @if($isAllowed)
            <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
                <div
                    class="flex flex-col md:flex-row items-center mb-6 space-y-4 md:space-y-0 md:space-x-4">
                    <div class="flex items-center justify-between w-full">
                        <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">
                            Projektförderung anlegen
                        </h1>
                    </div>
                </div>

                <div class="md:flex">
                    <!-- Left column -->
                    <div class="w-full md:w-60 mb-4 md:mb-0">
                        <div
                            class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-5">
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                                Formular
                            </h2>
                            <div class="space-y-4">
                                <div wire:dirty>
                                    <x-input label="Name" wire:model="form.name"/>
                                    @error('form.name')
                                        <span class="text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div wire:dirty>
                                    <x-textarea label="Beschreibung" wire:model="form.description"/>
                                    @error('form.description')
                                        <span class="text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>
                                <button
                                    wire:click="save"
                                    wire:loading.attr="disabled"
                                    class="w-full btn-sm bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 text-gray-800 dark:text-gray-300">
                                    Speichern
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right column -->
                    <div class="flex-1 md:ml-8">
                        <div
                            class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-5">
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                                Information
                            </h2>
                            <p class="text-sm text-gray-800 dark:text-gray-100">
                                Fülle das Formular aus, um eine neue Projektförderung anzulegen.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
                <div class="bg-white dark:bg-[#1B1B1B] shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-200">
                            Projektförderung
                        </h3>
                        <p class="mt-1 max-w">
                            Du bist nicht berechtigt, eine Projektförderung anzulegen.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
