<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->with('organization', 'student.grade', 'enrollments.course')
            ->where('role', 'student');

        if (! $request->user()->hasRole('super_admin')) {
            $query->where('organization_id', $request->user()->organization_id);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request, AuditLogService $auditLogService): JsonResponse
    {
        $data = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'grade_id' => ['required', 'exists:grades,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:255', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
            'parent_phone' => ['nullable', 'string', 'max:255'],
            'language_preference' => ['nullable', Rule::in(['ar', 'en'])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended'])],
        ]);

        if (! $request->user()->hasRole('super_admin')) {
            $data['organization_id'] = $request->user()->organization_id;
        }

        $student = User::query()->create([
            'organization_id' => $data['organization_id'],
            'name' => $data['name'],
            'email' => $data['email'] ?? $data['phone'].'@students.local',
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'student',
            'status' => $data['status'] ?? 'active',
        ]);

        Student::query()->create([
            'organization_id' => $data['organization_id'],
            'user_id' => $student->id,
            'grade_id' => $data['grade_id'],
            'parent_phone' => $data['parent_phone'] ?? null,
            'language_preference' => $data['language_preference'] ?? 'ar',
        ]);

        $student->assignRole('student');
        $auditLogService->record($request, 'student_created', $student);

        return response()->json($student->load('student.grade'), 201);
    }

    public function show(User $student): JsonResponse
    {
        abort_unless($student->role === 'student', 404);

        return response()->json($student->load('organization', 'student.grade', 'enrollments.course', 'devices'));
    }

    public function update(Request $request, User $student, AuditLogService $auditLogService): JsonResponse
    {
        abort_unless($student->role === 'student', 404);

        $data = $request->validate([
            'grade_id' => ['sometimes', 'exists:grades,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student->id)],
            'phone' => ['sometimes', 'string', 'max:255', Rule::unique('users', 'phone')->ignore($student->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'parent_phone' => ['nullable', 'string', 'max:255'],
            'language_preference' => ['nullable', Rule::in(['ar', 'en'])],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
        ]);

        $userData = collect($data)->only(['name', 'email', 'phone', 'status'])->all();

        if (! empty($data['password'])) {
            $userData['password'] = Hash::make($data['password']);
        }

        $student->update($userData);
        $student->student?->update(collect($data)->only(['grade_id', 'parent_phone', 'language_preference'])->all());
        $auditLogService->record($request, 'student_updated', $student);

        return response()->json($student->fresh('student.grade'));
    }
}
