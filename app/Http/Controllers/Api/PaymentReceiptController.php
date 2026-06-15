<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentReceipt;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentReceiptController extends Controller
{
    public function store(Request $request, AuditLogService $auditLogService): JsonResponse
    {
        $data = $request->validate([
            'payment_id' => ['required', 'exists:payments,id'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'receipt_path' => ['nullable', 'string', 'max:255'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $receipt = PaymentReceipt::query()->create($data);
        $auditLogService->record($request, 'payment_receipt_created', $receipt);

        return response()->json($receipt, 201);
    }

    public function update(Request $request, PaymentReceipt $paymentReceipt, AuditLogService $auditLogService): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'notes' => ['nullable', 'string'],
        ]);

        $paymentReceipt->update($data);
        $auditLogService->record($request, 'payment_receipt_updated', $paymentReceipt, ['status' => $data['status']]);

        return response()->json($paymentReceipt->fresh());
    }
}
