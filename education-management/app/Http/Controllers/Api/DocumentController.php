<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use App\Models\ClassRoom;

class DocumentController extends Controller
{
    /**
     * Display a listing of documents.
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();
        $query = Document::with(['uploader', 'classRoom']);

        // Apply role-based access control
        if ($user->isStudent()) {
            // Students can only see:
            // 1. Public documents
            // 2. Documents from classes they are enrolled in
            $enrolledClassIds = $user->enrolledClasses()->pluck('class_rooms.id')->toArray();

            $query->where(function ($q) use ($enrolledClassIds) {
                $q->where('is_public', true)
                  ->orWhereIn('class_id', $enrolledClassIds);
            });
        } elseif ($user->isTeacher()) {
            // Teachers can see:
            // 1. Public documents
            // 2. Documents from classes they teach
            // 3. Documents they uploaded
            $teachingClassIds = $user->teachingClasses()->pluck('id')->toArray();

            $query->where(function ($q) use ($teachingClassIds, $user) {
                $q->where('is_public', true)
                  ->orWhereIn('class_id', $teachingClassIds)
                  ->orWhere('uploaded_by', $user->id);
            });
        }
        // Admin can see all documents (no additional filter)

        // Filter by class
        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        // Filter public documents
        if ($request->has('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        }

        // Search by title
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $documents = $query->paginate($request->get('per_page', 15));

        return response()->json($documents);
    }

    /**
     * Store a newly created document.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|max:10240', // Max 10MB
            'class_id' => 'nullable|exists:class_rooms,id',
            'is_public' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = auth('api')->user();

        // Kiểm tra quyền upload vào lớp
        if ($request->class_id) {
            $classRoom = ClassRoom::find($request->class_id);

            // Chỉ giảng viên của lớp hoặc admin mới được upload vào lớp
            if (!$user->isAdmin() && $classRoom->teacher_id !== $user->id) {
                return response()->json(['error' => 'Bạn không có quyền upload tài liệu vào lớp này'], 403);
            }
        }

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('documents', $fileName, 'public');

        $document = Document::create([
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => $user->id,
            'class_id' => $request->class_id,
            'is_public' => $request->boolean('is_public', false)
        ]);

        return response()->json([
            'message' => 'Upload tài liệu thành công',
            'document' => $document->load(['uploader', 'classRoom'])
        ], 201);
    }

    /**
     * Display the specified document.
     */
    public function show(string $id)
    {
        $document = Document::with(['uploader', 'classRoom'])->find($id);

        if (!$document) {
            return response()->json(['error' => 'Không tìm thấy tài liệu'], 404);
        }

        return response()->json($document);
    }

    /**
     * Update the specified document.
     */
    public function update(Request $request, string $id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json(['error' => 'Không tìm thấy tài liệu'], 404);
        }

        $user = auth('api')->user();

        // Chỉ người upload hoặc admin mới có thể sửa
        if ($document->uploaded_by !== $user->id && !$user->isAdmin()) {
            return response()->json(['error' => 'Bạn không có quyền sửa tài liệu này'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'class_id' => 'nullable|exists:class_rooms,id',
            'is_public' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $document->update([
            'title' => $request->title,
            'description' => $request->description,
            'class_id' => $request->class_id,
            'is_public' => $request->boolean('is_public')
        ]);

        return response()->json([
            'message' => 'Cập nhật tài liệu thành công',
            'document' => $document->fresh()->load(['uploader', 'classRoom'])
        ]);
    }

    /**
     * Remove the specified document.
     */
    public function destroy(string $id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json(['error' => 'Không tìm thấy tài liệu'], 404);
        }

        $user = auth('api')->user();

        // Chỉ người upload hoặc admin mới có thể xóa
        if ($document->uploaded_by !== $user->id && !$user->isAdmin()) {
            return response()->json(['error' => 'Bạn không có quyền xóa tài liệu này'], 403);
        }

        // Xóa file vật lý
        Storage::disk('public')->delete($document->file_path);

        $document->delete();

        return response()->json(['message' => 'Xóa tài liệu thành công']);
    }

    /**
     * Download document
     */
    public function download(string $id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json(['error' => 'Không tìm thấy tài liệu'], 404);
        }

        $user = auth('api')->user();

        // Kiểm tra quyền download
        if (!$document->is_public) {
            // Nếu tài liệu không public, kiểm tra quyền
            if ($document->uploaded_by !== $user->id && !$user->isAdmin()) {
                // Kiểm tra có trong lớp học không (nếu tài liệu thuộc lớp)
                if ($document->class_id) {
                    $classRoom = ClassRoom::find($document->class_id);
                    $isInClass = $classRoom->teacher_id === $user->id ||
                               $classRoom->students()->where('student_id', $user->id)->exists();

                    if (!$isInClass) {
                        return response()->json(['error' => 'Bạn không có quyền download tài liệu này'], 403);
                    }
                } else {
                    return response()->json(['error' => 'Bạn không có quyền download tài liệu này'], 403);
                }
            }
        }

        $filePath = storage_path('app/public/' . $document->file_path);

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File không tồn tại'], 404);
        }

        return response()->download($filePath, $document->file_name);
    }
}
