<?php

namespace App\Livewire\Association\ProjectSupport\Form;

use App\Livewire\Forms\ProjectProposalForm;
use App\Models\ProjectProposal;
use App\Support\NostrAuth;
use Livewire\Component;
use Livewire\WithFileUploads;

final class Edit extends Component
{
    use WithFileUploads;

    public ProjectProposalForm $form;

    public ?ProjectProposal $projectProposal = null;

    public ?\Illuminate\Http\UploadedFile $image = null;

    public bool $isAllowed = false;

    public ?string $currentPubkey = null;

    protected $listeners = [
        'nostrLoggedIn' => 'handleNostrLoggedIn',
        'nostrLoggedOut' => 'handleNostrLoggedOut',
    ];

    public function mount(ProjectProposal $projectProposal): void
    {
        if (NostrAuth::check()) {
            $this->currentPubkey = NostrAuth::pubkey();
            $this->currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', $this->currentPubkey)->first();
            $this->isAllowed = true;
            $this->form->fill($projectProposal->toArray());
            $this->projectProposal = $projectProposal;
            $this->image = $projectProposal->getFirstMedia('main');
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
        if ($this->image && method_exists($this->image, 'temporaryUrl')) {
            $this->validate([
                'image' => 'nullable|image|max:1024',
            ]);
            $this->projectProposal
                ->addMedia($this->image->getRealPath())
                ->toMediaCollection('main');
        }

        $this->projectProposal->update([
            ...$this->form->except('id', 'slug'),
        ]);

        return redirect()->route('association.projectSupport');
    }
}
