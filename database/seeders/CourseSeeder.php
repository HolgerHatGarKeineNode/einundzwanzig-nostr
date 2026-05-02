<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseEvent;
use App\Models\Lecturer;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            'Grundlagen',
            'Self Custody',
            'Lightning',
            'Nostr',
            'Privacy',
            'Wirtschaft',
        ])->map(fn (string $name) => Category::query()->create(['name' => $name]));

        $markus = Lecturer::factory()->markusTurm()->create();
        $otherLecturers = Lecturer::factory()->count(3)->create();

        $bitcoinBasics = Course::factory()->bitcoinBasics()->for($markus)->create();
        $bitcoinBasics->categories()->attach([
            $categories->firstWhere('name', 'Grundlagen')->id,
            $categories->firstWhere('name', 'Wirtschaft')->id,
        ]);

        $lightningCourse = Course::factory()
            ->state(['name' => 'Lightning Network 101'])
            ->for($markus)
            ->create();
        $lightningCourse->categories()->attach($categories->firstWhere('name', 'Lightning')->id);

        foreach ($otherLecturers as $lecturer) {
            $course = Course::factory()->for($lecturer)->create();
            $course->categories()->attach($categories->random(rand(1, 3))->pluck('id'));
        }

        $venues = Venue::query()->take(3)->get();
        if ($venues->isEmpty()) {
            return;
        }

        foreach (Course::query()->get() as $course) {
            CourseEvent::factory()->for($course)->for($venues->random())->past()->create();
            CourseEvent::factory()->for($course)->for($venues->random())->create();
        }
    }
}
