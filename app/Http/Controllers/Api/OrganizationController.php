<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\QrCode;
use App\Models\RegistrationCode;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrganizationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Organization::query()->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:organizations,slug'],
            'type' => ['required', Rule::in(['center', 'teacher'])],
            'logo' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'description' => ['nullable', 'string'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended'])],
        ]);

        $organization = DB::transaction(function () use ($data) {
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']).'-'.Str::lower(Str::random(5));
            $organization = Organization::query()->create($data);

            Wallet::query()->create(['organization_id' => $organization->id]);
            Contract::query()->create([
                'organization_id' => $organization->id,
                'contract_type' => 'commission',
                'monthly_fee' => 0,
                'start_date' => now()->toDateString(),
                'is_active' => true,
            ]);

            $code = RegistrationCode::query()->create([
                'organization_id' => $organization->id,
                'code' => $this->generateCode('CTR'),
                'type' => 'student',
                'status' => 'active',
            ]);

            QrCode::query()->create([
                'organization_id' => $organization->id,
                'code_id' => $code->id,
                'qr_path' => 'qr-codes/'.$code->code.'.png',
            ]);

            return $organization->load('wallet', 'contracts', 'registrationCodes.qrCode');
        });

        return response()->json($organization, 201);
    }

    public function show(Organization $organization): JsonResponse
    {
        return response()->json($organization->load('users', 'courses', 'wallet', 'contracts'));
    }

    public function update(Request $request, Organization $organization): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('organizations', 'slug')->ignore($organization->id)],
            'type' => ['sometimes', Rule::in(['center', 'teacher'])],
            'logo' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'description' => ['nullable', 'string'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
        ]);

        $organization->update($data);

        return response()->json($organization->fresh());
    }

    public function destroy(Organization $organization): JsonResponse
    {
        $organization->delete();

        return response()->json(['message' => 'Organization deleted successfully.']);
    }

    private function generateCode(string $prefix): string
    {
        do {
            $code = $prefix.'-'.random_int(10000, 99999);
        } while (RegistrationCode::query()->where('code', $code)->exists());

        return $code;
    }
}
