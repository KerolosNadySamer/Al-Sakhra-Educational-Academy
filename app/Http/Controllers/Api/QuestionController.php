<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $question = Question::query()->create($this->validated($request));

        if ($request->filled('answers')) {
            $question->answers()->createMany($request->input('answers'));
        }

        return response()->json($question->load('answers'), 201);
    }

    public function update(Request $request, Question $question): JsonResponse
    {
        $question->update($this->validated($request, true));

        return response()->json($question->fresh('answers'));
    }

    public function destroy(Question $question): JsonResponse
    {
        $question->delete();

        return response()->json(['message' => 'Question deleted successfully.']);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'exam_id' => [$required, 'exists:exams,id'],
            'question_type' => [$required, Rule::in(['mcq', 'true_false', 'essay'])],
            'question_text' => [$required, 'string'],
            'marks' => [$required, 'numeric', 'min:0.5'],
            'order_number' => ['sometimes', 'integer', 'min:1'],
            'answers' => ['sometimes', 'array'],
            'answers.*.answer_text' => ['required_with:answers', 'string'],
            'answers.*.is_correct' => ['sometimes', 'boolean'],
        ]);
    }
}
