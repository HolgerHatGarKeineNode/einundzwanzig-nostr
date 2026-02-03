<?php

use App\Support\NostrAuth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public bool $isLoggedIn = false;

    #[Locked]
    public string $location = 'sidebar'; // 'sidebar' or 'navbar'

    public function mount(): void
    {
        $this->isLoggedIn = NostrAuth::check();
    }

    #[On('nostrLoggedIn')]
    public function handleNostrLoggedIn(string $pubkey): void
    {
        NostrAuth::login($pubkey);
        $this->js('window.location.reload(true);');
    }

    #[On('nostrLoggedOut')]
    public function handleNostrLoggedOut(): void
    {
        $this->isLoggedIn = false;
    }
}
?>

<div x-data="nostrLogin">
    @if($isLoggedIn)
        @if($location === 'sidebar')
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <flux:button variant="ghost" icon="arrow-right-start-on-rectangle" type="submit" wire:click="$dispatch('nostrLoggedOut')">Logout</flux:button>
            </form>
        @else
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <flux:button variant="ghost" icon="arrow-right-start-on-rectangle" type="submit" wire:click="$dispatch('nostrLoggedOut')">Logout</flux:button>
            </form>
        @endif
    @else
        @if($location === 'sidebar')
            <flux:button variant="primary" icon="user" @click="openNostrLogin">Mit Nostr verbinden</flux:button>
        @else
            <flux:button variant="primary" icon="user" @click="openNostrLogin">Mit Nostr verbinden</flux:button>
        @endif
    @endif
</div>
