<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->with('organization', 'teachingSubjects', 'teachingGrades')
            ->whereIn('role', ['teacher', 'teacher_assistant']);

        if (! $request->user()->hasRole('super_admin')) {
            $query->where('organization_id', $request->user()->organization_id);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request, AuditLogService $auditLogService): JsonResponse
    {
        $data = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:255', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(['teacher', 'teacher_assistant'])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended'])],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['exists:subjects,id'],
            'grade_ids' => ['nullable', 'array'],
            'grade_ids.*' => ['exists:grades,id'],
        ]);

        if (! $request->user()->hasRole('super_admin')) {
            $data['organization_id'] = $request->user()->organization_id;
        }

        $teacher = User::query()->create([
            'organization_id' => $data['organization_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'status' => $data['status'] ?? 'active',
        ]);

        $teacher->assignRole($data['role']);
        $teacher->teachingSubjects()->sync($data['subject_ids'] ?? []);
        $teacher->teachingGrades()->sync($data['grade_ids'] ?? []);
        $auditLogService->record($request, 'teacher_created', $teacher);

        return response()->json($teacher->load('teachingSubjects', 'teachingGrades'), 201);
    }

    public function show(User $teacher): JsonResponse
    {
        abort_unless(in_array($teacher->role, ['teacher', 'teacher_assistant'], true), 404);

        return response()->json($teacher->load('organization', 'teachingSubjects', 'teachingGrades', 'courses'));
    }

    public function update(Request $request, User $teacher, AuditLogService $auditLogService): JsonResponse
    {
        abort_unless(in_array($teacher->role, ['teacher', 'teacher_assistant'], true), 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teacher->id)],
            'phone' => ['nullable', 'string', 'max:255', Rule::unique('users', 'phone')->ignore($teacher->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['exists:subjects,id'],
            'grade_ids' => ['nullable', 'array'],
            'grade_ids.*' => ['exists:grades,id'],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $teacher->update(collect($data)->except(['subject_ids', 'grade_ids'])->all());

        if (array_key_exists('subject_ids', $data)) {
            $teacher->teachingSubjects()->sync($data['subject_ids'] ?? []);
        }

        if (array_key_exists('grade_ids', $data)) {
            $teacher->teachingGrades()->sync($data['grade_ids'] ?? []);
        }

        $auditLogService->record($request, 'teacher_updated', $teacher);

        return response()->json($teacher->fresh('teachingSubjects', 'teachingGrades'));
    }

    public function destroy(Request $request, User $teacher, AuditLogService $auditLogService): JsonResponse
    {
        abort_unless(in_array($teacher->role, ['teacher', 'teacher_assistant'], true), 404);

        $auditLogService->record($request, 'teacher_deleted', $teacher);
        $teacher->delete();

        return response()->json(['message' => 'Teacher deleted successfully.']);
    }
}
