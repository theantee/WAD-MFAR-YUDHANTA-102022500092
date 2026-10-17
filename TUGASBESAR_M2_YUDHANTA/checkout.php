<?php
require_once __DIR__ . '/bootstrap.php';
include('service/database.php');
include('service/shipping.php');
$pageTitle = 'Checkout';
require_login('checkout.php');
if (empty($_SESSION['cart'])) redirect('cart.php');

$error = '';

// Ringkasan keranjang untuk tampilan (harga selalu dibaca ulang dari database).
function load_cart_items(mysqli $db): array {
    $items = [];
    foreach ($_SESSION['cart'] as $id => $quantity) {
        $stmt = mysqli_prepare($db, 'SELECT id, name, price, stock FROM products WHERE id = ? AND active = 1');
        $id = (int)$id;
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if ($product) {
            $product['quantity'] = (int)$quantity;
            $product['subtotal'] = (float)$product['price'] * (int)$quantity;
            $items[] = $product;
        }
    }
    return $items;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $customerName = trim((string)($_POST['customer_name'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $alamat = trim((string)($_POST['alamat'] ?? ''));
    $detail = trim((string)($_POST['detail_alamat'] ?? ''));
    $idKurir = filter_input(INPUT_POST, 'id_kurir', FILTER_VALIDATE_INT);
    $koordinat = bacaKoordinat($_POST['latitude'] ?? null, $_POST['longitude'] ?? null);

    if ($customerName === '' || $phone === '' || $alamat === '') {
        $error = 'Nama, telepon, dan alamat wajib diisi.';
    } elseif (str_len($customerName) > 120 || str_len($phone) > 30 || str_len($alamat) > 255 || str_len($detail) > 1000) {
        $error = 'Ada isian yang terlalu panjang.';
    } elseif (!$koordinat) {
        $error = 'Klik peta untuk menandai titik tujuan pengiriman.';
    } elseif (!$idKurir) {
        $error = 'Pilih jenis kurir terlebih dahulu.';
    } else {
        try {
            // Zona, gudang, dan tarif dihitung ulang di server; harga dari browser tidak dipercaya.
            $tarif = hitungOngkir($db, $koordinat[0], $koordinat[1], $idKurir);
            if (!$tarif) {
                throw new RuntimeException('Titik tujuan di luar area layanan, atau kurir tidak tersedia untuk zona ini.');
            }

            // Satu transaksi untuk semuanya: stok, pesanan, alamat, dan pengiriman berhasil bersama atau batal bersama.
            mysqli_begin_transaction($db);
            $freshItems = [];
            $subtotal = 0;
            foreach ($_SESSION['cart'] as $id => $quantity) {
                $id = (int)$id; $quantity = (int)$quantity;
                $stmt = mysqli_prepare($db, 'SELECT id, name, price, stock FROM products WHERE id = ? AND active = 1 FOR UPDATE');
                mysqli_stmt_bind_param($stmt, 'i', $id);
                mysqli_stmt_execute($stmt);
                $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                mysqli_stmt_close($stmt);
                if (!$product || $quantity < 1 || $quantity > (int)$product['stock']) {
                    throw new RuntimeException('Stok barang tidak mencukupi. Kembali ke keranjang dan periksa jumlahnya.');
                }
                $product['quantity'] = $quantity;
                $product['subtotal'] = (float)$product['price'] * $quantity;
                $subtotal += $product['subtotal'];
                $freshItems[] = $product;
            }
            if (!$freshItems) throw new RuntimeException('Keranjang kosong.');

            $ongkir = (float)$tarif['harga'];
            $total = $subtotal + $ongkir;
            $userId = current_user_id();

            // 1) alamat pengiriman (titik peta + zona + gudang)
            $stmt = mysqli_prepare($db, 'INSERT INTO alamat_pengiriman (id_pengguna, id_gudang, id_zona, alamat, detail_alamat, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'iiissdd', $userId, $tarif['id_gudang'], $tarif['id_zona'], $alamat, $detail, $koordinat[0], $koordinat[1]);
            mysqli_stmt_execute($stmt);
            $idAlamat = mysqli_insert_id($db);
            mysqli_stmt_close($stmt);

            // 2) pesanan (subtotal + ongkir = total)
            $orderNumber = 'SKU-' . date('YmdHis') . '-' . random_int(100, 999);
            $status = 'Menunggu pembayaran';
            $fullAddress = $detail !== '' ? $alamat . ' (' . $detail . ')' : $alamat;
            $stmt = mysqli_prepare($db, 'INSERT INTO orders (order_number, id_pengguna, customer_name, phone, address, subtotal, ongkir, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'sisssddds', $orderNumber, $userId, $customerName, $phone, $fullAddress, $subtotal, $ongkir, $total, $status);
            mysqli_stmt_execute($stmt);
            $orderId = mysqli_insert_id($db);
            mysqli_stmt_close($stmt);

            // 3) rincian barang + pengurangan stok
            foreach ($freshItems as $item) {
                $productId = (int)$item['id']; $name = $item['name'];
                $price = (float)$item['price']; $quantity = (int)$item['quantity']; $lineSubtotal = (float)$item['subtotal'];
                $stmt = mysqli_prepare($db, 'INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)');
                mysqli_stmt_bind_param($stmt, 'iisdid', $orderId, $productId, $name, $price, $quantity, $lineSubtotal);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $stmt = mysqli_prepare($db, 'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
                mysqli_stmt_bind_param($stmt, 'iii', $quantity, $productId, $quantity);
                mysqli_stmt_execute($stmt);
                if (mysqli_stmt_affected_rows($stmt) !== 1) {
                    mysqli_stmt_close($stmt);
                    throw new RuntimeException('Stok berubah. Silakan ulangi checkout.');
                }
                mysqli_stmt_close($stmt);
            }

            // 4) pengiriman terhubung ke pesanan
            $statusKirim = 'Menunggu';
            $stmt = mysqli_prepare($db, 'INSERT INTO pengiriman (id_pesanan, id_alamat, id_pengguna, id_kurir, tarif_ongkir, status) VALUES (?, ?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'iiiids', $orderId, $idAlamat, $userId, $tarif['id_kurir'], $ongkir, $statusKirim);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($db);
            $_SESSION['cart'] = [];
            $_SESSION['last_order'] = [
                'order_number' => $orderNumber, 'subtotal' => $subtotal, 'ongkir' => $ongkir, 'total' => $total,
                'nama_kurir' => $tarif['nama_kurir'], 'estimasi' => $tarif['estimasi'],
                'nama_zona' => $tarif['nama_zona'], 'nama_gudang' => $tarif['nama_gudang'],
            ];
            redirect('order_success.php');
        } catch (Throwable $e) {
            try { mysqli_rollback($db); } catch (Throwable $ignored) {}
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'Pesanan gagal disimpan. Periksa struktur tabel database.';
        }
    }
}

$items = load_cart_items($db);
$subtotal = array_sum(array_column($items, 'subtotal'));
$zonaPeta = ambilSemuaZona($db);
$extraHead = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
include('layout/header.php');
$oldLat = $_POST['latitude'] ?? '';
$oldLng = $_POST['longitude'] ?? '';
$oldKurir = (int)($_POST['id_kurir'] ?? 0);

?>
<div class="page-wrap"><div class="page-heading"><p class="section-eyebrow">LANGKAH TERAKHIR</p><h1>Checkout</h1><p>Isi data penerima, tandai lokasi tujuan di peta, lalu pilih kurir. Ongkir dihitung otomatis dari zona lokasimu.</p></div>
<?php if ($error !== ''): ?><div class="error-notice"><?= h($error) ?></div><?php endif; ?>
<div class="checkout-layout">
<form class="checkout-form" id="formCheckout" method="post" action="checkout.php"><?= csrf_field() ?>
    <input type="hidden" name="latitude" id="latitude" value="<?= h($oldLat) ?>">
    <input type="hidden" name="longitude" id="longitude" value="<?= h($oldLng) ?>">
    <h2>1. Informasi penerima</h2>
    <label>Nama lengkap<input type="text" name="customer_name" maxlength="120" required value="<?= h($_POST['customer_name'] ?? '') ?>"></label>
    <label>Nomor telepon<input type="tel" name="phone" maxlength="30" required value="<?= h($_POST['phone'] ?? '') ?>"></label>
    <label>Alamat lengkap<input type="text" name="alamat" maxlength="255" placeholder="Jalan, nomor rumah, kelurahan" required value="<?= h($_POST['alamat'] ?? '') ?>"></label>
    <label>Detail alamat (opsional)<textarea name="detail_alamat" rows="2" maxlength="1000" placeholder="Patokan, lantai, warna pagar..."><?= h($_POST['detail_alamat'] ?? '') ?></textarea></label>

    <h2>2. Tandai lokasi tujuan</h2>
    <p class="small-muted">Klik peta untuk menaruh pin. Area berwarna adalah zona layanan gudang kami.</p>
    <div id="map" class="map-box" aria-label="Peta pemilihan lokasi pengiriman"></div>
    <div class="coord-line">Koordinat: <code id="coordText">belum dipilih</code></div>

    <h2>3. Pilih kurir</h2>
    <div id="hasilOngkir" class="courier-result" aria-live="polite"><p class="small-muted">Pilihan kurir muncul setelah lokasi ditandai di peta.</p></div>

    <button class="button button-primary button-full" id="btnPesan" type="submit" disabled>Buat pesanan</button>
    <p class="small-muted">Demo ini belum memproses pembayaran online.</p>
</form>
<aside class="summary-card"><h2>Pesananmu</h2>
    <?php foreach ($items as $item): ?><div class="summary-line"><span><?= h($item['name']) ?> × <?= (int)$item['quantity'] ?></span><strong><?= rupiah($item['subtotal']) ?></strong></div><?php endforeach; ?>
    <div class="summary-line"><span>Subtotal barang</span><strong><?= rupiah($subtotal) ?></strong></div>
    <div class="summary-line"><span>Ongkir <small id="ongkirLabel"></small></span><strong id="ongkirText">Pilih lokasi &amp; kurir</strong></div>
    <div class="summary-line" id="gudangRow" hidden><span>Dikirim dari</span><strong id="gudangText"></strong></div>
    <div class="summary-total"><span>Total bayar</span><strong id="totalText" data-subtotal="<?= (float)$subtotal ?>"><?= rupiah($subtotal) ?></strong></div>
    <a class="back-link" href="cart.php">← Kembali ke keranjang</a>
</aside></div></div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const csrfToken = <?= json_encode($_SESSION['csrf_token']) ?>;
const zonaLayanan = <?= json_encode($zonaPeta, JSON_UNESCAPED_UNICODE) ?>;
let oldKurir = <?= (int)$oldKurir ?>;
const subtotal = Number(document.getElementById('totalText').dataset.subtotal);

const map = L.map('map').setView([-6.175, 106.8], 10);
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);

// Gambar zona layanan agar pembeli tahu area mana yang bisa dikirimi.
const zoneLayers = [];
const colors = ['#DAA520', '#2e8b57', '#4682b4', '#b5651d'];
zonaLayanan.forEach(function(z, i) {
  if (!z.titik || z.titik.length < 3) return;
  const poly = L.polygon(z.titik.map(t => [t.latitude, t.longitude]), { color: colors[i % colors.length], weight: 2, fillOpacity: 0.12 }).addTo(map);
  poly.bindTooltip(z.nama_zona + ' · ' + z.nama_gudang, { sticky: true });
  poly.on('click', function(e) { L.DomEvent.stopPropagation(e); pilihTitik(e.latlng.lat, e.latlng.lng); });
  zoneLayers.push(poly);
});
if (zoneLayers.length) map.fitBounds(L.featureGroup(zoneLayers).getBounds().pad(0.1));

const latInput = document.getElementById('latitude'), lngInput = document.getElementById('longitude');
const hasil = document.getElementById('hasilOngkir'), btnPesan = document.getElementById('btnPesan');
let marker = null, requestSeq = 0;

function esc(v) { return String(v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c])); }
function rp(n) { return 'Rp' + Number(n).toLocaleString('id-ID'); }

function resetRingkasan(pesanHtml) {
  btnPesan.disabled = true;
  document.getElementById('ongkirText').textContent = 'Pilih lokasi & kurir';
  document.getElementById('ongkirLabel').textContent = '';
  document.getElementById('gudangRow').hidden = true;
  document.getElementById('totalText').textContent = rp(subtotal);
  hasil.innerHTML = pesanHtml;
}

function updateRingkasan(zona, opsi) {
  document.getElementById('ongkirText').textContent = rp(opsi.harga);
  document.getElementById('ongkirLabel').textContent = '(' + opsi.nama_kurir + ')';
  document.getElementById('gudangText').textContent = zona.nama_gudang;
  document.getElementById('gudangRow').hidden = false;
  document.getElementById('totalText').textContent = rp(subtotal + opsi.harga);
  btnPesan.disabled = false;
}

async function pilihTitik(lat, lng) {
  latInput.value = lat; lngInput.value = lng;
  document.getElementById('coordText').textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
  if (marker) map.removeLayer(marker);
  marker = L.marker([lat, lng]).addTo(map);
  resetRingkasan('<p class="small-muted">Menghitung ongkir…</p>');
  const seq = ++requestSeq;
  try {
    const body = new URLSearchParams({ csrf_token: csrfToken, latitude: lat, longitude: lng });
    const resp = await fetch('hitung_ongkir.php', { method: 'POST', body });
    const result = await resp.json();
    if (seq !== requestSeq) return;               // ada klik yang lebih baru
    if (!resp.ok || !result.success) throw new Error(result.message || 'Gagal menghitung ongkir.');
    tampilkanKurir(result.data.zona, result.data.opsi);
  } catch (err) {
    if (seq !== requestSeq) return;
    resetRingkasan('<div class="error-notice">' + esc(err.message) + '</div>');
  }
}

function tampilkanKurir(zona, opsi) {
  let html = '<div class="zone-info">Zona: <strong>' + esc(zona.nama_zona) + '</strong> · dikirim dari <strong>' + esc(zona.nama_gudang) + '</strong></div><div class="courier-list">';
  opsi.forEach(function(o) {
    html += '<label class="courier-option"><input type="radio" name="id_kurir" value="' + o.id_kurir + '" required>'
          + '<span class="courier-name">' + esc(o.nama_kurir) + '<small>' + esc(o.estimasi) + '</small></span>'
          + '<strong>' + esc(o.harga_format) + '</strong></label>';
  });
  hasil.innerHTML = html + '</div>';
  hasil.querySelectorAll('input[name=id_kurir]').forEach(function(r) {
    r.addEventListener('change', function() { updateRingkasan(zona, opsi.find(o => String(o.id_kurir) === this.value)); });
  });
  const pre = opsi.find(o => o.id_kurir === oldKurir);   // pulihkan pilihan setelah error validasi
  oldKurir = 0;
  if (pre) { hasil.querySelector('input[value="' + pre.id_kurir + '"]').checked = true; updateRingkasan(zona, pre); }
  else btnPesan.disabled = true;
}

map.on('click', e => pilihTitik(e.latlng.lat, e.latlng.lng));
if (latInput.value && lngInput.value) pilihTitik(Number(latInput.value), Number(lngInput.value));   // pulihkan pin setelah error
</script>
<?php include('layout/footer.html') ?>
