<?php
// Endpoint JSON: titik tujuan di peta -> zona, gudang asal, dan semua pilihan kurir + tarifnya.
require_once __DIR__ . '/bootstrap.php';
include('service/database.php');

if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Silakan masuk terlebih dahulu.'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    json_response(['success' => false, 'message' => 'Permintaan tidak valid. Muat ulang halaman dan coba lagi.'], 400);
}

$koordinat = bacaKoordinat($_POST['latitude'] ?? null, $_POST['longitude'] ?? null);
if (!$koordinat) {
    json_response(['success' => false, 'message' => 'Klik peta untuk memilih titik tujuan.'], 422);
}

try {
    $zona = cariZonaDariTitik($db, $koordinat[0], $koordinat[1]);
    if (!$zona) {
        json_response(['success' => false, 'message' => 'Titik berada di luar area layanan. Pilih titik di dalam area berwarna pada peta.'], 422);
    }
    $opsi = ambilOpsiKurir($db, $zona['id_zona']);
    if (!$opsi) {
        json_response(['success' => false, 'message' => 'Belum ada tarif kurir untuk zona ini.'], 422);
    }
    json_response(['success' => true, 'data' => ['zona' => $zona, 'opsi' => $opsi]]);
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => 'Terjadi kesalahan saat menghitung ongkir.'], 500);
}
