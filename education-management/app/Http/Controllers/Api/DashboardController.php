<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\Document;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        } elseif ($user->isTeacher()) {
            return $this->teacherDashboard($user);
        } elseif ($user->isStudent()) {
            return $this->studentDashboard($user);
        }
    }

    /**
     * Admin dashboard data
     */
    private function adminDashboard()
    {
        $totalUsers = User::count();
        $totalTeachers = User::whereHas('role', function ($query) {
            $query->where('name', 'teacher');
        })->count();
        $totalStudents = User::whereHas('role', function ($query) {
            $query->where('name', 'student');
        })->count();

        $totalClasses = ClassRoom::count();
        $activeClasses = ClassRoom::where('end_date', '>', now())->count();
        // Total enrollments across all classes (can be > unique students if students enroll in multiple classes)
        $totalEnrollments = \DB::table('class_enrollments')->count();

        // Distinct students currently enrolled (any class) - unique count
        $totalStudentsInClasses = \DB::table('class_enrollments')
            ->distinct()
            ->count('student_id');

        // Distinct students in active classes only (classes whose end_date is in the future)
        $activeStudentsInClasses = \DB::table('class_enrollments')
            ->join('class_rooms', 'class_enrollments.class_id', '=', 'class_rooms.id')
            ->where('class_rooms.end_date', '>', now())
            ->distinct()
            ->count('class_enrollments.student_id');

        // Total enrollments in active classes (can be > unique students)
        $activeEnrollments = \DB::table('class_enrollments')
            ->join('class_rooms', 'class_enrollments.class_id', '=', 'class_rooms.id')
            ->where('class_rooms.end_date', '>', now())
            ->count();

        // Class capacity usage calculation
        $totalCapacity = ClassRoom::sum('capacity');
        $activeCapacity = ClassRoom::where('end_date', '>', now())->sum('capacity');

        // Use total enrollments (not unique students) for capacity calculation as each enrollment takes a seat
        $capacityUsedPercent = $totalCapacity > 0 ? round(($totalEnrollments / $totalCapacity) * 100, 2) : 0;

        $totalDocuments = Document::count();
        $publicDocuments = Document::where('is_public', true)->count();
        $newDocuments = Document::where('created_at', '>=', now()->subDays(7))->count();

        $totalEvaluations = Evaluation::count();
        $averageRating = Evaluation::avg('rating');

        // Monthly student registrations (last 12 months including current)
        $startMonth = Carbon::now()->startOfMonth()->subMonths(11);
        $studentsMonthlyRaw = User::whereHas('role', function ($q) {
                $q->where('name', 'student');
            })
            ->where('created_at', '>=', $startMonth)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as ym, COUNT(*) as count')
            ->groupBy('ym')
            ->pluck('count', 'ym');

        $studentsMonthly = [];
        for ($i = 0; $i < 12; $i++) {
            $month = $startMonth->copy()->addMonths($i); // Carbon instance
            $key = $month->format('Y-m');
            $studentsMonthly[] = [
                'month' => $key,
                'count' => (int) ($studentsMonthlyRaw[$key] ?? 0)
            ];
        }

        // Yearly student registrations (last 5 years including current if present)
        $studentsYearlyRaw = User::whereHas('role', function ($q) {
                $q->where('name', 'student');
            })
            ->selectRaw('YEAR(created_at) as year, COUNT(*) as count')
            ->groupBy('year')
            ->orderBy('year')
            ->pluck('count', 'year');

        $currentYear = (int) Carbon::now()->format('Y');
        $studentsYearly = [];
        for ($y = $currentYear - 4; $y <= $currentYear; $y++) {
            $studentsYearly[] = [
                'year' => $y,
                'count' => (int) ($studentsYearlyRaw[$y] ?? 0)
            ];
        }

        // Recent activities
        $recentClasses = ClassRoom::with(['teacher'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Recent enrollments (students joining classes)
        $recentEnrollments = \DB::table('class_enrollments')
            ->join('class_rooms', 'class_enrollments.class_id', '=', 'class_rooms.id')
            ->join('users as students', 'class_enrollments.student_id', '=', 'students.id')
            ->join('users as teachers', 'class_rooms.teacher_id', '=', 'teachers.id')
            ->select(
                'class_enrollments.id',
                'class_enrollments.enrolled_at',
                'class_enrollments.created_at',
                'class_rooms.id as class_id',
                'class_rooms.name as class_name',
                'students.id as student_id',
                'students.name as student_name',
                'teachers.id as teacher_id',
                'teachers.name as teacher_name'
            )
            ->orderBy('class_enrollments.created_at', 'desc')
            ->limit(7)
            ->get();

        $recentEvaluations = Evaluation::with(['student', 'teacher', 'classRoom'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentUsers = User::with('role')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Top rated teachers
        $topTeachers = User::whereHas('role', function ($query) {
                $query->where('name', 'teacher');
            })
            ->withAvg('receivedEvaluations', 'rating')
            ->withCount('receivedEvaluations')
            ->having('received_evaluations_count', '>', 0)
            ->orderBy('received_evaluations_avg_rating', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'statistics' => [
                'users' => [
                    'total' => $totalUsers,
                    'new' => User::where('created_at', '>=', now()->subDays(7))->count(),
                    'teachers' => $totalTeachers,
                    'students' => $totalStudents
                ],
                'classes' => [
                    'total' => $totalClasses,
                    'active' => $activeClasses,
                    'enrolled_students' => $totalStudentsInClasses, // unique students enrolled (any class)
                    'active_enrolled_students' => $activeStudentsInClasses, // unique students in active classes
                    'total_enrollments' => $totalEnrollments, // total enrollments (can exceed unique students)
                    'active_enrollments' => $activeEnrollments, // total enrollments in active classes
                    'students' => $totalStudentsInClasses, // backward compatibility (deprecated; use enrolled_students)
                    'capacity_used_percent' => $capacityUsedPercent,
                    'used_capacity' => $totalEnrollments, // seats taken (total enrollments)
                    'total_capacity' => $totalCapacity,
                    'active_capacity' => $activeCapacity
                ],
                'documents' => [
                    'total' => $totalDocuments,
                    'new' => $newDocuments,
                    'public' => $publicDocuments
                ],
                'evaluations' => [
                    'total' => $totalEvaluations,
                    'average_rating' => round($averageRating ?: 0, 2)
                ],
                'student_stats' => [
                    'monthly' => $studentsMonthly,
                    'yearly' => $studentsYearly
                ]
            ],
            'recent_classes' => $recentClasses,
            'recent_enrollments' => $recentEnrollments,
            'recent_evaluations' => $recentEvaluations,
            'recent_users' => $recentUsers,
            'top_teachers' => $topTeachers
        ]);
    }

    /**
     * Teacher dashboard data
     */
    private function teacherDashboard($user)
    {
        $myClasses = ClassRoom::where('teacher_id', $user->id)->count();
        $activeClasses = ClassRoom::where('teacher_id', $user->id)
            ->where('end_date', '>', now())
            ->count();
        $completedClasses = ClassRoom::where('teacher_id', $user->id)
            ->where('end_date', '<=', now())
            ->count();

        $totalStudents = ClassRoom::where('teacher_id', $user->id)
            ->withCount('students')
            ->get()
            ->sum('students_count');

        $myEvaluations = Evaluation::where('teacher_id', $user->id)->count();
        $averageRating = Evaluation::where('teacher_id', $user->id)->avg('rating');

        $myDocuments = Document::where('uploaded_by', $user->id)->count();

        // My classes with details
        $classesWithStudents = ClassRoom::where('teacher_id', $user->id)
            ->withCount('students')
            ->with(['students' => function ($query) {
                $query->limit(5);
            }, 'documents'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Recent evaluations for my classes
        $recentEvaluations = Evaluation::where('teacher_id', $user->id)
            ->with(['student', 'classRoom'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'statistics' => [
                'classes' => [
                    'total' => $myClasses,
                    'active' => $activeClasses,
                    'completed' => $completedClasses
                ],
                'students' => $totalStudents,
                'evaluations' => [
                    'total' => $myEvaluations,
                    'average_rating' => round($averageRating ?: 0, 2)
                ],
                'documents' => [
                    'uploaded' => $myDocuments,
                    'total' => $myDocuments
                ]
            ],
            'my_classes' => $classesWithStudents,
            'recent_evaluations' => $recentEvaluations
        ]);
    }

    /**
     * Student dashboard data
     */
    private function studentDashboard($user)
    {
        $enrolledClasses = $user->enrolledClasses()->count();
        $activeClasses = $user->enrolledClasses()
            ->where('end_date', '>', now())
            ->count();
        $completedClasses = $user->enrolledClasses()
            ->where('end_date', '<=', now())
            ->count();

        $myEvaluations = Evaluation::where('student_id', $user->id)->count();
        $availableDocuments = Document::where('is_public', true)
            ->orWhereHas('classRoom.students', function ($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->count();

        // My enrolled classes
        $myClasses = $user->enrolledClasses()
            ->with(['teacher', 'students', 'documents'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Teachers I can evaluate (from my classes that I haven't evaluated yet)
        $teachersToEvaluate = User::whereHas('teachingClasses.students', function ($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->whereDoesntHave('receivedEvaluations', function ($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->with('role')
            ->get();

        return response()->json([
            'statistics' => [
                'classes' => [
                    'enrolled' => $enrolledClasses,
                    'active' => $activeClasses,
                    'completed' => $completedClasses
                ],
                'evaluations_given' => $myEvaluations,
                'available_documents' => $availableDocuments
            ],
            'my_classes' => $myClasses,
            'teachers_to_evaluate' => $teachersToEvaluate
        ]);
    }
}
