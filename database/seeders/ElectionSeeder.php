<?php

namespace Database\Seeders;

use App\Models\Election;
use Illuminate\Database\Seeder;

class ElectionSeeder extends Seeder
{
    public function run(): void
    {
        $candidates = config('einundzwanzig.config.current_board');

        Election::query()->updateOrCreate(
            ['year' => 2025],
            [
                'candidates' => $candidates,
                'end_time' => now()->setDate(2025, 4, 15),
            ]
        );

        Election::query()->updateOrCreate(
            ['year' => 2026],
            [
                'candidates' => $candidates,
                'end_time' => now()->addMonths(2),
            ]
        );

        Election::query()->updateOrCreate(
            ['year' => 2027],
            [
                'candidates' => [],
                'end_time' => now()->addYear()->addMonths(3),
            ]
        );
    }
}
