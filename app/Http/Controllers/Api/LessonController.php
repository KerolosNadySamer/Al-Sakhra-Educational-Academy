<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $lesson = Lesson::query()->create($this->validated($request));

        return response()->json($lesson, 201);
    }

    public function update(Request $request, Lesson $lesson): JsonResponse
    {
        $lesson->update($this->validated($request, true));

        return response()->json($lesson->fresh('video', 'files'));
    }

    public function destroy(Lesson $lesson): JsonResponse
    {
        $lesson->delete();

        return response()->json(['message' => 'Lesson deleted successfully.']);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'course_id' => [$required, 'exists:courses,id'],
            'title' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order_number' => ['sometimes', 'integer', 'min:1'],
            'is_free' => ['sometimes', 'boolean'],
        ]);
    }
}
