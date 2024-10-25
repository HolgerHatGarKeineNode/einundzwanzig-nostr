<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class NotificationForm extends Form
{
    #[Validate('required|string')]
    public $name = '';

    #[Validate('required|numeric')]
    public $category = '';

    #[Validate('nullable|string|min:5')]
    public $description = '';
}
