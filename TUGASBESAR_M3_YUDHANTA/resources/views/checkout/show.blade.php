@extends('layouts.app')
@section('title', 'Checkout')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@php
    // Pilihan kurir sebelumnya setelah validasi gagal: [id_gudang => id_kurir]
    $oldKurir = collect(old('kurir', []))->mapWithKeys(fn ($k, $g) => [(int) $g => (int) $k])->all();
@endphp

@section('content')
<div class="page-wrap"><div class="page-heading"><p class="section-eyebrow">LANGKAH TERAKHIR</p><h1>Checkout</h1><p>Isi data penerima, tandai lokasi tujuan di peta, lalu pilih kurir untuk setiap gudang pengirim. Ongkir dihitung dari gudang asal barang ke kota tujuanmu.</p></div>
@if (session('error'))<div class="error-notice">{{ session('error') }}</div>@endif
@if ($errors->any())<div class="error-notice">@foreach ($errors->all() as $pesan){{ $pesan }}@if (! $loop->last)<br>@endif @endforeach</div>@endif
<div class="checkout-layout">
<form class="checkout-form" id="formCheckout" method="post" action="{{ route('checkout.store') }}">@csrf
    <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
    <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
    <h2>1. Informasi penerima</h2>
    <label>Nama lengkap<input type="text" name="customer_name" maxlength="120" required value="{{ old('customer_name') }}"></label>
    <label>Nomor telepon<input type="tel" name="phone" maxlength="30" required value="{{ old('phone') }}"></label>
    <label>Alamat lengkap<input type="text" name="alamat" maxlength="255" placeholder="Jalan, nomor rumah, kelurahan" required value="{{ old('alamat') }}"></label>
    <label>Detail alamat (opsional)<textarea name="detail_alamat" rows="2" maxlength="1000" placeholder="Patokan, lantai, warna pagar...">{{ old('detail_alamat') }}</textarea></label>

    <h2>2. Tandai lokasi tujuan</h2>
    <p class="small-muted">Klik peta untuk menaruh pin. Setiap area adalah zona satu kota di Indonesia (warna menunjukkan kelompok pulau); area yang kamu pilih akan disorot. Pulau yang tidak memiliki kota layanan berada di luar jangkauan.</p>
    <div id="map" class="map-box" aria-label="Peta pemilihan lokasi pengiriman"></div>
    <div class="coord-line">Koordinat: <code id="coordText">belum dipilih</code></div>

    <h2>3. Pilih kurir untuk tiap gudang</h2>
    <div id="hasilOngkir" class="courier-result" aria-live="polite"><p class="small-muted">Pilihan kurir muncul setelah lokasi ditandai di peta.</p></div>

    <button class="button button-primary button-full" id="btnPesan" type="submit" disabled>Buat pesanan</button>
    <p class="small-muted">Demo ini belum memproses pembayaran online.</p>
</form>
<aside class="summary-card"><h2>Pesananmu</h2>
    @foreach ($grup as $idGudang => $g)
    <div class="ship-block">
        <div class="ship-title">📦 Dikirim dari {{ $g['gudang']->nama_gudang }}</div>
        @foreach ($g['items'] as $item)<div class="summary-line"><span>{{ $item->product->name }} × {{ $item->quantity }}</span><strong>{{ rupiah($item->subtotal()) }}</strong></div>@endforeach
        <div class="summary-line ship-fee"><span>Ongkir <small id="kurirLabel-{{ $idGudang }}"></small></span><strong id="ongkir-{{ $idGudang }}">Pilih kurir</strong></div>
    </div>
    @endforeach
    <div class="summary-line"><span>Subtotal barang</span><strong>{{ rupiah($subtotal) }}</strong></div>
    <div class="summary-line"><span>Total ongkir</span><strong id="ongkirTotal">–</strong></div>
    <div class="summary-total"><span>Total bayar</span><strong id="totalText" data-subtotal="{{ $subtotal }}">{{ rupiah($subtotal) }}</strong></div>
    <a class="back-link" href="{{ route('cart.index') }}">← Kembali ke keranjang</a>
</aside></div></div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const csrfToken = @json(csrf_token());
const urlOngkir = @json(route('shipping.quote'));
const zonaLayanan = @json($zonaPeta);
const gudangIds = @json($grup->keys()->map(fn ($k) => (int) $k)->values());
let oldKurir = @json((object) $oldKurir);   // pilihan sebelumnya setelah error validasi
const subtotal = Number(document.getElementById('totalText').dataset.subtotal);

const map = L.map('map').setView([-2.5, 118], 5);
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);

// Zona tujuan (satu kota per area). Area yang sedang dipilih disorot.
const zoneLayers = {}, zoneStyle = {};
const warnaPulau = ['#DAA520', '#2e8b57', '#4682b4', '#b5651d', '#8e44ad', '#c0392b', '#16a085', '#7f8c8d'];   // satu warna per kelompok pulau
const styleAktif = { color: '#000', weight: 3, fillOpacity: 0.45 };
zonaLayanan.forEach(function(z) {
  if (!z.titik || z.titik.length < 3) return;
  const warna = warnaPulau[(z.id_pulau - 1) % warnaPulau.length];
  zoneStyle[z.id_zona] = { color: warna, weight: 1, fillColor: warna, fillOpacity: 0.18 };
  const poly = L.polygon(z.titik.map(t => [t.latitude, t.longitude]), zoneStyle[z.id_zona]).addTo(map);
  poly.bindTooltip(z.nama_zona + ', ' + z.provinsi + ' (' + z.nama_pulau + ')', { sticky: true });
  poly.on('click', function(e) { L.DomEvent.stopPropagation(e); pilihTitik(e.latlng.lat, e.latlng.lng); });
  zoneLayers[z.id_zona] = poly;
});
const semuaLayer = Object.values(zoneLayers);
if (semuaLayer.length) map.fitBounds(L.featureGroup(semuaLayer).getBounds().pad(0.02));

