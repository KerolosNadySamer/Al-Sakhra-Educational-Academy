<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookPurchase;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Book::query()->with('organization')->where('status', 'active');

        if (! $request->user()->hasRole('super_admin')) {
            $query->where('organization_id', $request->user()->organization_id);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request, AuditLogService $auditLogService): JsonResponse
    {
        $data = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'pdf_file' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        if (! $request->user()->hasRole('super_admin')) {
            $data['organization_id'] = $request->user()->organization_id;
        }

        $book = Book::query()->create($data);
        $auditLogService->record($request, 'book_created', $book);

        return response()->json($book, 201);
    }

    public function purchase(Request $request, Book $book, AuditLogService $auditLogService): JsonResponse
    {
        $data = $request->validate([
            'payment_id' => ['nullable', 'exists:payments,id'],
        ]);

        $purchase = BookPurchase::query()->firstOrCreate([
            'student_id' => $request->user()->id,
            'book_id' => $book->id,
        ], [
            'payment_id' => $data['payment_id'] ?? null,
        ]);

        $auditLogService->record($request, 'book_purchased', $book, ['purchase_id' => $purchase->id]);

        return response()->json($purchase->load('book'), 201);
    }
}
