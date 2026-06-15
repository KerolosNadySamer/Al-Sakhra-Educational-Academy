<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private CommissionService $commissionService,
        private WalletService $walletService
    ) {
    }

    public function createCoursePayment(User $student, Course $course, string $paymentMethod, string $status = 'paid'): Payment
    {
        return DB::transaction(function () use ($student, $course, $paymentMethod, $status) {
            $split = $this->commissionService->split((float) $course->price);

            $payment = Payment::query()->create([
                'organization_id' => $course->organization_id,
                'student_id' => $student->id,
                'course_id' => $course->id,
                'amount' => $course->price,
                'commission_percentage' => $split['commission_percentage'],
                'platform_amount' => $split['platform_amount'],
                'owner_amount' => $split['owner_amount'],
                'payment_method' => $paymentMethod,
                'status' => $status,
            ]);

            if ($status === 'paid') {
                $this->walletService->credit($course->organization, $split['owner_amount'], 'Course payment #'.$payment->id);
            }

            return $payment;
        });
    }
}
