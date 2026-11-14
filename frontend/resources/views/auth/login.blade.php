@extends('layouts.app')
@section('title', 'Masuk · Ruang Baca')
@section('content')
<section class="auth-card"><div class="eyebrow">PERPUSTAKAAN KOMUNITAS</div><h1>Selamat datang kembali</h1><p class="muted">Masuk untuk mengelola koleksi dan peminjaman.</p>
<form method="post" action="{{ route('login.submit') }}" class="stack">@csrf
<label>Email<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
<label>Password<input type="password" name="password" required autocomplete="current-password"></label>
<button class="button primary">Masuk ke perpustakaan</button></form>
<p class="muted center">Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a></p></section>
@endsection
