<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'genre' => ['sometimes', 'nullable', 'string', 'max:80'],
            'sort' => ['sometimes', 'in:title,stock'],
            'direction' => ['sometimes', 'in:asc,desc'],
        ]);

        $books = Book::query()
            ->when($validated['genre'] ?? null, fn ($query, $genre) => $query->where('genre', $genre))
            ->orderBy($validated['sort'] ?? 'title', $validated['direction'] ?? 'asc')
            ->get();

        return response()->json(['data' => $books]);
    }

    public function store(Request $request): JsonResponse
    {
        $book = Book::create($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'genre' => ['required', 'string', 'max:80'],
            'stock' => ['required', 'integer', 'min:0'],
        ]));

        return response()->json(['message' => 'Buku berhasil ditambahkan.', 'data' => $book], 201);
    }

    public function destroy(Book $book): JsonResponse
    {
        $book->delete();

        return response()->json(['message' => 'Buku berhasil dihapus.']);
    }
}
