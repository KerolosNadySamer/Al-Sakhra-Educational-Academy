<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->hasRole('super_admin')) {
            return $next($request);
        }

        $organizationId = $request->route('organization')?->id
            ?? $request->route('course')?->organization_id
            ?? $request->route('lesson')?->course?->organization_id
            ?? $request->input('organization_id');

        if ($organizationId && (int) $organizationId !== (int) $user->organization_id) {
            abort(403, 'You do not have access to this organization data.');
        }

        return $next($request);
    }
}
