@props(['project', 'currentPleb', 'section' => 'all'])

@php
    $boardVotes = $project->votes->filter(function ($vote) {
        return in_array($vote->einundzwanzigPleb->npub, config('einundzwanzig.config.current_board'));
    });
    $approveCount = $boardVotes->where('value', 1)->count();
    $disapproveCount = $boardVotes->where('value', 0)->count();

    $shouldDisplay = match($section) {
        'all' => true,
        'new' => $approveCount < 3,
        'supported' => $project->sats_paid > 0,
        'rejected' => $disapproveCount >= 3,
        'approved' => ($approveCount === 3 || $disapproveCount !== 3),
        default => true,
    };
@endphp

@if($shouldDisplay)

    <flux:card class="flex overflow-hidden" wire:key="project_{{ $project->id }}">
        @if(!$project->sats_paid)
            <a class="relative block w-24 sm:w-56 xl:sidebar-expanded:w-40 2xl:sidebar-expanded:w-56 shrink-0"
               href="{{ route('association.projectSupport.item', ['projectProposal' => $project]) }}">
                <img class="absolute object-cover object-center w-full h-full"
                     src="{{ $project->getFirstMediaUrl('main') }}" alt="Meetup 01">
                <button class="absolute top-0 right-0 mt-4 mr-4">
                    <img class="rounded-full h-8 w-8"
                         src="{{ $project->einundzwanzigPleb->profile?->picture ?? asset('einundzwanzig-alpha.jpg') }}"
                         alt="">
                </button>
            </a>
        @else
            <div
                class="relative block w-24 sm:w-56 xl:sidebar-expanded:w-40 2xl:sidebar-expanded:w-56 shrink-0"
                href="{{ route('association.projectSupport.item', ['projectProposal' => $project]) }}">
                <img class="absolute object-cover object-center w-full h-full"
                     src="{{ $project->getFirstMediaUrl('main') }}" alt="Meetup 01">
                <button class="absolute top-0 right-0 mt-4 mr-4">
                    <img class="rounded-full h-8 w-8"
                         src="{{ $project->einundzwanzigPleb->profile?->picture ?? asset('einundzwanzig-alpha.jpg') }}"
                         alt="">
                </button>
            </div>
        @endif
        <!-- Content -->
        <div class="grow p-5 flex flex-col">
            <div class="grow">
                <div class="text-sm font-semibold text-amber-500 uppercase mb-2">
                    Eingereicht
                    von: {{ $project->einundzwanzigPleb->profile?->name ?? str($project->einundzwanzigPleb->npub)->limit(32) }}
                </div>
                <div class="inline-flex mb-2">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">
                        {{ $project->name }}
                    </h3>
                </div>
                <div class="text-sm line-clamp-1 sm:line-clamp-3">
                    {!! strip_tags($project->description) !!}
                </div>
            </div>
            <!-- Footer -->
            <div class="flex justify-between items-center mt-3">
                <!-- Tag -->
                <div
                    class="text-xs inline-flex items-center font-bold border border-gray-200 dark:border-gray-700/60 text-gray-600 dark:text-gray-200 rounded-full text-center px-2.5 py-1">
                    <span>{{ number_format($project->support_in_sats, 0, ',', '.') }} Sats</span>
                </div>
                <div
                    class="text-xs inline-flex items-center font-bold border border-gray-200 dark:border-gray-700/60 text-gray-600 dark:text-gray-200 rounded-full text-center px-2.5 py-1">
                    <a href="{{ $project->website }}" target="_blank">Webseite</a>
                </div>
                <!-- Avatars -->
                @if($project->votes->where('value', true)->count() > 0)
                    <div class="hidden sm:flex items-center space-x-2">
                        <div class="text-xs font-medium text-gray-400 dark:text-gray-300 italic">
                            Anzahl der Unterstützer:
                            +{{ $project->votes->where('value', true)->count() }}
                        </div>
                    </div>
                @endif
            </div>
             <div
                 class="flex flex-col sm:flex-row justify-between items-center mt-3 space-y-2 sm:space-y-0">
                 @if(
                     ($currentPleb && $currentPleb->id === $project->einundzwanzig_pleb_id)
                     || ($currentPleb && in_array($currentPleb->npub, config('einundzwanzig.config.current_board'), true))
                      )
                     <flux:button
                         icon="trash"
                         size="xs"
                         variant="danger"
                         wire:click="$dispatch('confirmDeleteProject', { id: {{ $project->id }} })">
                         Löschen
                     </flux:button>

                     <flux:button
                         icon="pencil"
                         size="xs"
                         :href="route('association.projectSupport.edit', ['projectProposal' => $project])">
                         Editieren
                     </flux:button>
                 @endif
                @if(($currentPleb && $currentPleb->association_status->value > 2) || $project->accepted)
                    <flux:button
                        icon="folder-open"
                        size="xs"
                        :href="route('association.projectSupport.item', ['projectProposal' => $project])">
                        Öffnen
                    </flux:button>
                @endif
            </div>
            <div class="py-2">
                @if($project->sats_paid)
                    <div
                        class="text-sm inline-flex font-medium bg-green-500/20 text-green-700 rounded-full text-center px-2.5 py-1">
                        Wurde mit {{ number_format($project->sats_paid, 0, ',', '.') }} Sats
                        unterstützt
                    </div>
                @endif
            </div>
        </div>
    </flux:card>
@endif
