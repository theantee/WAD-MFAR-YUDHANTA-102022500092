@php
    $navCategories = ['Wanita', 'Pria', 'Anak-anak', 'Home', 'Elektronik', 'Buku, Musik, Blu-Ray Film', 'Hobi & Barang Koleksi', 'Olahraga'];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Beranda') | ShopKu</title>
    <link rel="stylesheet" href="{{ asset('css/ecommerce.css') }}">
    @stack('head')
</head>
<body>
<header class="site-header">
    <div class="top-header">
        <a class="logo" href="{{ route('home') }}" aria-label="ShopKu beranda">ShopKu</a>
        <div class="search-container">
            <a class="catalog-button" href="{{ route('home') }}#kategori">Katalog <span>▼</span></a>
            <form class="search-box" method="get" action="{{ route('home') }}">
                @if (request()->filled('category') && is_string(request('category')))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <span class="search-icon" aria-hidden="true">⌕</span>
                <input type="search" name="q" placeholder="Cari barang" value="{{ is_string(request('q')) ? request('q') : '' }}" aria-label="Cari barang">
                <button class="search-submit" type="submit" aria-label="Cari">Cari</button>
            </form>
        </div>
        <div class="header-actions">
            <div class="auth-buttons">
                @auth
                    <a class="auth-link" href="{{ route('orders.index') }}">Pesanan</a>
                    <span class="divider">|</span>
                    <span class="auth-user" title="Akun yang sedang masuk">Halo, {{ auth()->user()->name }}</span>
                    <span class="divider">|</span>
                    <form class="logout-form" action="{{ route('logout') }}" method="post">@csrf<button type="submit" class="login-button">Keluar</button></form>
                @else
                    <a class="auth-link" href="{{ route('register') }}">Daftar</a>
                    <span class="divider">|</span>
                    <a class="auth-link" href="{{ route('login') }}">Masuk</a>
                @endauth
            </div>
            <button class="sell-button" type="button" onclick="alert('Fitur menjual barang belum tersedia pada demo ini.')">Jual Barang-mu sekarang!</button>
            <a class="cart-top-link" href="{{ route('cart.index') }}" aria-label="Keranjang belanja">🛒 <span class="cart-count">{{ $cartCount ?? 0 }}</span></a>
            <button class="help-button" type="button" aria-label="Bantuan" onclick="alert('Ini demo ShopKu (Laravel). Pembayaran online belum tersedia; akun, pesanan, dan ongkir antar pulau sudah berfungsi.')">?</button>
            <button class="language-button" type="button" onclick="alert('Bahasa yang tersedia saat ini: Indonesia.')">ID <span>▼</span></button>
        </div>
    </div>
    <nav class="category-nav" id="kategori" aria-label="Kategori produk">
        @foreach ($navCategories as $category)
            <a href="{{ route('home', ['category' => $category]) }}#produk">{{ $category }}</a>
        @endforeach
    </nav>
</header>
<main class="main-content">
@yield('content')
</main>
<footer class="footer" id="footer">
    <div class="footer-inner"><a class="footer-logo" href="{{ route('home') }}">ShopKu</a><p>Marketplace demo untuk belajar Laravel, MySQL, HTML, CSS, dan keranjang belanja.</p><p class="footer-note">Demo pembelajaran. Akun, gudang per barang, dan ongkir antar pulau sudah tersedia; fitur penjual dan pembayaran online belum.</p></div>
</footer>
</body>
</html>
