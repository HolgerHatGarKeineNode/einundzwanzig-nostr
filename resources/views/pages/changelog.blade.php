<?php

use Livewire\Volt\Component;

use function Livewire\Volt\computed;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;
use function Laravel\Folio\{middleware};
use function Laravel\Folio\name;
use function Livewire\Volt\{on};

name('changelog');

state(['entries' => []]);

mount(function () {
    $output = shell_exec('git log -n1000 --pretty=format:"%H|%s|%an|%ad" --date=format:"%Y-%m-%d %H:%M:%S"');
    $lines = explode("\n", trim($output));
    $entries = [];

    foreach ($lines as $line) {
        [$hash, $message, $author, $date] = explode('|', $line);
        $entries[] = [
            'hash' => $hash,
            'message' => $message,
            'author' => $author,
            'date' => $date,
        ];
    }
    $this->entries = $entries;
});

?>

<x-layouts.app title="{{ __('Changelog') }}">
    @volt
    <div>
        <div
            class="sm:flex sm:justify-between sm:items-center px-4 sm:px-6 py-8 border-b border-gray-200 dark:border-gray-700/60">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Changelog</h1>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">

                {{--<!-- Add entry button -->
                <button
                    class="btn bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                    Add Entry
                </button>--}}

            </div>

        </div>
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <div class="max-w-3xl m-auto">

                <!-- Posts -->
                <div class="xl:-translate-x-16">
                    @foreach($entries as $entry)
                        <article class="pt-6">
                            <div class="xl:flex">
                                <div class="w-32 shrink-0">
                                    <div
                                        class="text-xs font-semibold uppercase text-gray-400 dark:text-gray-500 xl:leading-8">
                                        {{ $entry['date'] }}
                                    </div>
                                </div>
                                <div class="grow pb-6 border-b border-gray-200 dark:border-gray-700/60">
                                    <header>
                                        <div class="flex flex-nowrap items-center space-x-2 mb-4">
                                            <div class="flex items-center">
                                                <a class="block text-sm font-semibold text-gray-800 dark:text-gray-100"
                                                   href="#0">
                                                    {{ $entry['author'] }}
                                                </a>
                                            </div>
                                            <div class="text-gray-400 dark:text-gray-600">·</div>
                                            <div>
                                                <div
                                                    class="text-xs inline-flex font-medium bg-green-500/20 text-green-700 rounded-full text-center px-2.5 py-1">
                                                    {{ $entry['hash'] }}
                                                </div>
                                            </div>
                                        </div>
                                    </header>
                                    <div class="space-y-3 font-mono">
                                        {!! $entry['message'] !!}
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

            </div>
        </div>
    </div>
    @endvolt
</x-layouts.app>
