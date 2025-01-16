<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ProjectProposalForm extends Form
{
    #[Validate('required|min:5')]
    public $name = '';

    #[Validate('required|numeric|min:21')]
    public $support_in_sats = '';

    #[Validate('required|string|min:5')]
    public $description = '';

    #[Validate('required|url')]
    public $website = '';

    #[Validate('bool')]
    public $accepted = '';

    #[Validate('nullable|numeric')]
    public $sats_paid = '';
}
