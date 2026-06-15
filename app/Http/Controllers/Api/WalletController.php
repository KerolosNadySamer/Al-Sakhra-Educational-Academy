<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Wallet::query()->with('organization', 'transactions', 'withdrawRequests');

        if (! $request->user()->hasRole('super_admin')) {
            $query->where('organization_id', $request->user()->organization_id);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function show(Request $request, Wallet $wallet): JsonResponse
    {
        if (! $request->user()->hasRole('super_admin') && (int) $wallet->organization_id !== (int) $request->user()->organization_id) {
            abort(403, 'You do not have access to this wallet.');
        }

        return response()->json($wallet->load('organization', 'transactions', 'withdrawRequests'));
    }
}
