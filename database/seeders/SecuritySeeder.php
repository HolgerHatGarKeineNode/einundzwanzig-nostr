<?php

namespace Database\Seeders;

use App\Models\SecurityAttempt;
use Illuminate\Database\Seeder;

class SecuritySeeder extends Seeder
{
    public function run(): void
    {
        SecurityAttempt::factory()->count(15)->create();
        SecurityAttempt::factory()->high()->count(4)->create();
        SecurityAttempt::factory()->critical()->count(1)->create();
    }
}
