@extends('layouts.app')
@section('title', $product->name)

@section('content')
<div class="page-wrap product-detail-wrap">
    <a class="back-link" href="{{ route('home') }}">← Kembali ke katalog</a>
    @include('partials.flash')
    <div class="product-detail-card">
        <div class="detail-image"><img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}"></div>
        <div class="detail-info">
            <p class="section-eyebrow">{{ $product->category }}</p>
            <h1>{{ $product->name }}</h1>
            <p class="detail-price">{{ rupiah($product->price) }}</p>
            <span class="detail-condition">{{ $product->condition_label }}{{ $product->size_label !== '' && $product->size_label !== '-' ? ' · Ukuran '.$product->size_label : '' }}</span>
            <p class="detail-description">{{ $product->description }}</p>
            <p class="vendor-line">📍 Dikirim dari {{ $product->gudang->nama_gudang }}</p>
            <p class="detail-stock">{{ $product->stock > 0 ? 'Tersedia: '.$product->stock.' barang' : 'Stok habis' }}</p>
            @if ($product->stock > 0)
            <form action="{{ route('cart.add') }}" method="post" class="detail-add-form">
                @csrf<input type="hidden" name="product_id" value="{{ $product->id }}"><input type="hidden" name="return_to" value="product:{{ $product->id }}">
                <label>Jumlah<input type="number" name="quantity" value="1" min="1" max="{{ $product->stock }}"></label>
                <button class="button button-primary" type="submit">+ Tambah ke keranjang</button>
                <a class="button button-secondary" href="{{ route('cart.index') }}">Lihat keranjang</a>
            </form>
            @else
                <span class="sold-out">Stok habis</span>
            @endif
        </div>
    </div>
</div>
@endsection
