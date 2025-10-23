<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ClassRoom;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::whereHas('role', fn($q)=>$q->where('name','student'))->pluck('id')->toArray();
        $classes = ClassRoom::pluck('id')->toArray();

        if (empty($students) || empty($classes)) {
            $this->command?->info('No students or classes found; skipping enrollments.');
            return;
        }

        // Simple round-robin attach a few students to each class
        foreach ($classes as $classId) {
            $pick = collect($students)->shuffle()->take(rand(2, min(5, count($students))));
            $class = ClassRoom::find($classId);
            $attachData = [];
            foreach ($pick as $sid) {
                $attachData[$sid] = ['enrolled_at' => now(), 'created_at' => now(), 'updated_at' => now()];
            }
            $class->students()->syncWithoutDetaching($attachData);
        }
    }
}
