<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Video;
use App\Services\AuditLogService;
use App\Services\VideoSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoSecurityController extends Controller
{
    public function signedUrl(Request $request, Video $video, VideoSecurityService $videoSecurityService, AuditLogService $auditLogService): JsonResponse
    {
        $video->load('lesson.course');
        $user = $request->user();

        $enrolled = Enrollment::query()
            ->where('student_id', $user->id)
            ->where('course_id', $video->lesson->course_id)
            ->where('status', 'active')
            ->exists();

        if (! $enrolled && ! $video->lesson->is_free && ! $user->hasAnyRole(['super_admin', 'center_owner', 'center_admin', 'teacher'])) {
            abort(403, 'You are not allowed to watch this video.');
        }

        $auditLogService->record($request, 'video_signed_url_created', $video);

        return response()->json([
            'provider' => $video->provider,
            'expires_in' => 300,
            'stream_url' => $videoSecurityService->signedStreamUrl($video),
        ]);
    }
}
