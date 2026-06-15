<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    public function record(Request $request, string $action, ?Model $auditable = null, array $metadata = []): AuditLog
    {
        $user = $request->user();

        return AuditLog::query()->create([
            'organization_id' => $user?->organization_id ?? $metadata['organization_id'] ?? null,
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'metadata' => $metadata,
        ]);
    }
}
