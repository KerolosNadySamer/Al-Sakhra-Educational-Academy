<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function check(Request $request, AuditLogService $auditLogService): JsonResponse
    {
        $data = $request->validate([
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_identifier' => ['required', 'string', 'max:255'],
            'max_devices' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $user = $request->user();
        $maxDevices = $data['max_devices'] ?? 2;
        $device = $user->devices()->where('device_identifier', $data['device_identifier'])->first();

        if (! $device && $user->devices()->where('is_active', true)->count() >= $maxDevices) {
            $auditLogService->record($request, 'device_rejected', null, ['device_identifier' => $data['device_identifier']]);
            abort(403, 'Maximum number of active devices reached.');
        }

        $device = $user->devices()->updateOrCreate(
            ['device_identifier' => $data['device_identifier']],
            [
                'device_name' => $data['device_name'] ?? null,
                'last_login_at' => now(),
                'is_active' => true,
            ]
        );

        $auditLogService->record($request, 'device_checked', $device);

        return response()->json([
            'message' => 'Device allowed.',
            'device' => $device,
            'active_devices' => $user->devices()->where('is_active', true)->count(),
            'max_devices' => $maxDevices,
        ]);
    }
}
