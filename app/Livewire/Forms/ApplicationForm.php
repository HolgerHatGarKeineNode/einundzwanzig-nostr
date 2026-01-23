<?php

namespace App\Livewire\Forms;

use App\Enums\AssociationStatus;
use App\Models\EinundzwanzigPleb;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ApplicationForm extends Form
{
    #[Validate('nullable|string')]
    public $reason = '';

    #[Validate('accepted')]
    public $check = false;

    public ?EinundzwanzigPleb $currentPleb = null;

    public function setPleb(EinundzwanzigPleb $pleb): void
    {
        $this->currentPleb = $pleb;
    }

    public function apply(AssociationStatus|int $status): void
    {
        $this->validate();

        $status = $status instanceof AssociationStatus ? $status : AssociationStatus::from($status);

        $this->currentPleb->update([
            'association_status' => $status,
        ]);

        $this->reset('check');
    }
}
