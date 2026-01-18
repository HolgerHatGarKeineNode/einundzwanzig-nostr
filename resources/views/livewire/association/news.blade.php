<?php

use App\Enums\NewsCategory;
use App\Models\Notification;
use App\Support\NostrAuth;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts.app')]
#[Title('News')]
class extends Component {
    use WithFileUploads;

    public Collection|array $news = [];

    public array $form = [
        'category' => '',
        'name' => '',
        'description' => '',
    ];

    public mixed $file;

    public bool $isAllowed = false;

    public bool $canEdit = false;

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

            if ($currentPleb && $currentPleb->association_status->value > 2) {
                $this->canEdit = true;
            }

            $this->news = \App\Models\Notification::query()
                ->with(['einundzwanzigPleb.profile'])
                ->latest()
                ->get();
        }
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
            'category' => $this->form['category'],
            'einundzwanzig_pleb_id' => $currentPleb->id,
        ]);

        if ($this->file) {
            $news
                ->addMedia($this->file)
                ->toMediaCollection('pdf');
        }

        $this->reset(['form', 'file']);

        $this->news = \App\Models\Notification::query()
            ->with(['einundzwanzigPleb.profile'])
            ->latest()
            ->get();
    }

    public function delete(int $id): void
    {
        $news = Notification::query()->find($id);
        if ($news) {
            $news->delete();
        }

        $this->news = \App\Models\Notification::query()
            ->with(['einundzwanzigPleb.profile'])
            ->latest()
            ->get();
    }
};
?>

<div>
    @if($isAllowed)
        <div class="px-4 sm:px-6 lg:px-8 py-8 md:py-0 w-full max-w-9xl mx-auto">

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
                                        <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">
                                            News</h1>
                                    </header>

                                </div>

                                <!-- Links -->
                                <div
                                    class="flex flex-nowrap overflow-x-scroll no-scrollbar md:block md:overflow-auto px-4 md:space-y-3 -mx-4">
                                    <!-- Group 1 -->
                                    <div>
                                        <div
                                            class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase mb-3 md:sr-only">
                                            Menu
                                        </div>
                                        <ul class="flex flex-nowrap md:block mr-3 md:mr-0">
                                            @foreach(\App\Enums\NewsCategory::selectOptions() as $category)
                                                <li class="mr-0.5 md:mr-0 md:mb-0.5"
                                                    wire:key="category_{{ $category['value'] }}">
                                                    <div
                                                        class="flex items-center px-2.5 py-2 rounded-lg whitespace-nowrap bg-white dark:bg-gray-800">
                                                        <i class="fa-sharp-duotone fa-solid fa-{{ $category['icon'] }} shrink-0 fill-current text-amber-500 mr-2"></i>
                                                        <span
                                                            class="text-sm font-medium text-amber-500">{{ $category['label'] }}</span>
                                                    </div>
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
                                @forelse($news as $post)
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
                                                <!-- Title -->
                                                <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-2">
                                                    {{ $post->name }}
                                                </h2>
                                                <p class="mb-6">
                                                    {{ $post->description }}
                                                </p>
                                                <!-- Footer -->
                                                <footer class="flex flex-wrap text-sm">
                                                    <div
                                                        class="flex items-center after:block after:content-['·'] last:after:content-[''] after:text-sm after:text-gray-400 dark:after:text-gray-600 after:px-2">
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
                                                        class="flex items-center after:block after:content-['·'] last:after:content-[''] after:text-sm after:text-gray-400 dark:after:text-gray-600 after:px-2">
                                                            <span
                                                                class="text-gray-500">{{ $post->created_at->format('d.m.Y') }}</span>
                                                    </div>
                                                </footer>
                                            </div>
                                        </div>
                                        <div class="mt-2 flex justify-end w-full space-x-2">
                                            <flux:button
                                                xs
                                                target="_blank"
                                                :href="url()->temporarySignedRoute('dl', now()->addMinutes(30), ['media' => $post->getFirstMedia('pdf')])"
                                                icon="cloud-arrow-down">
                                                Öffnen
                                            </flux:button>
                                            @if($canEdit)
                                                <flux:button
                                                    xs
                                                    negative
                                                    wire:click="delete({{ $post->id }})"
                                                    wire:loading.attr="disabled"
                                                    icon="trash">
                                                    Löschen
                                                </flux:button>
                                            @endif
                                        </div>
                                    </flux:card>
                                @empty
                                    <flux:card>
                                        <p>Keine News vorhanden.</p>
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
                                            class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase mb-4">
                                            News anlegen
                                        </div>
                                        <div class="mt-4 flex flex-col space-y-2">
                                            <div wire:dirty>
                                                <input class="text-gray-200" type="file" wire:model="file">
                                                @error('file')
                                                <span class="text-red-500">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div wire:dirty>
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
                                            <div wire:dirty>
                                                <flux:field>
                                                    <flux:label>Titel</flux:label>
                                                    <flux:input wire:model="form.name" placeholder="News-Titel"/>
                                                    <flux:error name="form.name"/>
                                                </flux:field>
                                            </div>
                                            <div wire:dirty>
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

        </div>
    @else
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <flux:callout variant="warning" icon="exclamation-circle">
                <flux:heading>Zugriff auf News nicht möglich</flux:heading>
                <p>Um die News einzusehen, benötigst du:</p>
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
