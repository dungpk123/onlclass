<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClassRoom;
use App\Models\User;

class ClassEnrollmentController extends Controller
{
    /**
     * Enroll a student (or current authenticated student) into a class.
     */
    public function enroll(Request $request, $classId)
    {
        $class = ClassRoom::findOrFail($classId);
        $user = auth()->user();

        // Determine target student id: explicit student_id (admin/teacher enrolling) or current user
        $studentId = $request->input('student_id', $user->id);

        // Basic role/permission rules (adjust as needed)
        // - Student can only enroll themselves
        if ($user->isStudent() && $studentId !== $user->id) {
            return response()->json(['message' => 'Bạn không thể ghi danh người khác'], 403);
        }

        // - Teachers cannot enroll students unless we allow it explicitly
        if ($user->isTeacher() && $studentId !== $user->id) {
            return response()->json(['message' => 'Giảng viên không thể ghi danh học viên trực tiếp'], 403);
        }

        // Capacity check
        $currentCount = $class->students()->count();
        if ($class->capacity !== null && $currentCount >= $class->capacity) {
            return response()->json(['message' => 'Lớp đã đủ chỉ tiêu sĩ số'], 422);
        }

        // Prevent duplicate
        if ($class->students()->where('users.id', $studentId)->exists()) {
            return response()->json(['message' => 'Đã ghi danh trước đó'], 200);
        }

        $class->students()->attach($studentId, [
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Ghi danh thành công']);
    }

    /**
     * Unenroll current or specified student from class (optional helper).
     */
    public function unenroll(Request $request, $classId)
    {
        $class = ClassRoom::findOrFail($classId);
        $user = auth()->user();
        $studentId = $request->input('student_id', $user->id);

        if ($user->isStudent() && $studentId !== $user->id) {
            return response()->json(['message' => 'Bạn không thể hủy ghi danh người khác'], 403);
        }

        if (!$class->students()->where('users.id', $studentId)->exists()) {
            return response()->json(['message' => 'Chưa ghi danh'], 404);
        }

        $class->students()->detach($studentId);

        return response()->json(['message' => 'Đã hủy ghi danh']);
    }
}
