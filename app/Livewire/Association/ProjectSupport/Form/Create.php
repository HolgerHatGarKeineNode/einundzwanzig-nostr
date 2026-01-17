<?php

namespace App\Livewire\Association\ProjectSupport\Form;

use App\Livewire\Forms\ProjectProposalForm;
use App\Support\NostrAuth;
use Livewire\Component;
use Livewire\WithFileUploads;

final class Create extends Component
{
    use WithFileUploads;

    public ProjectProposalForm $form;

    public ?\Illuminate\Http\UploadedFile $image = null;

    public bool $isAllowed = false;

    public ?string $currentPubkey = null;

    protected $listeners = [
        'nostrLoggedIn' => 'handleNostrLoggedIn',
        'nostrLoggedOut' => 'handleNostrLoggedOut',
    ];

    public function mount(): void
    {
        if (NostrAuth::check()) {
            $this->currentPubkey = NostrAuth::pubkey();
            $this->currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', $this->currentPubkey)->first();
            $this->isAllowed = true;
        }
    }

    public function handleNostrLoggedIn($pubkey): void
    {
        NostrAuth::login($pubkey);
        $this->currentPubkey = $pubkey;
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', $pubkey)->first();
        $this->isAllowed = true;
    }

    public function handleNostrLoggedOut(): void
    {
        $this->isAllowed = false;
        $this->currentPubkey = null;
        $this->currentPleb = null;
    }

    public function save(): \Illuminate\Http\RedirectResponse
    {
        $this->form->validate();

        $projectProposal = \App\Models\ProjectProposal::query()->create([
            ...$this->form->all(),
            'einundzwanzig_pleb_id' => $this->currentPleb->id,
        ]);
        if ($this->image) {
            $this->validate([
                'image' => 'image|max:1024',
            ]);
            $projectProposal
                ->addMedia($this->image->getRealPath())
                ->toMediaCollection('main');
        }

        return redirect()->route('association.projectSupport');
    }
}
