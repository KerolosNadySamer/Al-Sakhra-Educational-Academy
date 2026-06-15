<?php

namespace App\Services;

use App\Models\Video;

class VideoSecurityService
{
    public function signedStreamUrl(Video $video, int $ttlSeconds = 300): string
    {
        $expires = now()->addSeconds($ttlSeconds)->timestamp;
        $secret = config('services.video_signing.secret', config('app.key'));
        $baseUrl = rtrim((string) config('services.video_signing.base_url'), '/');
        $path = '/video/'.$video->provider_video_id.'.m3u8';
        $token = hash_hmac('sha256', $video->provider_video_id.'|'.$expires, $secret);

        return $baseUrl.$path.'?token='.$token.'&expires='.$expires;
    }
}
