<?php

namespace App\Livewire\EinundzwanzigFeed;

use App\Models\Event;
use Livewire\Component;

final class Index extends Component
{
    public array $events = [];

    public bool $newEvents = false;

    public function mount(): void
    {
        $this->events = Event::query()
            ->where('type', 'root')
            ->orderBy('created_at', 'desc')
            ->with([
                'renderedEvent',
            ])
            ->get()
            ->toArray();
    }

    public function hydrate(): void
    {
        if ($this->newEvents) {
            $this->loadMore();
        }
    }

    #[Rule('echo:events,.newEvents')]
    public function updated(): void
    {
        $this->newEvents = true;
    }

    public function loadMore(): void
    {
        $this->newEvents = false;
        $this->events = Event::query()
            ->where('type', 'root')
            ->orderBy('created_at', 'desc')
            ->with([
                'renderedEvent',
            ])
            ->get()
            ->toArray();
    }
}
