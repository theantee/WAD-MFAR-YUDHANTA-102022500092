@extends('layouts.app')
@section('title', 'Beranda')

@section('content')
<section class="hero">
    <div class="hero-content">
        <p class="hero-eyebrow">LEBIH HEMAT, LEBIH BERMAKNA</p>
        <h1>Mau jual baju bekas atau barang bekas?</h1>
        <p class="hero-description">Temukan barang favoritmu dengan harga yang ramah di kantong.</p>
        <button class="hero-button" type="button" onclick="document.getElementById('produk').scrollIntoView({behavior:'smooth'})">Mulai belanja sekarang</button>
        <a href="#cara-belanja" class="hero-link">Mau tahu cara belanja? Cek di sini</a>
    </div>
    <div class="hero-decoration" aria-hidden="true">
        <div class="hero-orbit orbit-one"></div><div class="hero-orbit orbit-two"></div>
        <div class="hero-shopping-bag">🛍️</div>
        <div class="hero-note"><span>Temukan barang unik</span><strong>Harga bersahabat ✦</strong></div>
    </div>
</section>
<section class="product-section" id="produk">
    <div class="product-container">
        <div class="product-header">
            <div><p class="section-eyebrow">PILIHAN SHOPKU</p><h2>{{ $search !== '' ? 'Hasil pencarian' : ($category !== '' ? 'Kategori: '.$category : 'Temukan barang favoritmu') }}</h2></div>
            @if ($search !== '' || $category !== '')
                <a class="see-all" href="{{ route('home') }}">Hapus filter ×</a>
            @else
                <span class="product-count">Barang pilihan dengan harga menarik</span>
            @endif
        </div>
        @include('partials.flash')
        <div class="product-grid">
        @forelse ($products as $product)
            <article class="product-card">
                <div class="product-image">
                    <a class="product-link" href="{{ route('product.show', $product->id) }}" aria-label="Lihat {{ $product->name }}">
                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy">
                    </a>
                    <span class="condition-badge">{{ $product->condition_label }}</span>
                    <button class="like-button" type="button" aria-pressed="false" data-id="{{ $product->id }}" data-count="{{ $product->likes }}" aria-label="Sukai {{ $product->name }}">
                        <span class="heart-icon">♡</span><span class="like-count">{{ $product->likes }}</span>
                    </button>
                </div>
                <div class="product-info">
                    <a class="product-name-link" href="{{ route('product.show', $product->id) }}"><h3>{{ $product->name }}</h3></a>
                    <p class="product-condition-text">{{ $product->category }}{{ $product->size_label !== '' && $product->size_label !== '-' ? ' · Ukuran '.$product->size_label : '' }}</p>
                    <p class="vendor-line">📍 Dikirim dari {{ $product->gudang->nama_gudang }}</p>
                    <div class="product-bottom">
                        <strong class="product-price">{{ rupiah($product->price) }}</strong>
                        @if ($product->stock > 0)
                        <form action="{{ route('cart.add') }}" method="post" class="add-form">
                            @csrf<input type="hidden" name="product_id" value="{{ $product->id }}"><input type="hidden" name="quantity" value="1"><input type="hidden" name="return_to" value="home">
                            <button class="addtochart-button" type="submit" aria-label="Tambahkan ke keranjang">+ 🛒</button>
                        </form>
                        @else
                            <span class="sold-out">Habis</span>
                        @endif
                    </div>
                    <p class="stock-note">{{ $product->stock > 0 ? 'Stok '.$product->stock : 'Stok habis' }}</p>
                </div>
            </article>
        @empty
            <div class="no-results"><span>⌕</span><h3>Barang tidak ditemukan</h3><p>Coba kata kunci atau kategori lain.</p><a href="{{ route('home') }}" class="button button-primary">Lihat semua barang</a></div>
        @endforelse
        </div>
        <section class="discovery-section" id="cara-belanja">
            <div class="discovery-box"><h2>Most popular categories</h2><div class="tag-container">
                @foreach (['Wanita', 'Pria', 'Sepatu', 'Tas', 'Elektronik', 'Hobi & Barang Koleksi', 'Olahraga'] as $cat)
                    @php $realCat = in_array($cat, ['Sepatu', 'Tas'], true) ? '' : $cat; @endphp
                    <a class="category-tag" href="{{ $realCat !== '' ? route('home', ['category' => $realCat]).'#produk' : '#produk' }}">{{ $cat }}</a>
                @endforeach
            </div></div>
            <div class="discovery-box"><h2>Best selling brands</h2><div class="tag-container">
                @foreach (['Nike', 'Adidas', 'Uniqlo', 'H&M', 'Zara', "Levi's", 'Converse', 'Vans', 'Puma', 'New Balance'] as $brand)<a class="brand-tag" href="#produk" data-brand="{{ $brand }}">{{ $brand }}</a>@endforeach
            </div></div>
        </section>
        <div class="see-more-container"><a class="see-more-button" href="{{ route('home') }}">Lihat lebih banyak</a></div>
        <div class="info-strip"><span class="info-icon">✓</span><div><strong>Belanja sederhana, langkah demi langkah</strong><p>Tambahkan barang ke keranjang, periksa pesanan, lalu checkout. Demo ini belum menerima pembayaran online.</p></div></div>
    </div>
</section>
<script>
// Tombol suka masih disimpan di browser ini saja; belum tersimpan ke database.
document.querySelectorAll('.brand-tag[data-brand]').forEach(function(link) {
    link.addEventListener('click', function(event) {
        event.preventDefault();
        const input = document.querySelector('.search-box [name=q]');
        input.value = this.dataset.brand;
        document.querySelector('.search-box').requestSubmit();
    });
});

document.querySelectorAll('.like-button').forEach(function(button) {
    const id = button.dataset.id;
    const base = Number(button.dataset.count || 0);
    const key = 'shopku-liked-' + id;
    let liked = localStorage.getItem(key) === 'yes';
    const countEl = button.querySelector('.like-count');
    function render() {
        button.classList.toggle('liked', liked);
        button.setAttribute('aria-pressed', String(liked));
        button.querySelector('.heart-icon').textContent = liked ? '♥' : '♡';
        countEl.textContent = Math.max(0, base + (liked ? 1 : 0));
    }
    render();
    button.addEventListener('click', function() {
        liked = !liked;
        if (liked) localStorage.setItem(key, 'yes'); else localStorage.removeItem(key);
        render();
    });
});
</script>
@endsection
