@extends('layouts.app')
@section('title', 'Pesanan saya')

@section('content')
<div class="page-wrap">
    <div class="page-heading"><p class="section-eyebrow">AKUNMU</p><h1>Pesanan saya</h1><p>20 pesanan terbaru beserta status pengirimannya.</p></div>
    @forelse ($orders as $o)
        <article class="order-card">
            <div class="order-card-head">
                <div><strong>{{ $o->order_number }}</strong><span class="muted"> · {{ $o->created_at->translatedFormat('d F Y H:i') }}</span></div>
                <div><span class="status-badge">{{ $o->status }}</span></div>
            </div>
            @foreach ($o->pengiriman as $s)
            <div class="ship-block">
                <div class="order-card-head"><div class="ship-title">📦 Dikirim dari {{ $s->gudang->nama_gudang }} → {{ $s->alamat->zona->nama_zona }}</div><span class="status-badge status-ship">Kirim: {{ $s->status }}</span></div>
                <ul class="order-items">
                    @foreach ($o->items->where('id_gudang', $s->id_gudang) as $it)<li><span>{{ $it->product_name }} × {{ $it->quantity }}</span><strong>{{ rupiah($it->subtotal) }}</strong></li>@endforeach
                </ul>
                <div class="order-ship"><div class="muted">{{ $s->kurir->nama_kurir }} ({{ $s->kurir->estimasi }}) · ongkir {{ rupiah($s->tarif_ongkir) }}{{ $s->biaya_antar_pulau > 0 ? ' (termasuk antar pulau '.rupiah($s->biaya_antar_pulau).')' : '' }}</div><div class="muted">📍 {{ $s->alamat->alamat }}</div></div>
            </div>
            @endforeach
            <div class="order-totals">
                <span>Barang {{ rupiah($o->subtotal) }} + Ongkir {{ rupiah($o->ongkir) }}</span>
                <strong>Total {{ rupiah($o->total) }}</strong>
            </div>
        </article>
    @empty
        <div class="empty-state"><div class="empty-icon">📦</div><h2>Belum ada pesanan</h2><p>Pesanan yang kamu buat akan muncul di sini.</p><a class="button button-primary" href="{{ route('home') }}">Mulai belanja</a></div>
    @endforelse
</div>
@endsection
