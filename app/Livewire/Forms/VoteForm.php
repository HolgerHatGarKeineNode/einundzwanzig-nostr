<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class VoteForm extends Form
{
    #[Validate('nullable|min:5')]
    public $reason = '';
}
