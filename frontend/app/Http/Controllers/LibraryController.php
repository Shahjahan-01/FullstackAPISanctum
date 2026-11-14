<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class LibraryController extends Controller
{
    private function api(Request $request): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.library_api.url'), '/'))
            ->acceptJson()->asJson()->timeout(10)
            ->withToken((string) $request->session()->get('api_token'));
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('api_token')) {
            return redirect()->route('login');
        }

        $response = $this->api($request)->get('/api/books', $request->only('genre', 'sort', 'direction'));
        if ($response->status() === 401) {
            $request->session()->forget(['api_token', 'user']);
            return redirect()->route('login')->with('error', 'Sesi berakhir. Silakan login kembali.');
        }

        $loans = $this->api($request)->get('/api/loans')->json('data', []);
        $allBooks = $this->api($request)->get('/api/books')->json('data', []);
        return view('library', [
            'books' => $response->json('data', []),
            'loans' => $loans,
            'genres' => collect($allBooks)->pluck('genre')->unique()->sort()->values(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $response = Http::baseUrl(rtrim((string) config('services.library_api.url'), '/'))
            ->acceptJson()->asJson()->timeout(10)->post('/api/auth/login', $credentials);
        if (! $response->successful()) {
            return back()->withInput($request->only('email'))->withErrors($response->json('errors', ['email' => [$response->json('message', 'Login gagal.')]]));
        }

        $request->session()->regenerate();
        $request->session()->put(['api_token' => $response->json('token'), 'user' => $response->json('user')]);
        return redirect()->route('library')->with('success', 'Selamat datang kembali.');
    }

    public function register(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $response = Http::baseUrl(rtrim((string) config('services.library_api.url'), '/'))
            ->acceptJson()->asJson()->timeout(10)->post('/api/auth/register', $credentials);
        if (! $response->successful()) {
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors($response->json('errors', ['email' => [$response->json('message', 'Registrasi gagal.')]]));
        }

        $request->session()->regenerate();
        $request->session()->put(['api_token' => $response->json('token'), 'user' => $response->json('user')]);
        return redirect()->route('library')->with('success', 'Akun berhasil dibuat.');
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($request->session()->has('api_token')) {
            $this->api($request)->post('/api/auth/logout');
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', 'Anda sudah logout.');
    }

    public function addBook(Request $request): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'genre' => ['required', 'string', 'max:80'], 'stock' => ['required', 'integer', 'min:0']]);
        $response = $this->api($request)->post('/api/books', $data);
        return $response->successful() ? back()->with('success', 'Buku ditambahkan.') : back()->withErrors($response->json('errors', ['book' => [$response->json('message', 'Gagal menambah buku.')]]));
    }

    public function deleteBook(Request $request, int $book): RedirectResponse
    {
        $response = $this->api($request)->delete('/api/books/'.$book);
        return $response->successful() ? back()->with('success', 'Buku dihapus; riwayat peminjaman tetap tersimpan.') : back()->withErrors(['book' => [$response->json('message', 'Gagal menghapus buku.')]]);
    }

    public function previewLoan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'borrower_name' => ['required', 'string', 'max:120'], 'phone_number' => ['required', 'string', 'max:30'],
            'due_date' => ['required', 'date', 'after_or_equal:today'], 'items' => ['required', 'array', 'min:1'],
            'items.*.book_id' => ['required', 'integer', 'distinct'], 'items.*.quantity' => ['required', 'integer', 'min:0'],
        ]);
        $formItems = $data['items'];
        $data['items'] = array_values(array_filter($data['items'], fn (array $item): bool => $item['quantity'] > 0));
        if ($data['items'] === []) {
            return back()->withInput()->withErrors(['items' => ['Pilih minimal satu buku dengan jumlah lebih dari nol.']]);
        }
        $books = $this->api($request)->get('/api/books')->json('data', []);
        $indexed = collect($books)->keyBy('id');
        $summary = [];
        foreach ($data['items'] as $item) {
            $book = $indexed->get($item['book_id']);
            if (! $book || $book['stock'] < $item['quantity']) {
                return back()->withInput()->withErrors(['items' => ['Jumlah yang diminta melebihi stok yang tersedia.']]);
            }
            $summary[] = ['book_title' => $book['title'], 'quantity' => $item['quantity']];
        }
        $data['idempotency_key'] = (string) Str::uuid();
        $request->session()->put('loan_preview', $data + ['summary' => $summary, 'edit_items' => $formItems]);
        return redirect()->route('library')->with('show_summary', true);
    }

    public function confirmLoan(Request $request): RedirectResponse
    {
        $data = $request->session()->get('loan_preview');
        abort_unless(is_array($data), 422, 'Ringkasan peminjaman tidak ditemukan.');
        $response = $this->api($request)->post('/api/loans', collect($data)->except('summary', 'edit_items')->all());
        if (! $response->successful()) {
            return redirect()->route('library')->withErrors($response->json('errors', ['loan' => [$response->json('message', 'Peminjaman gagal. Stok belum berubah.')]]))->with('show_summary', true);
        }
        $request->session()->forget('loan_preview');
        return redirect()->route('library')->with('success', 'Peminjaman dikonfirmasi dan stok berhasil diperbarui.');
    }

    public function cancelLoan(Request $request): RedirectResponse
    {
        $preview = $request->session()->pull('loan_preview', []);
        $input = collect($preview)->only('borrower_name', 'phone_number', 'due_date')->all();
        $input['items'] = $preview['edit_items'] ?? [];

        return redirect()->route('library')->withInput($input)->with('success', 'Silakan ubah data peminjaman. Stok tidak berubah.');
    }
}
