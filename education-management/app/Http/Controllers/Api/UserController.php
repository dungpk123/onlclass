<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with('role');

        // Filter by role
        if ($request->has('role')) {
            $query->whereHas('role', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:admin,student,teacher',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        $role = Role::where('name', $request->role)->first();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return response()->json([
            'message' => 'Tạo người dùng thành công',
            'user' => $user->load('role')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with('role')->find($id);

        if (!$user) {
            return response()->json(['error' => 'Không tìm thấy người dùng'], 404);
        }

        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Không tìm thấy người dùng'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|string|in:admin,student,teacher',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        $role = Role::where('name', $request->role)->first();

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $role->id,
            'phone' => $request->phone,
            'address' => $request->address,
        ];

        if ($request->password) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json([
            'message' => 'Cập nhật người dùng thành công',
            'user' => $user->fresh()->load('role')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Không tìm thấy người dùng'], 404);
        }

        // Không cho phép xóa admin
        if ($user->isAdmin()) {
            return response()->json(['error' => 'Không thể xóa tài khoản admin'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Xóa người dùng thành công']);
    }

    /**
     * Get teachers list
     */
    public function teachers(Request $request)
    {
        $user = auth('api')->user();
        $query = User::whereHas('role', function ($q) {
            $q->where('name', 'teacher');
        })->with('role');

        // If student requests teachers, only return teachers from enrolled classes
        if ($request->has('enrolled') && $request->boolean('enrolled') && $user->isStudent()) {
            $enrolledTeacherIds = $user->enrolledClasses()
                ->with('teacher')
                ->get()
                ->pluck('teacher.id')
                ->unique()
                ->filter(); // Remove nulls

            $query->whereIn('id', $enrolledTeacherIds);
        }

        $teachers = $query->get();

        return response()->json($teachers);
    }

    /**
     * Get students list
     */
    public function students()
    {
        $students = User::whereHas('role', function ($query) {
            $query->where('name', 'student');
        })->with('role')->get();

        return response()->json($students);
    }



    /**
     * Get current user profile
     */
    public function profile()
    {
        $user = auth()->user()->load('role');
        return response()->json($user);
    }

    /**
     * Update current user profile
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users,email,' . $user->id,
            'current_password' => 'required_with:password|string',
            'password' => 'nullable|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        // Verify current password if changing password
        if ($request->password) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['current_password' => ['Mật khẩu hiện tại không đúng']], 400);
            }
        }

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
        ];

        if ($request->password) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json([
            'message' => 'Cập nhật thông tin thành công',
            'user' => $user->fresh()->load('role')
        ]);
    }
}
