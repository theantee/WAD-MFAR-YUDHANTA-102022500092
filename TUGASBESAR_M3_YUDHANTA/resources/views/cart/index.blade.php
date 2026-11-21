@extends('layouts.app')
@section('title', 'Keranjang')

@section('content')
<div class="page-wrap">
    <div class="page-heading"><p class="section-eyebrow">PESANANMU</p><h1>Keranjang belanja</h1><p>Periksa barang dan jumlah sebelum checkout.</p></div>
    @include('partials.flash')
    @if ($items->isEmpty())
        <div class="empty-state"><div class="empty-icon">🛒</div><h2>Keranjang masih kosong</h2><p>Tambahkan barang dari katalog untuk mulai berbelanja.</p><a class="button button-primary" href="{{ route('home') }}">Lihat produk</a></div>
    @else
    <div class="cart-layout"><section class="cart-panel"><form action="{{ route('cart.update') }}" method="post">@csrf
        @foreach ($items as $item)
        <div class="cart-item"><img class="cart-product-image" src="{{ $item->product->imageUrl() }}" alt=""><div class="cart-product-details"><h3>{{ $item->product->name }}</h3><p>{{ rupiah($item->product->price) }} / barang</p><span class="muted">Stok: {{ $item->product->stock }}</span><p class="vendor-line">📍 Dikirim dari {{ $item->product->gudang->nama_gudang }}</p></div><label class="quantity-label">Jumlah<input type="number" name="qty[{{ $item->product->id }}]" min="0" max="{{ max(1, $item->product->stock) }}" value="{{ $item->quantity }}"></label><div class="cart-subtotal"><strong>{{ rupiah($item->subtotal()) }}</strong><button class="text-button" type="submit" name="remove_id" value="{{ $item->product->id }}">Hapus</button></div></div>
        @endforeach
        <button class="button button-secondary" type="submit">Perbarui jumlah</button>
    </form></section><aside class="summary-card"><h2>Ringkasan</h2><div class="summary-line"><span>Jumlah barang</span><strong>{{ $jumlah }}</strong></div><div class="summary-line"><span>Subtotal</span><strong>{{ rupiah($total) }}</strong></div><p class="summary-note">Barang dari gudang berbeda dikirim terpisah. Ongkir dihitung per gudang saat checkout, setelah kamu menandai lokasi tujuan di peta.</p><div class="summary-total"><span>Total</span><strong>{{ rupiah($total) }}</strong></div><a href="{{ route('checkout.show') }}" class="button button-primary button-full">Lanjut checkout</a><a href="{{ route('home') }}" class="back-link">← Lanjut belanja</a></aside></div>
    @endif
</div>
@endsection
