<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ApplicationForm extends Form
{
    #[Validate('nullable|string')]
    public $reason = '';
    #[Validate('boolean|in:true')]
    public $check = false;
}
