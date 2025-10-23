<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Evaluation;
use App\Models\User;
use App\Models\ClassRoom;

class EvaluationController extends Controller
{
    /**
     * Display a listing of evaluations.
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();
        $query = Evaluation::with(['student', 'teacher:id,name,avatar,role_id', 'teacher.role', 'classRoom' => function($q){
            $q->select('id','name','teacher_id');
        }, 'classRoom.teacher:id,name,avatar']);

        // Apply role-based access control
        if ($user->isStudent()) {
            // Students can only see:
            // 1. Evaluations they gave to teachers
            // 2. Evaluations in classes they are enrolled in
            $enrolledClassIds = $user->enrolledClasses()->pluck('class_rooms.id')->toArray();

            $query->where(function ($q) use ($user, $enrolledClassIds) {
                $q->where('student_id', $user->id)
                  ->orWhereIn('class_id', $enrolledClassIds);
            });
        } elseif ($user->isTeacher()) {
            // Teacher chỉ xem các đánh giá dành cho mình
            $query->where('teacher_id', $user->id);
        }
        // Admin can see all evaluations (no additional filter)

        // Filter by teacher
        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        // Filter by student
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        // Filter by class
        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        $evaluations = $query->paginate($request->get('per_page', 15));

        // Mask student info for teacher role inside paginated data structure
        $authUser = $user; // already have
        if ($authUser->isTeacher()) {
            $evaluations->getCollection()->transform(function($ev){
                unset($ev->student);
                $ev->student_id = null;
                $ev->anonymous = true;
                return $ev;
            });
        }

        return response()->json($evaluations);
    }

    /**
     * Store a newly created evaluation.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|exists:users,id',
            'class_id' => 'required|exists:class_rooms,id',
            // Đánh giá theo sao 1-5
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string',
            'criteria' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = auth()->user();

        // Chỉ học viên mới có thể đánh giá
        if (!$user->isStudent()) {
            return response()->json(['error' => 'Chỉ học viên mới có thể đánh giá'], 403);
        }

        // Kiểm tra học viên có trong lớp không
        $classRoom = ClassRoom::find($request->class_id);
        if (!$classRoom->students()->where('student_id', $user->id)->exists()) {
            return response()->json(['error' => 'Bạn không có trong lớp học này'], 403);
        }

        // Kiểm tra giảng viên có đúng với lớp học không
        if ($classRoom->teacher_id != $request->teacher_id) {
            return response()->json(['error' => 'Giảng viên không đúng với lớp học'], 400);
        }

        // Kiểm tra đã đánh giá chưa
        $existingEvaluation = Evaluation::where([
            'student_id' => $user->id,
            'teacher_id' => $request->teacher_id,
            'class_id' => $request->class_id
        ])->first();

        if ($existingEvaluation) {
            return response()->json(['error' => 'Bạn đã đánh giá giảng viên này trong lớp học này'], 400);
        }

        $evaluation = Evaluation::create([
            'student_id' => $user->id,
            'teacher_id' => $request->teacher_id,
            'class_id' => $request->class_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'criteria' => $request->criteria
        ]);

        return response()->json([
            'message' => 'Đánh giá thành công',
            'evaluation' => $evaluation->load(['student', 'teacher', 'classRoom'])
        ], 201);
    }

    /**
     * Display the specified evaluation.
     */
    public function show(string $id)
    {
        $evaluation = Evaluation::with(['student', 'teacher', 'classRoom'])->find($id);

        if (!$evaluation) {
            return response()->json(['error' => 'Không tìm thấy đánh giá'], 404);
        }

        $user = auth()->user();
        // Authorization check for teachers
        if ($evaluation->teacher_id !== $user->id && !$user->isAdmin() && $user->isTeacher()) {
            return response()->json(['error' => 'Bạn không có quyền xem đánh giá này'], 403);
        }

        $authUser = auth('api')->user();
        if ($authUser && $authUser->isTeacher()) {
            unset($evaluation->student);
            $evaluation->student_id = null;
            $evaluation->anonymous = true;
        }

        return response()->json($evaluation);
    }

