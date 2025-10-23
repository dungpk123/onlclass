<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ClassRoom;
use Illuminate\Support\Facades\DB;

class BulkEnrollRandom extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:bulk-enroll-random {--count=20 : Number of enrollments to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create random enrollments between students and classes for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = $this->option('count');

        $this->info("Creating {$count} random enrollments...");

        // Get all students
        $students = User::whereHas('role', function($q) {
            $q->where('name', 'student');
        })->pluck('id')->toArray();

        // Get all classes
        $classes = ClassRoom::pluck('id')->toArray();

        if (empty($students)) {
            $this->error('No students found! Please create some student users first.');
            return 1;
        }

        if (empty($classes)) {
            $this->error('No classes found! Please create some classes first.');
            return 1;
        }

        $this->info("Found " . count($students) . " students and " . count($classes) . " classes");

        $enrollments = [];
        $created = 0;

        for ($i = 0; $i < $count; $i++) {
            $studentId = $students[array_rand($students)];
            $classId = $classes[array_rand($classes)];

            // Check if enrollment already exists
            $exists = DB::table('class_enrollments')
                ->where('student_id', $studentId)
                ->where('class_id', $classId)
                ->exists();

            if (!$exists) {
                DB::table('class_enrollments')->insert([
                    'student_id' => $studentId,
                    'class_id' => $classId,
                    'enrolled_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $created++;
                $this->line("✓ Enrolled student {$studentId} in class {$classId}");
            } else {
                $this->line("- Student {$studentId} already enrolled in class {$classId}, skipping");
            }
        }

        $this->info("Successfully created {$created} enrollments!");

        // Show summary
        $totalEnrollments = DB::table('class_enrollments')->count();
        $this->info("Total enrollments in database: {$totalEnrollments}");

        return 0;
    }
}
