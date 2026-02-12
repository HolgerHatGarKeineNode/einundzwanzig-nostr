<?php

use App\Enums\NewsCategory;
use App\Models\Notification;
use App\Support\NostrAuth;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts.app')]
#[Title('News')]
class extends Component {
    use WithFileUploads;

    #[Locked]
    public Collection|array $news = [];

    #[Url(as: 'kategorie')]
    public ?int $selectedCategory = null;

    public array $form = [
        'category' => '',
        'name' => '',
        'description' => '',
    ];

    public $file;

    #[Locked]
    public bool $isAllowed = false;

    #[Locked]
    public bool $canEdit = false;

    public ?int $confirmDeleteId = null;

    public function mount(): void
    {
        if (NostrAuth::check()) {
            $currentPubkey = NostrAuth::pubkey();
            $currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', $currentPubkey)->first();

            if (
                $currentPleb
                && $currentPleb->association_status->value > 1
                && $currentPleb->paymentEvents()->where('year', date('Y'))->where('paid', true)->exists()
            ) {
                $this->isAllowed = true;
            }

            if ($currentPleb && in_array($currentPleb->npub, config('einundzwanzig.config.current_board'))) {
                $this->canEdit = true;
            }

            $this->loadNews();
        }
    }

    #[Computed]
    public function filteredNews(): Collection|array
    {
        if ($this->selectedCategory === null) {
            return $this->news;
        }

        return collect($this->news)->filter(
            fn ($item) => $item->category->value === $this->selectedCategory
        );
    }

    public function filterByCategory(?int $category): void
    {
        $this->selectedCategory = $this->selectedCategory === $category ? null : $category;
    }

    public function clearFilter(): void
    {
        $this->selectedCategory = null;
    }

    private function loadNews(): void
    {
        $this->news = Notification::query()
            ->with(['einundzwanzigPleb.profile'])
            ->latest()
            ->get();
    }

    public function save(): void
    {
        $this->validate([
            'file' => 'required|file|mimes:pdf',
            'form.category' => 'required|string|in:'.implode(',', NewsCategory::values()),
            'form.name' => 'required|string|max:255',
            'form.description' => 'nullable|string',
        ]);

        $currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', NostrAuth::pubkey())->first();

        $news = new Notification;
        $news->name = $this->form['name'];
        $news->description = $this->form['description'] ?? null;
        $news->category = $this->form['category'];
        $news->einundzwanzig_pleb_id = $currentPleb->id;
        $news->save();

        if ($this->file) {
            $news
                ->addMedia($this->file)
                ->toMediaCollection('pdf');
        }

        $this->reset(['form', 'file']);
        $this->loadNews();
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
    }

    public function delete(): void
    {
        $news = Notification::query()->findOrFail($this->confirmDeleteId);
        $news->delete();
        $this->loadNews();
    }

    public function removeFile(): void
    {
        $this->file->delete();
        $this->file = null;
    }
};
?>

