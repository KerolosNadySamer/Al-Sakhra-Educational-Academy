<?php

namespace App\Services;

use App\Models\CommissionSetting;

class CommissionService
{
    public function courseCommissionPercentage(): float
    {
        $settings = CommissionSetting::query()->latest('id')->first();

        return (float) ($settings?->course_commission ?? 25);
    }

    public function split(float $amount, ?float $percentage = null): array
    {
        $commission = $percentage ?? $this->courseCommissionPercentage();
        $platformAmount = round($amount * ($commission / 100), 2);

        return [
            'commission_percentage' => $commission,
            'platform_amount' => $platformAmount,
            'owner_amount' => round($amount - $platformAmount, 2),
        ];
    }
}
