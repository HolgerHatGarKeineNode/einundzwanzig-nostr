<?php

use App\Models\ProjectProposal;
use App\Support\NostrAuth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Projektförderung bearbeiten')]
class extends Component {
    public ProjectProposal $project;

    public array $form = [
        'name' => '',
        'description' => '',
    ];

    public bool $isAllowed = false;

    public function mount($projectProposal): void
    {
        $this->project = ProjectProposal::query()->where('slug', $projectProposal)->firstOrFail();

        if (NostrAuth::check()) {
            $currentPubkey = NostrAuth::pubkey();
            $currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', $currentPubkey)->first();

            if (
                (
                    $currentPleb
                    && $currentPleb->id === $this->project->einundzwanzig_pleb_id
                )
                || in_array($currentPleb->npub, config('einundzwanzig.config.current_board'))
            ) {
                $this->isAllowed = true;
                $this->form = [
                    'name' => $this->project->name,
                    'description' => $this->project->description,
                ];
            }
        }
    }

    public function update(): void
    {
        $this->validate([
            'form.name' => 'required|string|max:255',
            'form.description' => 'required|string',
        ]);

        $this->project->update([
            'name' => $this->form['name'],
            'description' => $this->form['description'],
        ]);

        $this->redirectRoute('association.projectSupport.item', $this->project);
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
                        Projektförderung bearbeiten
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
                            <div>
                                <flux:field>
                                    <flux:label>Name</flux:label>
                                    <flux:input wire:model="form.name" placeholder="Projektname" />
                                    <flux:error name="form.name" />
                                </flux:field>
                            </div>
                            <div>
                                <flux:field>
                                    <flux:label>Beschreibung</flux:label>
                                    <flux:textarea wire:model="form.description" rows="6" placeholder="Projektbeschreibung..." />
                                    <flux:error name="form.description" />
                                </flux:field>
                            </div>
                            <flux:button wire:click="update" wire:loading.attr="disabled" variant="primary" class="w-full">
                                Speichern
                            </flux:button>
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
                            Bearbeite die Projektförderung und speichere deine Änderungen.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <flux:callout variant="warning" icon="exclamation-circle">
                <flux:heading>Projektförderung kann nicht bearbeitet werden</flux:heading>
                <p>Um diese Projektförderung zu bearbeiten, musst du entweder:</p>
                <ul class="list-disc ml-5 mt-2 space-y-1">
                    <li>Der Ersteller dieser Projektförderung sein</li>
                    <li>Ein Mitglied des aktuellen Vorstands sein</li>
                </ul>
                <p class="mt-3">
                    @if(!NostrAuth::check())
                        Bitte melde dich zunächst mit Nostr an.
                    @else
                        Bitte kontaktiere den Vorstand, wenn du denkst, dass du berechtigt sein solltest.
                    @endif
                </p>
            </flux:callout>
        </div>
    @endif
</div>
