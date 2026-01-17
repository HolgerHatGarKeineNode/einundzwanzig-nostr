<?php

namespace App\Livewire\Meetups;

use Livewire\Component;

final class Worldmap extends Component
{
    public array $markers = [];

    public function mount(): void
    {
        $this->markers = [];
    }

    public function filterByMarker($id): void
    {
        //
    }
}
