<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function ensureWallet(Organization $organization): Wallet
    {
        return Wallet::query()->firstOrCreate(
            ['organization_id' => $organization->id],
            ['available_balance' => 0, 'pending_balance' => 0, 'withdrawn_balance' => 0]
        );
    }

    public function credit(Organization $organization, float $amount, ?string $description = null): Wallet
    {
        return DB::transaction(function () use ($organization, $amount, $description) {
            $wallet = $this->ensureWallet($organization);
            $wallet->increment('available_balance', $amount);
            $wallet->transactions()->create([
                'type' => 'credit',
                'amount' => $amount,
                'description' => $description,
            ]);

            return $wallet->fresh('transactions');
        });
    }

    public function debit(Wallet $wallet, float $amount, ?string $description = null): Wallet
    {
        return DB::transaction(function () use ($wallet, $amount, $description) {
            $wallet->decrement('available_balance', $amount);
            $wallet->increment('withdrawn_balance', $amount);
            $wallet->transactions()->create([
                'type' => 'debit',
                'amount' => $amount,
                'description' => $description,
            ]);

            return $wallet->fresh('transactions');
        });
    }
}
