<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_identifier' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()
            ->where('email', $data['login'])
            ->orWhere('phone', $data['login'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => ['Invalid credentials.']]);
        }

        if ($user->status !== 'active') {
            abort(403, 'User account is not active.');
        }

        $this->recordDevice($user, $data);

        return response()->json([
            'user' => $user->load('organization', 'roles'),
            'token' => $user->createToken($data['device_name'] ?? 'api-token')->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('organization', 'roles', 'permissions'));
    }

    private function recordDevice(User $user, array $data): void
    {
        if (empty($data['device_identifier'])) {
            return;
        }

        $maxDevices = 2;
        $device = $user->devices()->where('device_identifier', $data['device_identifier'])->first();

        if (! $device && $user->devices()->where('is_active', true)->count() >= $maxDevices) {
            abort(403, 'Maximum number of active devices reached.');
        }

        $user->devices()->updateOrCreate(
            ['device_identifier' => $data['device_identifier']],
            [
                'device_name' => $data['device_name'] ?? null,
                'last_login_at' => now(),
                'is_active' => true,
            ]
        );
    }
}
