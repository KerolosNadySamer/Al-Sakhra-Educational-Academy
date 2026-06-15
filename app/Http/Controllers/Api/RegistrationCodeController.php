<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Models\RegistrationCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RegistrationCodeController extends Controller
{
    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'type' => ['required', Rule::in(['student', 'teacher_assistant', 'cashier'])],
            'expires_at' => ['nullable', 'date'],
        ]);

        $registrationCode = DB::transaction(function () use ($data) {
            $code = RegistrationCode::query()->create([
                'organization_id' => $data['organization_id'],
                'code' => $this->generateCode(),
                'type' => $data['type'],
                'status' => 'active',
                'expires_at' => $data['expires_at'] ?? null,
            ]);

            QrCode::query()->create([
                'organization_id' => $data['organization_id'],
                'code_id' => $code->id,
                'qr_path' => 'qr-codes/'.$code->code.'.png',
            ]);

            return $code->load('organization', 'qrCode');
        });

        return response()->json($registrationCode, 201);
    }

    public function validateCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $code = RegistrationCode::query()->with('organization')->where('code', $data['code'])->first();

        if (! $code || $code->status !== 'active' || ($code->expires_at && $code->expires_at->isPast())) {
            return response()->json(['valid' => false, 'message' => 'Registration code is invalid.'], 422);
        }

        return response()->json(['valid' => true, 'registration_code' => $code]);
    }

    public function qr(RegistrationCode $registrationCode): JsonResponse
    {
        return response()->json($registrationCode->load('qrCode'));
    }

    private function generateCode(): string
    {
        do {
            $code = 'CTR-'.random_int(10000, 99999);
        } while (RegistrationCode::query()->where('code', $code)->exists());

        return $code;
    }
}
