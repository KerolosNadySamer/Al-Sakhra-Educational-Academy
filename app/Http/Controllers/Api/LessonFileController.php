<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\FileDownload;
use App\Models\LessonFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonFileController extends Controller
{
    public function download(Request $request, LessonFile $lessonFile): JsonResponse
    {
        $user = $request->user();
        $lessonFile->load('lesson.course');

        $enrolled = Enrollment::query()
            ->where('student_id', $user->id)
            ->where('course_id', $lessonFile->lesson->course_id)
            ->where('status', 'active')
            ->exists();

        if (! $enrolled && ! $lessonFile->lesson->is_free) {
            abort(403, 'You are not enrolled in this course.');
        }

        if (! $lessonFile->allow_download) {
            abort(403, 'Downloading this file is disabled.');
        }

        $download = FileDownload::query()->firstOrCreate(
            ['student_id' => $user->id, 'lesson_file_id' => $lessonFile->id],
            ['download_count' => 0, 'first_download_at' => now()]
        );

        if ($download->download_count >= $lessonFile->download_limit) {
            abort(403, 'Download limit exceeded.');
        }

        if ($download->first_download_at && $download->first_download_at->copy()->addDays($lessonFile->expiry_days)->isPast()) {
            abort(403, 'Download period expired.');
        }

        $download->increment('download_count');
        $download->update(['last_download_at' => now()]);

        return response()->json([
            'message' => 'Download allowed.',
            'file_path' => $lessonFile->file_path,
            'download' => $download->fresh(),
        ]);
    }
}
