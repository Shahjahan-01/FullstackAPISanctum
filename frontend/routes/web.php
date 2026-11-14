<?php

use App\Http\Controllers\LibraryController;
use Illuminate\Support\Facades\Route;

Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');
Route::post('/login', [LibraryController::class, 'login'])->name('login.submit');
Route::post('/register', [LibraryController::class, 'register'])->name('register.submit');
Route::get('/', [LibraryController::class, 'index'])->name('library');
Route::post('/logout', [LibraryController::class, 'logout'])->name('logout');
Route::post('/books', [LibraryController::class, 'addBook'])->name('books.store');
Route::delete('/books/{book}', [LibraryController::class, 'deleteBook'])->name('books.destroy');
Route::post('/loans/preview', [LibraryController::class, 'previewLoan'])->name('loans.preview');
Route::post('/loans/confirm', [LibraryController::class, 'confirmLoan'])->name('loans.confirm');
Route::delete('/loans/preview', [LibraryController::class, 'cancelLoan'])->name('loans.cancel');
