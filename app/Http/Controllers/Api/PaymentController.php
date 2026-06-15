<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()->with('organization', 'student', 'course', 'receipts.paymentMethod');

        if (! $request->user()->hasRole('super_admin')) {
            $query->where('organization_id', $request->user()->organization_id);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request, PaymentService $paymentService, AuditLogService $auditLogService): JsonResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'payment_method' => ['required', Rule::in(['vodafone_cash', 'instapay', 'fawry', 'card', 'cash'])],
            'status' => ['nullable', Rule::in(['pending', 'paid', 'failed', 'refunded'])],
        ]);

        $student = User::query()->findOrFail($data['student_id']);
        $course = Course::query()->with('organization')->findOrFail($data['course_id']);

        if (! $request->user()->hasRole('super_admin') && (int) $course->organization_id !== (int) $request->user()->organization_id) {
            abort(403, 'You do not have access to this course payment.');
        }

        $payment = $paymentService->createCoursePayment(
            $student,
            $course,
            $data['payment_method'],
            $data['status'] ?? 'paid'
        );

        $auditLogService->record($request, 'payment_created', $payment);

        return response()->json($payment->load('student', 'course'), 201);
    }

    public function update(Request $request, Payment $payment, AuditLogService $auditLogService): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'paid', 'failed', 'refunded'])],
        ]);

        $payment->update($data);
        $auditLogService->record($request, 'payment_updated', $payment, ['status' => $data['status']]);

        return response()->json($payment->fresh('receipts'));
    }
}
