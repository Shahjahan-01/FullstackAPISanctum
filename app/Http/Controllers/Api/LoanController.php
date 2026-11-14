<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Loan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanController extends Controller
{
    public function index(): JsonResponse
    {
        $loans = Loan::with('items')->latest()->get();

        return response()->json(['data' => $loans]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'borrower_name' => ['required', 'string', 'max:120'],
            'phone_number' => ['required', 'string', 'max:30'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'idempotency_key' => ['required', 'uuid'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.book_id' => ['required', 'integer', 'distinct', 'exists:books,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $existing = Loan::with('items')->where('idempotency_key', $validated['idempotency_key'])->first();
        if ($existing) {
            return response()->json(['message' => 'Peminjaman sudah pernah dicatat.', 'data' => $existing]);
        }

        $loan = DB::transaction(function () use ($validated): Loan {
            $books = Book::query()->whereIn('id', collect($validated['items'])->pluck('book_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($validated['items'] as $item) {
                $book = $books->get($item['book_id']);
                if (! $book || $book->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ["Stok buku {$book?->title} tidak mencukupi. Tidak ada stok yang diubah."],
                    ]);
                }
            }

            $loan = Loan::create(collect($validated)->only('borrower_name', 'phone_number', 'due_date', 'idempotency_key')->all());
            foreach ($validated['items'] as $item) {
                $book = $books->get($item['book_id']);
                $loan->items()->create([
                    'book_id' => $book->id,
                    'book_title' => $book->title,
                    'quantity' => $item['quantity'],
                ]);
                $book->decrement('stock', $item['quantity']);
            }

            return $loan->load('items');
        }, attempts: 3);

        return response()->json(['message' => 'Peminjaman berhasil dicatat.', 'data' => $loan], 201);
    }
}
