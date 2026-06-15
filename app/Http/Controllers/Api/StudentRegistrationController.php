<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\RegistrationCode;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StudentRegistrationController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
            'registration_code' => ['required', 'string'],
            'grade_id' => ['nullable', 'exists:grades,id'],
            'parent_phone' => ['nullable', 'string', 'max:255'],
            'language_preference' => ['nullable', 'in:ar,en'],
        ]);

        $result = DB::transaction(function () use ($data) {
            $code = RegistrationCode::query()
                ->where('code', $data['registration_code'])
                ->lockForUpdate()
                ->first();

            if (! $code || $code->type !== 'student' || $code->status !== 'active' || ($code->expires_at && $code->expires_at->isPast())) {
                throw ValidationException::withMessages(['registration_code' => ['Registration code is invalid.']]);
            }

            $gradeId = $data['grade_id'] ?? Grade::query()
                ->where('organization_id', $code->organization_id)
                ->orderBy('order_number')
                ->value('id');

            if (! $gradeId) {
                throw ValidationException::withMessages(['grade_id' => ['A grade is required for this organization.']]);
            }

            $user = User::query()->create([
                'organization_id' => $code->organization_id,
                'name' => $data['name'],
                'email' => $data['phone'].'@students.local',
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'role' => 'student',
                'status' => 'active',
            ]);

            Student::query()->create([
                'organization_id' => $code->organization_id,
                'user_id' => $user->id,
                'grade_id' => $gradeId,
                'parent_phone' => $data['parent_phone'] ?? null,
                'language_preference' => $data['language_preference'] ?? 'ar',
            ]);

            $user->assignRole('student');

            $code->update([
                'status' => 'used',
                'used_by' => $user->id,
            ]);

            return [
                'user' => $user->load('organization', 'student.grade', 'roles'),
                'token' => $user->createToken('student-registration')->plainTextToken,
            ];
        });

        return response()->json($result, 201);
    }
}
