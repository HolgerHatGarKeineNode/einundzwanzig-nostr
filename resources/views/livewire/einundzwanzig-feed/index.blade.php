<x-layouts.app title="Einundzwanzig Feed">
    <div class="px-8 py-8 space-y-6">
        <div>
            @if($newEvents)
                <div class="rounded-md bg-blue-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor"
                                 aria-hidden="true">
                                <path fill-rule="evenodd"
                                      d="M18 10a8 8 0 11-16 0 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                                      clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3 flex-1 md:flex md:justify-between">
                            <p class="text-sm text-blue-700">Es gibt neue Events...</p>
                            <div class="mt-3 text-sm md:ml-6 md:mt-0">
                                <div wire:click="loadMore"
                                     class="cursor-pointer whitespace-nowrap font-medium text-blue-700 hover:text-blue-600">
                                    Anzeigen
                                    <span aria-hidden="true"> &rarr;</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        @foreach($events as $event)
            <article class="flex flex-col items-start justify-between"
                     wire:key="{{ $event['rendered_event']['event_id'] }}">
                <div class="relative flex items-center gap-x-4">
                    <img
                        src="{{ $event['rendered_event']['profile_image'] }}"
                        alt="" class="h-10 w-10 rounded-full bg-gray-50">
                    <div class="text-sm leading-6">
                        <div class="font-semibold text-gray-900">
                            <div>
                                <span class="absolute inset-0"></span>
                                {{ $event['rendered_event']['profile_name'] }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-x-4 text-xs">
                    <time datetime="{{ $event['rendered_event']['created_at'] }}"
                          class="text-gray-500">{{ $event['rendered_event']['created_at'] }}</time>
                </div>
                <div class="group relative">
                    <div class="mt-5 line-clamp-3 text-sm leading-6 text-gray-600">
                        {!! $event['rendered_event']['html'] !!}
                    </div>
                </div>
                <div class="group relative">
                    <div class="mt-5 line-clamp-3 text-sm leading-6 text-gray-600">
                        <pre>{{ json_encode(json_decode($event['json'], true, 512, JSON_THROW_ON_ERROR)['tags']) }}</pre>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</x-layouts.app>
