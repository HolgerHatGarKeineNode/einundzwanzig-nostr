<?php

use App\Models\ProjectProposal;
use App\Support\NostrAuth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Layout('layouts.app')]
#[Title('Projektförderung ')]
class extends Component {
    public ProjectProposal $project;

    public bool $isAllowed = false;

    public function mount(ProjectProposal $project): void
    {
        $this->project = $project;
        if (NostrAuth::check()) {
            $this->isAllowed = true;
        }
    }
};
?>

<div>
    @if($isAllowed)
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <div
                class="flex flex-col md:flex-row items-center mb-6 space-y-4 md:space-y-0 md:space-x-4">
                <div class="flex items-center justify-between w-full">
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">
                        {{ $project->name }}
                    </h1>
                    <div>
                        @if($project->status === 'pending')
                            <x-badge info label="Pending"/>
                        @elseif($project->status === 'active')
                            <x-badge success label="Active"/>
                        @else
                            <x-badge neutral label="Archiviert"/>
                        @endif
                    </div>
                </div>
            </div>

            <div class="md:flex">
                <!-- Left column -->
                <div class="w-full md:w-60 mb-4 md:mb-0">
                    <flux:card>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                            Details
                        </h2>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-xs font-semibold text-gray-500 uppercase mb-1">Status</dt>
                                <dd class="text-sm text-gray-800 dark:text-gray-100">
                                    @if($project->status === 'pending')
                                        Ausstehend
                                    @elseif($project->status === 'active')
                                        Aktiv
                                    @else
                                        Archiviert
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-gray-500 uppercase mb-1">Erstellt am</dt>
                                <dd class="text-sm text-gray-800 dark:text-gray-100">
                                    {{ $project->created_at->format('d.m.Y') }}
                                </dd>
                            </div>
                        </dl>
                    </flux:card>
                </div>

                <!-- Right column -->
                <div class="flex-1 md:ml-8">
                    <flux:card>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                            Beschreibung
                        </h2>
                        <p class="text-sm text-gray-800 dark:text-gray-100">
                            {{ $project->description ?? 'Keine Beschreibung' }}
                        </p>
                    </flux:card>
                </div>
            </div>
        </div>
    @else
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <flux:callout variant="warning" icon="exclamation-circle">
                <flux:heading>Zugriff auf Projektförderung nicht möglich</flux:heading>
                <p>
                    @if(!NostrAuth::check())
                        Bitte melde dich zunächst mit Nostr an, um Zugriff auf die Projektförderung zu erhalten.
                    @else
                        Du benötigst eine gültige Nostr-Authentifizierung, um diese Projektförderung einzusehen.
                    @endif
                </p>
            </flux:callout>
        </div>
    @endif
</div>
