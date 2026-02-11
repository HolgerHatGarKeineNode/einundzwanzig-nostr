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

        $news = Notification::query()->create([
            'name' => $this->form['name'],
            'description' => $this->form['description'] ?? null,
        ]);
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
            <div class="xl:flex">

                <!-- Left + Middle content -->
                <div class="md:flex flex-1">

                    <!-- Left content -->
                    <div class="w-full md:w-60 mb-8 md:mb-0">
                        <div
                            class="md:sticky md:top-16 md:h-[calc(100dvh-64px)] md:overflow-x-hidden md:overflow-y-auto no-scrollbar">
                            <div class="md:py-8">

                                <div class="flex justify-between items-center md:block">

                                    <!-- Title -->
                                    <header class="mb-6">
                                        <h1 class="text-2xl md:text-3xl text-zinc-800 dark:text-zinc-100 font-bold">
                                            News
                                        </h1>
                                    </header>

                                </div>

                                <!-- Links -->
                                <div
                                    class="flex flex-nowrap overflow-x-scroll no-scrollbar md:block md:overflow-auto px-4 md:space-y-3 -mx-4">
                                    <!-- Group 1 -->
                                    <div>
                                        <div
                                            class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase mb-3 md:sr-only">
                                            Kategorien
                                        </div>
                                        <ul class="flex flex-nowrap md:block mr-3 md:mr-0">
                                            <li class="mr-0.5 md:mr-0 md:mb-0.5" wire:key="category_all">
                                                <button
                                                    type="button"
                                                    wire:click="clearFilter"
                                                    @class([
                                                        'inline-flex items-center px-2.5 py-1 rounded-md text-sm font-medium transition-colors cursor-pointer',
                                                        'bg-amber-500 text-white' => $selectedCategory === null,
                                                        'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' => $selectedCategory !== null,
                                                    ])
                                                >
                                                    <i class="fa-sharp-duotone fa-solid fa-layer-group shrink-0 fill-current mr-2"></i>
                                                    <span>Alle</span>
                                                </button>
                                            </li>
                                            @foreach(\App\Enums\NewsCategory::selectOptions() as $category)
                                                <li class="mr-0.5 md:mr-0 md:mb-0.5"
                                                    wire:key="category_{{ $category['value'] }}">
                                                    <button
                                                        type="button"
                                                        wire:click="filterByCategory({{ $category['value'] }})"
                                                        @class([
                                                            'inline-flex items-center px-2.5 py-1 rounded-md text-sm font-medium transition-colors cursor-pointer',
                                                            'bg-amber-500 text-white' => $selectedCategory === $category['value'],
                                                            'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' => $selectedCategory !== $category['value'],
                                                        ])
                                                    >
                                                        <i class="fa-sharp-duotone fa-solid fa-{{ $category['icon'] }} shrink-0 fill-current mr-2"></i>
                                                        <span>{{ $category['label'] }}</span>
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Middle content -->
                    <div class="flex-1 md:ml-8 xl:mx-4 2xl:mx-8">
                        <div class="md:py-8">

                            <div class="space-y-2">
                                @forelse($this->filteredNews as $post)
                                    <flux:card wire:key="post_{{ $post->id }}">
                                        <!-- Avatar -->
                                        <div class="shrink-0 mt-1.5">
                                            <img class="w-8 h-8 rounded-full"
                                                 src="{{ $post->einundzwanzigPleb->profile?->picture ?? asset('einundzwanzig-alpha.jpg') }}"
                                                 width="32" height="32"
                                                 alt="{{ $post->einundzwanzigPleb->profile?->name }}">
                                        </div>
                                        <!-- Content -->
                                        <div class="grow">
                                            <!-- Category Badge -->
                                            <div class="mb-2">
                                                <button
                                                    type="button"
                                                    wire:click="filterByCategory({{ $post->category->value }})"
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors"
                                                >
                                                    <i class="fa-sharp-duotone fa-solid fa-{{ $post->category->icon() }} mr-1"></i>
                                                    {{ $post->category->label() }}
                                                </button>
                                            </div>
                                            <!-- Title -->
                                            <h2 class="font-semibold text-zinc-800 dark:text-zinc-100 mb-2">
                                                {{ $post->name }}
                                            </h2>
                                            <p class="mb-6">
                                                {{ $post->description }}
                                            </p>
                                            <!-- Footer -->
                                            <footer class="flex flex-wrap text-sm">
                                                <div
                                                    class="flex items-center after:block after:content-['·'] last:after:content-[''] after:text-sm after:text-zinc-400 dark:after:text-zinc-600 after:px-2">
                                                    <div
                                                        class="font-medium text-amber-500 hover:text-amber-600 dark:hover:text-amber-400">
                                                        <div class="flex items-center">
                                                            <svg class="mr-2 fill-current" width="16"
                                                                 height="16"
                                                                 xmlns="http://www.w3.org/2000/svg">
                                                                <path
                                                                    d="M15.686 5.708 10.291.313c-.4-.4-.999-.4-1.399 0s-.4 1 0 1.399l.6.6-6.794 3.696-1-1C1.299 4.61.7 4.61.3 5.009c-.4.4-.4 1 0 1.4l1.498 1.498 2.398 2.398L.6 14.001 2 15.4l3.696-3.697L9.692 15.7c.5.5 1.199.2 1.398 0 .4-.4.4-1 0-1.4l-.999-.998 3.697-6.695.6.6c.599.6 1.199.2 1.398 0 .3-.4.3-1.1-.1-1.499Zm-7.193 6.095L4.196 7.507l6.695-3.697 1.298 1.299-3.696 6.694Z"></path>
                                                            </svg>
                                                            {{ $post->einundzwanzigPleb->profile->name }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    class="flex items-center after:block after:content-['·'] last:after:content-[''] after:text-sm after:text-zinc-400 dark:after:text-zinc-600 after:px-2">
                                                            <span
                                                                class="text-zinc-500">{{ $post->created_at->format('d.m.Y') }}</span>
                                                </div>
                                            </footer>
                                        </div>
                                        <div class="mt-2 flex justify-end w-full space-x-2">
                                            <flux:button
                                                xs
                                                target="_blank"
                                                :href="url()->temporarySignedRoute('media.signed', now()->addMinutes(30), ['media' => $post->getFirstMedia('pdf')])"
                                                icon="cloud-arrow-down">
                                                Öffnen
                                            </flux:button>
                                            @if($canEdit)
                                                <flux:modal.trigger name="delete-news-{{ $post->id }}">
                                                    <flux:button
                                                        xs
                                                        variant="danger"
                                                        icon="trash"
                                                        wire:click="confirmDelete({{ $post->id }})">
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
                                        @if($selectedCategory !== null)
                                            <p>Keine News in dieser Kategorie vorhanden.</p>
                                            <flux:button wire:click="clearFilter" size="sm" class="mt-2">
                                                Alle anzeigen
                                            </flux:button>
                                        @else
                                            <p>Keine News vorhanden.</p>
                                        @endif
                                    </flux:card>
                                @endforelse
                            </div>

                        </div>
                    </div>

                </div>

                <!-- Right content -->
                <div class="w-full mt-8 sm:mt-0 xl:w-72">
                    <div
                        class="lg:sticky lg:top-16 lg:h-[calc(100dvh-64px)] lg:overflow-x-hidden lg:overflow-y-auto no-scrollbar">
                        <div class="md:py-8">

                            <!-- Blocks -->
                            <div class="space-y-4">

                                @if($canEdit)
                                    <flux:card>
                                        <div
                                            class="text-xs font-semibold text-zinc-400 dark:text-zinc-200 uppercase mb-4">
                                            News anlegen
                                        </div>
                                        <div class="mt-4 flex flex-col space-y-2">
                                            <flux:file-upload wire:model="file" label="PDF hochladen">
                                                <flux:file-upload.dropzone heading="Drop file here or click to browse" text="PDF bis 10MB" />
                                            </flux:file-upload>
                                            @error('file')
                                            <span class="text-red-500">{{ $message }}</span>
                                            @enderror
                                            <div class="mt-3 flex flex-col gap-2">
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
                                            </div>
                                            <div>
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
                                                    <flux:error name="form.category"/>
                                                </flux:field>
                                            </div>
                                            <div>
                                                <flux:field>
                                                    <flux:label>Titel</flux:label>
                                                    <flux:input wire:model="form.name" placeholder="News-Titel"/>
                                                    <flux:error name="form.name"/>
                                                </flux:field>
                                            </div>
                                            <div>
                                                <flux:field>
                                                    <flux:label>Beschreibung</flux:label>
                                                    <flux:description>optional</flux:description>
                                                    <flux:textarea wire:model="form.description" rows="4"
                                                                   placeholder="Beschreibung..."/>
                                                    <flux:error name="form.description"/>
                                                </flux:field>
                                            </div>
                                            <flux:button wire:click="save" class="w-full">
                                                Hinzufügen
                                            </flux:button>
                                        </div>
                                    </flux:card>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>

            </div>
    @else
        <div class="">
            <flux:callout variant="warning" icon="exclamation-circle">
                <flux:heading>Zugriff auf News nicht möglich</flux:heading>
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
            </flux:callout>
        </div>
    @endif
</div>
