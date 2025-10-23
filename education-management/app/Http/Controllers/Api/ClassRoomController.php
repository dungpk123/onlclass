<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ClassRoom;
use App\Models\User;

class ClassRoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();
        $query = ClassRoom::with(['teacher', 'students']);

        // Teachers can only see their own classes
        if ($user->isTeacher()) {
            $query->where('teacher_id', $user->id);
        }

        // Filter by teacher
        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        // Filter by enrolled classes (for students)
        if ($request->has('enrolled') && $request->boolean('enrolled')) {
            if ($user->isStudent()) {
                $enrolledClassIds = $user->enrolledClasses()->pluck('class_rooms.id')->toArray();
                $query->whereIn('id', $enrolledClassIds);
            }
        }

        // Search by name or subject
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        // Filter by status (upcoming, ongoing, completed)
        if ($request->has('status') && $request->status !== '') {
            $status = $request->status;
            $today = now()->startOfDay();
            switch ($status) {
                case 'upcoming':
                    // classes that haven't started yet
                    $query->whereDate('start_date', '>', $today);
                    break;
                case 'ongoing':
                    // classes currently happening
                    $query->whereDate('start_date', '<=', $today)
                          ->whereDate('end_date', '>=', $today);
                    break;
                case 'completed':
                    // (Not currently used by frontend, but added for completeness)
                    $query->whereDate('end_date', '<', $today);
                    break;
            }
        }

        // Default ordering (prioritize upcoming earliest, then ongoing, then others)
        $query->orderBy('start_date', 'asc');

        $classes = $query->paginate($request->get('per_page', 15));

        return response()->json($classes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'teacher_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Kiểm tra teacher có đúng role không
        $teacher = User::find($request->teacher_id);
        if (!$teacher->isTeacher()) {
            return response()->json(['error' => 'User được chọn không phải là giảng viên'], 400);
        }

        $classRoom = ClassRoom::create($request->all());

        return response()->json([
            'message' => 'Tạo lớp học thành công',
            'class' => $classRoom->load(['teacher', 'students'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $classRoom = ClassRoom::with([
            'teacher',
            'students',
            'documents.uploader',
            'evaluations'
        ])->find($id);

        if (!$classRoom) {
            return response()->json(['error' => 'Không tìm thấy lớp học'], 404);
        }

        $user = auth('api')->user();
        if ($user->isTeacher() && $classRoom->teacher_id !== $user->id) {
            return response()->json(['error' => 'Bạn không có quyền truy cập lớp học này'], 403);
        }

        return response()->json($classRoom);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $classRoom = ClassRoom::find($id);

        if (!$classRoom) {
            return response()->json(['error' => 'Không tìm thấy lớp học'], 404);
        }

        $user = auth('api')->user();
        if ($user->isTeacher() && $classRoom->teacher_id !== $user->id) {
            return response()->json(['error' => 'Bạn không thể cập nhật lớp học này'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'teacher_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Kiểm tra teacher có đúng role không
        $teacher = User::find($request->teacher_id);
        if (!$teacher->isTeacher()) {
            return response()->json(['error' => 'User được chọn không phải là giảng viên'], 400);
        }

        $classRoom->update($request->all());

        return response()->json([
            'message' => 'Cập nhật lớp học thành công',
            'class' => $classRoom->fresh()->load(['teacher', 'students'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $classRoom = ClassRoom::find($id);

        if (!$classRoom) {
            return response()->json(['error' => 'Không tìm thấy lớp học'], 404);
        }

        $user = auth('api')->user();
        if ($user->isTeacher() && $classRoom->teacher_id !== $user->id) {
            return response()->json(['error' => 'Bạn không thể xóa lớp học này'], 403);
        }

        $classRoom->delete();

        return response()->json(['message' => 'Xóa lớp học thành công']);
    }

    /**
     * Enroll current user (student) to class
     */
    public function enrollSelf(Request $request, string $id)
    {
        $user = auth('api')->user();

        if (!$user->isStudent()) {
            return response()->json(['error' => 'Chỉ học viên mới có thể đăng ký lớp học'], 403);
        }

        $classRoom = ClassRoom::find($id);
        if (!$classRoom) {
            return response()->json(['error' => 'Không tìm thấy lớp học'], 404);
        }

        // Kiểm tra đã đăng ký chưa
        if ($classRoom->students()->where('users.id', $user->id)->exists()) {
            return response()->json(['error' => 'Bạn đã đăng ký lớp học này rồi'], 400);
        }

        // Kiểm tra số lượng
        if ($classRoom->students()->count() >= $classRoom->capacity) {
            return response()->json(['error' => 'Lớp học đã đầy'], 400);
        }

        $classRoom->students()->attach($user->id);

        return response()->json(['message' => 'Đăng ký lớp học thành công']);
    }

    /**
     * Enroll student to class (Admin/Teacher only)
     */
    public function enrollStudent(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $classRoom = ClassRoom::find($id);
        if (!$classRoom) {
            return response()->json(['error' => 'Không tìm thấy lớp học'], 404);
        }

        $user = auth('api')->user();
        if ($user->isTeacher() && $classRoom->teacher_id !== $user->id) {
            return response()->json(['error' => 'Bạn không thể thêm học viên vào lớp này'], 403);
        }

        $student = User::find($request->student_id);
        if (!$student->isStudent()) {
            return response()->json(['error' => 'User được chọn không phải là học viên'], 400);
        }

        // Kiểm tra đã đăng ký chưa
        if ($classRoom->students()->where('users.id', $student->id)->exists()) {
            return response()->json(['error' => 'Học viên đã được đăng ký vào lớp này'], 400);
        }

        // Kiểm tra số lượng
        if ($classRoom->students()->count() >= $classRoom->capacity) {
            return response()->json(['error' => 'Lớp học đã đầy'], 400);
        }

        $classRoom->students()->attach($student->id);

        return response()->json(['message' => 'Đăng ký học viên vào lớp thành công']);
    }

    /**
     * Remove current user (student) from class
     */
    public function leaveSelf(Request $request, string $id)
    {
        $user = auth('api')->user();

        if (!$user->isStudent()) {
            return response()->json(['error' => 'Chỉ học viên mới có thể rút khỏi lớp học'], 403);
        }

        $classRoom = ClassRoom::find($id);
        if (!$classRoom) {
            return response()->json(['error' => 'Không tìm thấy lớp học'], 404);
        }

        $classRoom->students()->detach($user->id);

        return response()->json(['message' => 'Rút khỏi lớp học thành công']);
    }

    /**
     * Remove student from class (Admin/Teacher only)
     */
    public function removeStudent(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $classRoom = ClassRoom::find($id);
        if (!$classRoom) {
            return response()->json(['error' => 'Không tìm thấy lớp học'], 404);
        }

        $user = auth('api')->user();
        if ($user->isTeacher() && $classRoom->teacher_id !== $user->id) {
            return response()->json(['error' => 'Bạn không thể hủy học viên lớp này'], 403);
        }

        $classRoom->students()->detach($request->student_id);

        return response()->json(['message' => 'Hủy đăng ký học viên khỏi lớp thành công']);
    }
}
