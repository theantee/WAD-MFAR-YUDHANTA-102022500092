@extends('layouts.app')
@section('title', 'Pesanan berhasil')

@section('content')
<div class="page-wrap"><div class="success-card"><div class="success-icon">✓</div><p class="section-eyebrow">PESANAN TERCATAT</p><h1>Terima kasih!</h1>
@if ($order)
    <p>Pesanan dan pengirimanmu berhasil disimpan.</p>
    <div class="order-code"><span>Nomor pesanan</span><strong>{{ $order['order_number'] }}</strong></div>
    <div class="success-lines">
        @foreach ($order['pengiriman'] as $p)
        <div class="summary-line"><span>📦 {{ $p['nama_gudang'] }} ({{ $p['nama_pulau'] }}) → {{ $order['nama_zona'] }}, {{ $order['provinsi'] }} ({{ $order['nama_pulau'] }})<br><small>{{ $p['nama_kurir'] }} · {{ $p['estimasi'] }} · {{ number_format((int) $p['jarak_km'], 0, ',', '.') }} km<br>Dasar {{ rupiah($p['biaya_dasar']) }} + jarak {{ rupiah($p['biaya_jarak']) }}{{ (float) $p['biaya_antar_pulau'] > 0 ? ' + antar pulau '.rupiah($p['biaya_antar_pulau']) : '' }}</small></span><strong>{{ rupiah($p['harga']) }}</strong></div>
        @endforeach
        <div class="summary-line"><span>Subtotal barang</span><strong>{{ rupiah($order['subtotal']) }}</strong></div>
        <div class="summary-line"><span>Total ongkir ({{ count($order['pengiriman']) }} pengiriman)</span><strong>{{ rupiah($order['ongkir']) }}</strong></div>
    </div>
    <div class="success-total"><span>Total bayar</span><strong>{{ rupiah($order['total']) }}</strong></div>
@else
    <p>Checkout belum dilakukan dalam sesi ini.</p>
@endif
<p class="small-muted">Ini hanya demo. Belum ada pembayaran yang dilakukan.</p>
<a class="button button-primary" href="{{ route('orders.index') }}">Lihat pesanan saya</a> <a class="button button-secondary" href="{{ route('home') }}">Kembali ke toko</a></div></div>
@endsection
