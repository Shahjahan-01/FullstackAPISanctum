<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Mei Library')</title>
    <link rel="stylesheet" href="{{ asset('css/library.css') }}">
</head>
<body>
<header class="topbar"><a class="brand" href="{{ route('library') }}"><span class="brand-mark">M</span><span>Ruang Baca<small>Perpustakaan Komunitas</small></span></a>
    @if(session('user'))<div class="account"><span>{{ session('user.name') }}</span><form method="post" action="{{ route('logout') }}">@csrf<button class="link-button">Keluar</button></form></div>@endif
</header>
<main class="page">@if(session('success'))<div class="notice success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="notice error">{{ session('error') }}</div>@endif
@if($errors->any())<div class="notice error">{{ $errors->first() }}</div>@endif
@yield('content')</main>
</body>
</html>
