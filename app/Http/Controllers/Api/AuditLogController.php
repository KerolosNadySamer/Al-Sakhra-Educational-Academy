<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()->with('organization', 'user')->latest();

        if (! $request->user()->hasRole('super_admin')) {
            $query->where('organization_id', $request->user()->organization_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        return response()->json($query->paginate(30));
    }
}
