<?php

namespace App\Livewire\Association\News;

use App\Livewire\Forms\NotificationForm;
use App\Models\EinundzwanzigPleb;
use App\Models\Notification;
use App\Support\NostrAuth;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Actions\Notification as WireNotification;

final class Index extends Component
{
    use WithFileUploads;

    public NotificationForm $form;

    public ?\Illuminate\Http\UploadedFile $file = null;

    public \Illuminate\Database\Eloquent\Collection $news;

    public bool $isAllowed = false;

    public bool $canEdit = false;

    public ?string $currentPubkey = null;

    public ?EinundzwanzigPleb $currentPleb = null;

    protected $listeners = [
        'nostrLoggedIn' => 'handleNostrLoggedIn',
        'nostrLoggedOut' => 'handleNostrLoggedOut',
    ];

    public function mount(): void
    {
        if (NostrAuth::check()) {
            $this->currentPubkey = NostrAuth::pubkey();
            $this->currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', $this->currentPubkey)->first();
            if (in_array($this->currentPleb->npub, config('einundzwanzig.config.current_board'), true)) {
                $this->canEdit = true;
            }
            $this->isAllowed = true;
        }
        $this->refreshNews();
    }

    public function refreshNews(): void
    {
        $this->news = Notification::query()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function handleNostrLoggedIn($pubkey): void
    {
        NostrAuth::login($pubkey);
        $this->currentPubkey = $pubkey;
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', $pubkey)->first();
        if (in_array($this->currentPleb->npub, config('einundzwanzig.config.current_board'), true)) {
            $this->canEdit = true;
        }
        $this->isAllowed = true;
    }

    public function handleNostrLoggedOut(): void
    {
        $this->isAllowed = false;
        $this->currentPubkey = null;
        $this->currentPleb = null;
    }

    public function save(): void
    {
        $this->form->validate();

        $this->validate([
            'file' => 'required|file|mimes:pdf|max:1024',
        ]);

        $notification = Notification::query()
            ->orderBy('created_at', 'desc')
            ->create([
                'einundzwanzig_pleb_id' => $this->currentPleb->id,
                'category' => $this->form->category,
                'name' => $this->form->name,
                'description' => $this->form->description,
            ]);

        $notification
            ->addMedia($this->file->getRealPath())
            ->usingName($this->file->getClientOriginalName())
            ->toMediaCollection('pdf');

        $this->form->reset();
        $this->file = null;

        $this->refreshNews();
    }

    public function delete($id): void
    {
        $notification = new WireNotification($this);
        $notification->confirm([
            'title' => 'Post löschen',
            'message' => 'Bist du sicher, dass du diesen Post löschen möchtest?',
            'accept' => [
                'label' => 'Ja, löschen',
                'method' => 'deleteNow',
                'params' => $id,
            ],
        ]);
    }

    public function deleteNow($id): void
    {
        $notification = Notification::query()->find($id);
        $notification->delete();
        $this->refreshNews();
    }
}
