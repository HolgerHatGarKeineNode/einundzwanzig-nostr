<?php

use App\Models\ProjectProposal;
use App\Support\NostrAuth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts.app')]
#[Title('Projektförderung anlegen')]
class extends Component
{
    use WithFileUploads;

    public array $form = [
        'name' => '',
        'description' => '',
        'support_in_sats' => '',
        'website' => '',
        'accepted' => false,
        'sats_paid' => 0,
    ];

    public $file = null;

    public bool $isAllowed = false;

    public bool $isAdmin = false;

    public function mount(): void
    {
        if (NostrAuth::check()) {
            $currentPubkey = NostrAuth::pubkey();
            $currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', $currentPubkey)->first();

            if ($currentPleb && $currentPleb->association_status->value > 1 && $currentPleb->paymentEvents()->where('year', date('Y'))->where('paid', true)->exists()) {
                $this->isAllowed = true;
            }

            if ($currentPleb && in_array($currentPleb->npub, config('einundzwanzig.config.current_board'), true)) {
                $this->isAdmin = true;
            }
        }
    }

    public function removeFile(): void
    {
        if ($this->file) {
            $this->file->delete();
            $this->file = null;
        }
    }

    public function save(): void
    {
        $this->validate([
            'form.name' => 'required|string|max:255',
            'form.description' => 'required|string',
            'form.support_in_sats' => 'required|integer|min:0',
            'form.website' => 'required|url|max:255',
            'file' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        $projectProposal = ProjectProposal::query()->create([
            'name' => $this->form['name'],
            'description' => $this->form['description'],
            'support_in_sats' => (int) $this->form['support_in_sats'],
            'website' => $this->form['website'],
            'accepted' => $this->form['accepted'],
            'sats_paid' => $this->form['sats_paid'],
            'einundzwanzig_pleb_id' => \App\Models\EinundzwanzigPleb::query()->where('pubkey', NostrAuth::pubkey())->first()->id,
        ]);

        if ($this->file) {
            $projectProposal->addMedia($this->file)->toMediaCollection('main');
        }

        $this->redirectRoute('association.projectSupport');
    }
};
?>
<div>
        @if($isAllowed)
            <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
                <div
                    class="flex flex-col md:flex-row items-center mb-6 space-y-4 md:space-y-0 md:space-x-4">
                    <div class="flex items-center justify-between w-full">
                        <h1 class="text-2xl md:text-3xl text-zinc-800 dark:text-zinc-100 font-bold">
                            Projektförderungs-Antrag anlegen
                        </h1>
                        <flux:button :href="route('association.projectSupport')" variant="ghost" icon="arrow-left">
                            Zurück
                        </flux:button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Form card -->
                    <div class="lg:col-span-2">
                        <flux:card>
                            <flux:fieldset>
                                <flux:legend>Formular</flux:legend>
                                <div class="space-y-4">
                                    <flux:field>
                                        <flux:label>Name</flux:label>
                                        <flux:input wire:model="form.name" placeholder="Projektname" />
                                        <flux:error name="form.name" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Website</flux:label>
                                        <flux:input wire:model="form.website" type="url" placeholder="https://example.com" />
                                        <flux:error name="form.website" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Unterstützung (Sats)</flux:label>
                                        <flux:input wire:model="form.support_in_sats" type="number" placeholder="100000" />
                                        <flux:error name="form.support_in_sats" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Bild</flux:label>
                                        <flux:file-upload wire:model="file" label="Bild hochladen">
                                            <flux:file-upload.dropzone
                                                heading="Bild hier ablegen oder zum Durchsuchen klicken"
                                                text="JPG, PNG, GIF, WebP bis zu 10MB"
                                            />
                                        </flux:file-upload>
                                        <flux:error name="file" />

                                        @if($file)
                                            <div class="mt-4 flex flex-col gap-2">
                                                <flux:file-item
                                                    :heading="$file->getClientOriginalName()"
                                                    :image="$file->temporaryUrl()"
                                                    :size="$file->getSize()"
                                                >
                                                    <x-slot name="actions">
                                                        <flux:file-item.remove wire:click="removeFile" />
                                                    </x-slot>
                                                </flux:file-item>
                                            </div>
                                        @endif
                                    </flux:field>
                                    <flux:editor wire:model="form.description" label="Beschreibung" description="Projektbeschreibung..." />
                                    <flux:error name="form.description" />

                                    @if($isAdmin)
                                        <flux:separator />
                                        <flux:heading level="3" class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Admin Felder</flux:heading>
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

                                    <flux:button wire:click="save" wire:loading.attr="disabled" variant="primary" class="w-full mt-4">
                                        Speichern
                                    </flux:button>
                                </div>
                            </flux:fieldset>
                        </flux:card>
                    </div>

                    <!-- Information card -->
                    <div>
                        <flux:card>
                            <flux:heading level="2">Information</flux:heading>
                            <p class="text-sm text-zinc-800 dark:text-zinc-100 mt-4">
                                Fülle das Formular aus, um eine neue Projektförderung anzulegen.
                            </p>
                        </flux:card>
                    </div>
                </div>
            </div>
        @else
            <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
                <flux:callout variant="warning" icon="exclamation-circle">
                    <flux:heading>Projektförderung kann nicht angelegt werden</flux:heading>
                    <p>Um eine Projektförderung anzulegen, benötigst du:</p>
                    <ul class="list-disc ml-5 mt-2 space-y-1">
                        <li>Einen Vereinsstatus von mindestens 2 (Aktives Mitglied)</li>
                        <li>Eine bezahlte Mitgliedschaft für das aktuelle Jahr ({{ date('Y') }})</li>
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
