<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ApplicationForm extends Form
{
    #[Validate('required|min:5')]
    public $reason = '';
}
