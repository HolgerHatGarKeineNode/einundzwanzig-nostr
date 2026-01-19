<?php

use App\Support\NostrAuth;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public bool $isLoggedIn = false;

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
                <flux:sidebar.item icon="arrow-right-start-on-rectangle" type="submit" wire:click="$dispatch('nostrLoggedOut')">Logout</flux:sidebar.item>
            </form>
        @else
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <flux:navbar.item type="submit" icon="arrow-right-start-on-rectangle" wire:click="$dispatch('nostrLoggedOut')">Logout</flux:navbar.item>
            </form>
        @endif
    @else
        @if($location === 'sidebar')
            <flux:sidebar.item icon="user" @click="openNostrLogin">Mit Nostr verbinden</flux:sidebar.item>
        @else
            <flux:navbar.item icon="user" @click="openNostrLogin">Mit Nostr verbinden</flux:navbar.item>
        @endif
    @endif
</div>
