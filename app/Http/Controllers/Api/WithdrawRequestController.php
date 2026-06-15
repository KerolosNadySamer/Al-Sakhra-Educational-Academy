<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WithdrawRequest;
use App\Services\AuditLogService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WithdrawRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WithdrawRequest::query()->with('wallet.organization');

        if (! $request->user()->hasRole('super_admin')) {
            $query->whereHas('wallet', fn ($wallet) => $wallet->where('organization_id', $request->user()->organization_id));
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request, AuditLogService $auditLogService): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $wallet = Wallet::query()->where('organization_id', $request->user()->organization_id)->firstOrFail();

        if ((float) $wallet->available_balance < (float) $data['amount']) {
            abort(422, 'Insufficient wallet balance.');
        }

        $withdrawRequest = $wallet->withdrawRequests()->create([
            'amount' => $data['amount'],
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);

        $auditLogService->record($request, 'withdraw_request_created', $withdrawRequest);

        return response()->json($withdrawRequest, 201);
    }

    public function update(Request $request, WithdrawRequest $withdrawRequest, WalletService $walletService, AuditLogService $auditLogService): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected', 'paid'])],
            'notes' => ['nullable', 'string'],
        ]);

        $withdrawRequest->load('wallet.organization');
        $oldStatus = $withdrawRequest->status;
        $withdrawRequest->update($data);

        if ($oldStatus !== 'paid' && $data['status'] === 'paid') {
            $walletService->debit($withdrawRequest->wallet, (float) $withdrawRequest->amount, 'Withdraw request #'.$withdrawRequest->id);
        }

        $auditLogService->record($request, 'withdraw_request_updated', $withdrawRequest, ['status' => $data['status']]);

        return response()->json($withdrawRequest->fresh('wallet'));
    }
}