const latInput = document.getElementById('latitude'), lngInput = document.getElementById('longitude');
const hasil = document.getElementById('hasilOngkir'), btnPesan = document.getElementById('btnPesan');
let marker = null, requestSeq = 0, aktifZona = null;
const pilihan = {};   // id_gudang -> harga kurir terpilih

function esc(v) { return String(v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c])); }
function rp(n) { return 'Rp' + Number(n).toLocaleString('id-ID'); }

function sorotZona(id) {
  if (aktifZona && zoneLayers[aktifZona]) zoneLayers[aktifZona].setStyle(zoneStyle[aktifZona]);
  aktifZona = id;
  if (id && zoneLayers[id]) { zoneLayers[id].setStyle(styleAktif); zoneLayers[id].bringToBack(); }
}

function hitungTotal() {
  let ongkir = 0, lengkap = gudangIds.length > 0;
  gudangIds.forEach(function(g) { if (pilihan[g] === undefined) lengkap = false; else ongkir += pilihan[g]; });
  document.getElementById('ongkirTotal').textContent = lengkap ? rp(ongkir) : '–';
  document.getElementById('totalText').textContent = rp(subtotal + (lengkap ? ongkir : 0));
  btnPesan.disabled = !lengkap;
}

function resetRingkasan(pesanHtml) {
  Object.keys(pilihan).forEach(k => delete pilihan[k]);
  gudangIds.forEach(function(g) {
    document.getElementById('ongkir-' + g).textContent = 'Pilih kurir';
    document.getElementById('kurirLabel-' + g).textContent = '';
  });
  hitungTotal();
  hasil.innerHTML = pesanHtml;
}

async function pilihTitik(lat, lng) {
  latInput.value = lat; lngInput.value = lng;
  document.getElementById('coordText').textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
  if (marker) map.removeLayer(marker);
  marker = L.marker([lat, lng]).addTo(map);
  sorotZona(null);
  resetRingkasan('<p class="small-muted">Menghitung ongkir…</p>');
  const seq = ++requestSeq;
  try {
    const body = new URLSearchParams({ _token: csrfToken, latitude: lat, longitude: lng });
    const resp = await fetch(urlOngkir, {
      method: 'POST', body,
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
    });
    const result = await resp.json();
    if (seq !== requestSeq) return;               // ada klik yang lebih baru
    if (!resp.ok || !result.success) throw new Error(result.message || 'Gagal menghitung ongkir.');
    sorotZona(result.data.zona.id_zona);
    tampilkanKurir(result.data.zona, result.data.grup);
  } catch (err) {
    if (seq !== requestSeq) return;
    resetRingkasan('<div class="error-notice">' + esc(err.message) + '</div>');
  }
}

function tampilkanKurir(zona, grup) {
  let html = '<div class="zone-info">Tujuan: <strong>' + esc(zona.nama_zona) + '</strong>, ' + esc(zona.provinsi) + ' (' + esc(zona.nama_pulau) + ')</div>';
  grup.forEach(function(g) {
    const rute = g.antar_pulau ? ' → ' + esc(zona.nama_pulau) + ' · antar pulau' : ' · satu pulau';
    html += '<fieldset class="courier-group"><legend>📦 Dari ' + esc(g.nama_gudang) + ' (' + esc(g.nama_pulau) + ')' + rute + '</legend><div class="courier-list">';
    g.opsi.forEach(function(o) {
      const rincian = o.jarak_km.toLocaleString('id-ID') + ' km: dasar ' + rp(o.biaya_dasar) + ' + jarak ' + rp(o.biaya_jarak)
                    + (o.biaya_antar_pulau > 0 ? ' + antar pulau ' + rp(o.biaya_antar_pulau) : '');
      html += '<label class="courier-option"><input type="radio" name="kurir[' + g.id_gudang + ']" value="' + o.id_kurir + '" required>'
            + '<span class="courier-name">' + esc(o.nama_kurir) + '<small>' + esc(o.estimasi) + '<br>' + esc(rincian) + '</small></span>'
            + '<strong>' + esc(o.harga_format) + '</strong></label>';
    });
    html += '</div></fieldset>';
  });
  hasil.innerHTML = html;
  grup.forEach(function(g) {
    hasil.querySelectorAll('input[name="kurir[' + g.id_gudang + ']"]').forEach(function(r) {
      r.addEventListener('change', function() { pilihKurir(g, g.opsi.find(o => String(o.id_kurir) === this.value)); });
    });
    const pre = g.opsi.find(o => o.id_kurir === oldKurir[g.id_gudang]);   // pulihkan pilihan setelah error validasi
    if (pre) { hasil.querySelector('input[name="kurir[' + g.id_gudang + ']"][value="' + pre.id_kurir + '"]').checked = true; pilihKurir(g, pre); }
  });
  oldKurir = {};
}

function pilihKurir(g, opsi) {
  pilihan[g.id_gudang] = opsi.harga;
  document.getElementById('ongkir-' + g.id_gudang).textContent = rp(opsi.harga);
  document.getElementById('kurirLabel-' + g.id_gudang).textContent = '(' + opsi.nama_kurir + ')';
  hitungTotal();
}

map.on('click', e => pilihTitik(e.latlng.lat, e.latlng.lng));
if (latInput.value && lngInput.value) pilihTitik(Number(latInput.value), Number(lngInput.value));   // pulihkan pin setelah error
</script>
@endsection