<div>
    @if($isAllowed)
        <div class="lg:flex gap-8">

            <!-- Main content -->
            <div class="flex-1 min-w-0">
                <!-- Header -->
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <flux:heading size="xl">News</flux:heading>
                </div>

                <!-- Category filter (horizontal, scrollable on mobile) -->
                <div class="mb-6 flex flex-nowrap gap-2 overflow-x-auto no-scrollbar pb-1">
                    <flux:badge
                        as="button"
                        wire:click="clearFilter"
                        :color="$selectedCategory === null ? 'amber' : 'zinc'"
                        :variant="$selectedCategory === null ? 'solid' : 'outline'"
                        size="sm"
                        class="shrink-0 cursor-pointer"
                    >
                        Alle
                    </flux:badge>
                    @foreach(\App\Enums\NewsCategory::selectOptions() as $category)
                        <flux:badge
                            wire:key="cat_{{ $category['value'] }}"
                            as="button"
                            wire:click="filterByCategory({{ $category['value'] }})"
                            :color="$selectedCategory === $category['value'] ? 'amber' : 'zinc'"
                            :variant="$selectedCategory === $category['value'] ? 'solid' : 'outline'"
                            size="sm"
                            class="shrink-0 cursor-pointer"
                        >
                            <i class="fa-sharp-duotone fa-solid fa-{{ $category['icon'] }} mr-1"></i>
                            {{ $category['label'] }}
                        </flux:badge>
                    @endforeach
                </div>

                <!-- News list -->
                <div class="space-y-4">
                    @forelse($this->filteredNews as $post)
                        <flux:card wire:key="post_{{ $post->id }}" class="space-y-0">
                            <!-- Header row: avatar + meta + actions -->
                            <div class="flex items-start gap-3">
                                <flux:avatar
                                    size="sm"
                                    :src="$post->einundzwanzigPleb->profile?->picture ?? asset('einundzwanzig-alpha.jpg')"
                                    :name="$post->einundzwanzigPleb->profile?->name ?? 'Anonym'"
                                    circle
                                />
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <flux:text class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $post->einundzwanzigPleb?->profile?->name ?? str($post->einundzwanzigPleb?->npub)->limit(32) }}
                                        </flux:text>
                                        <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">
                                            {{ $post->created_at->format('d.m.Y') }}
                                        </flux:text>
                                    </div>
                                    <flux:badge
                                        as="button"
                                        wire:click="filterByCategory({{ $post->category->value }})"
                                        :color="$post->category->color()"
                                        size="sm"
                                        class="cursor-pointer"
                                    >
                                        <i class="fa-sharp-duotone fa-solid fa-{{ $post->category->icon() }} mr-1"></i>
                                        {{ $post->category->label() }}
                                    </flux:badge>
                                </div>
                            </div>

                            <!-- Body -->
                            <div class="mt-3">
                                <flux:heading class="mb-1">{{ $post->name }}</flux:heading>
                                @if($post->description)
                                    <flux:text class="text-sm">{{ $post->description }}</flux:text>
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="mt-4 flex items-center gap-2">
                                @if($post->getFirstMedia('pdf'))
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        target="_blank"
                                        :href="url()->temporarySignedRoute('media.signed', now()->addMinutes(30), ['media' => $post->getFirstMedia('pdf')])"
                                        icon="document-arrow-down"
                                    >
                                        PDF öffnen
                                    </flux:button>
                                @endif
                                @if($canEdit)
                                    <flux:spacer />
                                    <flux:modal.trigger name="delete-news-{{ $post->id }}">
                                        <flux:button
                                            size="sm"
                                            variant="danger"
                                            icon="trash"
                                            wire:click="confirmDelete({{ $post->id }})"
                                        >
                                            Löschen
                                        </flux:button>
                                    </flux:modal.trigger>

                                    <flux:modal name="delete-news-{{ $post->id }}" class="min-w-88">
                                        <div class="space-y-6">
                                            <div>
                                                <flux:heading size="lg">News löschen?</flux:heading>
                                                <flux:text class="mt-2">
                                                    Du bist dabei, diese News zu löschen.<br>
                                                    Diese Aktion kann nicht rückgängig gemacht werden.
                                                </flux:text>
                                            </div>
                                            <div class="flex gap-2">
                                                <flux:spacer />
                                                <flux:modal.close>
                                                    <flux:button variant="ghost">Abbrechen</flux:button>
                                                </flux:modal.close>
                                                <flux:button wire:click="delete" variant="danger">Löschen</flux:button>
                                            </div>
                                        </div>
                                    </flux:modal>
                                @endif
                            </div>
                        </flux:card>
                    @empty
                        <flux:card>
                            <div class="py-6 text-center">
                                @if($selectedCategory !== null)
                                    <flux:icon name="funnel" class="mx-auto mb-3 text-zinc-300 dark:text-zinc-600" />
                                    <flux:heading>Keine News in dieser Kategorie</flux:heading>
                                    <flux:text class="mt-1 text-sm">Versuche eine andere Kategorie oder zeige alle an.</flux:text>
                                    <flux:button wire:click="clearFilter" size="sm" class="mt-4">
                                        Alle anzeigen
                                    </flux:button>
                                @else
                                    <flux:icon name="newspaper" class="mx-auto mb-3 text-zinc-300 dark:text-zinc-600" />
                                    <flux:heading>Noch keine News vorhanden</flux:heading>
                                    <flux:text class="mt-1 text-sm">Hier werden zukünftige Neuigkeiten angezeigt.</flux:text>
                                @endif
                            </div>
                        </flux:card>
                    @endforelse
                </div>
            </div>

            <!-- Sidebar: create form (board members only) -->
            @if($canEdit)
                <div class="w-full lg:w-80 shrink-0 mt-8 lg:mt-0">
                    <div class="lg:sticky lg:top-16">
                        <flux:card class="space-y-4">
                            <flux:heading>News anlegen</flux:heading>
                            <flux:separator />

                            <flux:file-upload wire:model="file" label="PDF hochladen">
                                <flux:file-upload.dropzone heading="Datei hier ablegen oder klicken" text="PDF bis 10MB" />
                            </flux:file-upload>
                            <flux:error name="file" />

                            @if ($file)
                                <flux:file-item
                                    :heading="$file->getClientOriginalName()"
                                    :size="$file->getSize()"
                                >
                                    <x-slot name="actions">
                                        <flux:file-item.remove wire:click="removeFile" aria-label="{{ 'Remove file: ' . $file->getClientOriginalName() }}" />
                                    </x-slot>
                                </flux:file-item>
                            @endif

                            <flux:field>
                                <flux:label>Kategorie</flux:label>
                                <flux:select
                                    wire:model="form.category"
                                    placeholder="Wähle Kategorie"
                                >
                                    @foreach(\App\Enums\NewsCategory::selectOptions() as $category)
                                        <flux:select.option
                                            :label="$category['label']"
                                            :value="$category['value']"
                                        />
                                    @endforeach
                                </flux:select>
                                <flux:error name="form.category" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Titel</flux:label>
                                <flux:input wire:model="form.name" placeholder="News-Titel" />
                                <flux:error name="form.name" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Beschreibung</flux:label>
                                <flux:description>optional</flux:description>
                                <flux:textarea wire:model="form.description" rows="4" placeholder="Beschreibung..." />
                                <flux:error name="form.description" />
                            </flux:field>

                            <flux:button wire:click="save" variant="primary" class="w-full">
                                Hinzufügen
                            </flux:button>
                        </flux:card>
                    </div>
                </div>
            @endif

        </div>
    @else
        <div class="max-w-2xl mx-auto">
            <flux:callout variant="warning" icon="exclamation-circle">
                <flux:callout.heading>Zugriff auf News nicht möglich</flux:callout.heading>
                <flux:callout.text>
                    <p>Um die News einzusehen, benötigst du:</p>
                    <ul class="list-disc ml-5 mt-2 space-y-1">
                        <li>Einen Vereinsstatus von "Aktives Mitglied"</li>
                        <li>Eine bezahlte Mitgliedschaft für das aktuelle Jahr ({{ date('Y') }})</li>
                    </ul>
                    <p class="mt-3">
                        @if(!NostrAuth::check())
                            Bitte melde dich zunächst mit Nostr an.
                        @else
                            Bitte kontaktiere den Vorstand, wenn du denkst, dass du berechtigt sein solltest.
                        @endif
                    </p>
                </flux:callout.text>
            </flux:callout>
        </div>
    @endif
</div>
