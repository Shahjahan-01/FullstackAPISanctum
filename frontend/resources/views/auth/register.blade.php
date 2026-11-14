@extends('layouts.app')
@section('title', 'Daftar · Ruang Baca')
@section('content')
<section class="auth-card"><div class="eyebrow">AKUN PERPUSTAKAAN</div><h1>Buat akun baru</h1><p class="muted">Akun diperlukan untuk mengelola koleksi.</p>
<form method="post" action="{{ route('register.submit') }}" class="stack">@csrf
<label>Nama<input name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name"></label>
<label>Email<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
<label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
<label>Ulangi password<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
<button class="button primary">Buat akun</button></form><p class="muted center">Sudah terdaftar? <a href="{{ route('login') }}">Masuk</a></p></section>
@endsection
