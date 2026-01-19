<?php

use App\Models\ProjectProposal;
use App\Support\NostrAuth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Projektförderung bearbeiten')]
class extends Component
{
    public ProjectProposal $project;

    public array $form = [
        'name' => '',
        'description' => '',
        'support_in_sats' => '',
        'website' => '',
        'accepted' => false,
        'sats_paid' => 0,
    ];

    public bool $isAllowed = false;

    public bool $isAdmin = false;

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
                    'support_in_sats' => (string) $this->project->support_in_sats,
                    'website' => $this->project->website ?? '',
                    'accepted' => (bool) $this->project->accepted,
                    'sats_paid' => $this->project->sats_paid,
                ];
            }

            if ($currentPleb && in_array($currentPleb->npub, config('einundzwanzig.config.current_board'), true)) {
                $this->isAdmin = true;
            }
        }
    }

    public function update(): void
    {
        $this->validate([
            'form.name' => 'required|string|max:255',
            'form.description' => 'required|string',
            'form.support_in_sats' => 'required|integer|min:0',
            'form.website' => 'required|url|max:255',
        ]);

        $this->project->update([
            'name' => $this->form['name'],
            'description' => $this->form['description'],
            'support_in_sats' => (int) $this->form['support_in_sats'],
            'website' => $this->form['website'],
            'accepted' => $this->isAdmin ? (bool) $this->form['accepted'] : $this->project->accepted,
            'sats_paid' => $this->isAdmin ? $this->form['sats_paid'] : $this->project->sats_paid,
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left column - Form -->
                <div class="lg:col-span-2">
                    <flux:card>
                        <flux:fieldset>
                            <flux:legend>Formular</flux:legend>
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
                                        <flux:label>Website</flux:label>
                                        <flux:input wire:model="form.website" type="url" placeholder="https://example.com" />
                                        <flux:error name="form.website" />
                                    </flux:field>
                                </div>
                                <div>
                                    <flux:field>
                                        <flux:label>Unterstützung (Sats)</flux:label>
                                        <flux:input wire:model="form.support_in_sats" type="number" placeholder="100000" />
                                        <flux:error name="form.support_in_sats" />
                                    </flux:field>
                                </div>
                                <div>
                                    <flux:editor wire:model="form.description" label="Beschreibung" description="Projektbeschreibung..." />
                                    <flux:error name="form.description" />
                                </div>

                                @if($isAdmin)
                                    <flux:separator />
                                    <flux:heading level="3" class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Admin Felder</flux:heading>
                                    <div class="space-y-3 mt-3">
                                        <flux:field>
                                            <flux:label>Akzeptiert</flux:label>
                                            <flux:switch wire:model="form.accepted" />
                                        </flux:field>
                                        <flux:field>
                                            <flux:label>Sats bezahlt</flux:label>
                                            <flux:input type="number" wire:model="form.sats_paid" />
                                        </flux:field>
                                    </div>
                                @endif

                                <flux:button wire:click="update" wire:loading.attr="disabled" variant="primary" class="w-full mt-4">
                                    Speichern
                                </flux:button>
                            </div>
                        </flux:fieldset>
                    </flux:card>
                </div>

                <!-- Right column - Information -->
                <div>
                    <flux:card>
                        <flux:heading level="2">Information</flux:heading>
                        <p class="text-sm text-gray-800 dark:text-gray-100 mt-4">
                            Bearbeite die Projektförderung und speichere deine Änderungen.
                        </p>
                    </flux:card>
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
