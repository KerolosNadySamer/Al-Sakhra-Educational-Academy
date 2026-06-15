<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $exam = Exam::query()->create($this->validated($request));

        return response()->json($exam, 201);
    }

    public function show(Exam $exam): JsonResponse
    {
        return response()->json($exam->load('questions.answers'));
    }

    public function update(Request $request, Exam $exam): JsonResponse
    {
        $exam->update($this->validated($request, true));

        return response()->json($exam->fresh('questions.answers'));
    }

    public function destroy(Exam $exam): JsonResponse
    {
        $exam->delete();

        return response()->json(['message' => 'Exam deleted successfully.']);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'course_id' => [$required, 'exists:courses,id'],
            'title' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => [$required, 'integer', 'min:1'],
            'total_marks' => [$required, 'numeric', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
