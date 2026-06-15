<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Course::query()->with('organization', 'teacher', 'subject', 'grade', 'settings');

        if (! $request->user()?->hasRole('super_admin')) {
            $query->where('organization_id', $request->user()->organization_id);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        if (! $request->user()?->hasRole('super_admin')) {
            $data['organization_id'] = $request->user()->organization_id;
        }

        $course = Course::query()->create($data);
        $course->settings()->create([
            'allow_multiple_devices' => false,
            'max_devices' => 1,
            'watermark_enabled' => true,
            'allow_pdf_download' => false,
        ]);

        return response()->json($course->load('settings'), 201);
    }

    public function show(Course $course): JsonResponse
    {
        return response()->json($course->load('lessons.video', 'lessons.files', 'exams.questions.answers'));
    }

    public function update(Request $request, Course $course): JsonResponse
    {
        $course->update($this->validated($request, true));

        return response()->json($course->fresh('settings'));
    }

    public function destroy(Course $course): JsonResponse
    {
        $course->delete();

        return response()->json(['message' => 'Course deleted successfully.']);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'organization_id' => [$required, 'exists:organizations,id'],
            'teacher_id' => [$required, 'exists:users,id'],
            'subject_id' => [$required, 'exists:subjects,id'],
            'grade_id' => [$required, 'exists:grades,id'],
            'title' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => [$partial ? 'sometimes' : 'nullable', 'numeric', 'min:0'],
            'thumbnail' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