    /**
     * Update the specified evaluation.
     */
    public function update(Request $request, string $id)
    {
        $evaluation = Evaluation::find($id);

        if (!$evaluation) {
            return response()->json(['error' => 'Không tìm thấy đánh giá'], 404);
        }

        $user = auth()->user();

        // Chỉ người tạo mới có thể sửa
        if ($evaluation->student_id !== $user->id) {
            return response()->json(['error' => 'Bạn không có quyền sửa đánh giá này'], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string',
            'criteria' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $evaluation->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
            'criteria' => $request->criteria
        ]);

        return response()->json([
            'message' => 'Cập nhật đánh giá thành công',
            'evaluation' => $evaluation->fresh()->load(['student', 'teacher', 'classRoom'])
        ]);
    }

    /**
     * Remove the specified evaluation.
     */
    public function destroy(string $id)
    {
        $evaluation = Evaluation::find($id);

        if (!$evaluation) {
            return response()->json(['error' => 'Không tìm thấy đánh giá'], 404);
        }

        $user = auth()->user();

        // Chỉ người tạo hoặc admin mới có thể xóa
        if ($evaluation->student_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['error' => 'Bạn không có quyền xóa đánh giá này'], 403);
        }

        $evaluation->delete();

        return response()->json(['message' => 'Xóa đánh giá thành công']);
    }

    /**
     * Get teacher's evaluation summary
     */
    public function teacherSummary(string $teacherId)
    {
        $teacher = User::find($teacherId);

        if (!$teacher || !$teacher->isTeacher()) {
            return response()->json(['error' => 'Không tìm thấy giảng viên'], 404);
        }

        $evaluations = Evaluation::where('teacher_id', $teacherId)->with(['student','classRoom'])->get();

        if ($evaluations->isEmpty()) {
            return response()->json([
                'teacher' => $teacher->load('role'),
                'total_evaluations' => 0,
                'average_rating' => 0,
                'evaluations' => []
            ]);
        }

        $averageRating = $evaluations->avg('rating');
        $totalEvaluations = $evaluations->count();

        // If the authenticated user is the teacher themself, mask student identities
        $authUser = auth('api')->user();
        if ($authUser && $authUser->id === $teacher->id) {
            $evaluations->transform(function($ev){
                unset($ev->student);
                $ev->student_id = null;
                $ev->anonymous = true;
                return $ev;
            });
        }

        return response()->json([
            'teacher' => $teacher->load('role'),
            'total_evaluations' => $totalEvaluations,
            'average_rating' => round($averageRating, 2),
            'evaluations' => $evaluations
        ]);
    }

    /**
     * Get my evaluation for a class
     */
    public function myEvaluation($classId)
    {
        $user = auth('api')->user();
        if (!$user->isStudent()) {
            return response()->json(['error' => 'Chỉ học viên mới xem đánh giá cá nhân'], 403);
        }
        $class = ClassRoom::find($classId);
        if (!$class) {
            return response()->json(['error' => 'Không tìm thấy lớp học'], 404);
        }
        // ensure enrolled
        if (!$class->students()->where('student_id', $user->id)->exists()) {
            return response()->json(['error' => 'Bạn không thuộc lớp này'], 403);
        }
        $evaluation = Evaluation::where('class_id', $classId)
            ->where('student_id', $user->id)
            ->where('teacher_id', $class->teacher_id)
            ->first();
        if ($evaluation) {
            $evaluation->load(['teacher']);
        }
        return response()->json(['evaluation' => $evaluation]);
    }

    private function maskStudentIfTeacher($user, $evaluation)
    {
        if ($user->isTeacher()) {
            unset($evaluation->student); // remove relation
            $evaluation->student_id = null;
            $evaluation->anonymous = true;
        }
        return $evaluation;
    }

    private function maskCollectionIfTeacher($user, $collection)
    {
        if ($user->isTeacher()) {
            return $collection->map(function($ev){
                unset($ev->student);
                $ev->student_id = null;
                $ev->anonymous = true;
                return $ev;
            });
        }
        return $collection;
    }
}
