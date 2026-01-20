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

    <flux:card class="flex flex-col sm:flex-row overflow-hidden" wire:key="project_{{ $project->id }}">
        @if(!$project->sats_paid)
            <a class="relative block w-full h-48 sm:w-56 sm:h-auto xl:sidebar-expanded:w-40 2xl:sidebar-expanded:w-56 shrink-0 sm:shrink-0"
               href="{{ route('association.projectSupport.item', ['projectProposal' => $project]) }}">
                <img class="absolute object-cover object-center w-full h-full"
                     src="{{ $project->getFirstMediaUrl('main') }}" alt="Meetup 01">
                <button class="absolute top-0 right-0 mt-4 mr-4">
                    <img class="rounded-full h-8 w-8"
                         src="{{ $project->einundzwanzigPleb->profile?->picture }}"
                         onerror="this.src='{{ asset('einundzwanzig-alpha.jpg') }}'"
                         alt="">
                </button>
            </a>
        @else
            <a class="relative block w-full h-48 sm:w-56 sm:h-auto xl:sidebar-expanded:w-40 2xl:sidebar-expanded:w-56 shrink-0 sm:shrink-0"
               href="{{ route('association.projectSupport.item', ['projectProposal' => $project]) }}">
                <img class="absolute object-cover object-center w-full h-full"
                     src="{{ $project->getFirstMediaUrl('main') }}" alt="Meetup 01">
                <button class="absolute top-0 right-0 mt-4 mr-4">
                    <img class="rounded-full h-8 w-8"
                         src="{{ $project->einundzwanzigPleb->profile?->picture }}"
                         onerror="this.src='{{ asset('einundzwanzig-alpha.jpg') }}'"
                         alt="">
                </button>
            </a>
        @endif
        <!-- Content -->
        <div class="grow p-5 flex flex-col">
            <div class="grow">
                <div class="text-sm font-semibold text-amber-500 uppercase mb-2">
                    Eingereicht von: <flux:link href="https://njump.me/{{ $project->einundzwanzigPleb->npub }}" target="_blank">
                        {{ $project->einundzwanzigPleb->profile?->name ?? str($project->einundzwanzigPleb->npub)->limit(32) }}
                    </flux:link>
                </div>
                <div class="inline-flex mb-2">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">
                        {{ $project->name }}
                    </h3>
                </div>
            </div>
             <!-- Footer -->
             <div class="mt-3 space-y-3">
                 <!-- First row: Sats, Website, Supporters -->
                 <div class="flex flex-wrap items-center gap-2">
                     <flux:badge color="amber">{{ number_format($project->support_in_sats, 0, ',', '.') }} Sats</flux:badge>
                     <flux:link
                         href="{{ $project->website }}"
                         target="_blank">
                         Webseite
                     </flux:link>
                     @if($project->votes->where('value', true)->count() > 0)
                         <flux:badge color="blue">
                             +{{ $project->votes->where('value', true)->count() }} Unterstützer
                         </flux:badge>
                     @endif
                 </div>

                 <!-- Second row: Action buttons -->
                 <div class="flex flex-wrap gap-2">
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
             </div>
            <div class="py-2">
                @if($project->sats_paid)
                    <div class="inline-block">
                        <flux:badge color="green">Wurde mit {{ number_format($project->sats_paid, 0, ',', '.') }} Sats
                            unterstützt
                        </flux:badge>
                    </div>
                @endif
            </div>
        </div>
    </flux:card>
@endif
