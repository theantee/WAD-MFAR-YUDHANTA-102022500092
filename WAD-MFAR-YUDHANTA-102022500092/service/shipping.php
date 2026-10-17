<?php
/**
 * Layanan zona & ongkir (diambil dari KirimAja, disesuaikan untuk ShopKu).
 *
 * Perbedaan dari KirimAja: pembeli tidak lagi memilih gudang secara manual.
 * Gudang ditentukan otomatis dari titik tujuan di peta, yaitu gudang yang
 * poligon zonanya memuat titik tersebut. Karena itu zona antar gudang tidak
 * boleh tumpang tindih.
 */

/** Titik-dalam-poligon (ray casting). Titik tepat di garis batas dianggap di luar. */
function titikDalamPoligon(float $lat, float $lng, array $titik): bool
{
    $jumlah = count($titik);
    if ($jumlah < 3) {
        return false;
    }
    $dalam = false;
    for ($i = 0, $j = $jumlah - 1; $i < $jumlah; $j = $i++) {
        $latI = (float)$titik[$i]['latitude'];
        $lngI = (float)$titik[$i]['longitude'];
        $latJ = (float)$titik[$j]['latitude'];
        $lngJ = (float)$titik[$j]['longitude'];
        $memotong = (($latI > $lat) !== ($latJ > $lat))
            && ($lng < (($lngJ - $lngI) * ($lat - $latI) / ($latJ - $latI) + $lngI));
        if ($memotong) {
            $dalam = !$dalam;
        }
    }
    return $dalam;
}

/** Semua zona aktif beserta titik poligonnya (juga dipakai untuk menggambar peta). */
function ambilSemuaZona(mysqli $db): array
{
    $zona = [];
    $res = mysqli_query(
        $db,
        'SELECT z.id_zona, z.nama_zona, g.id_gudang, g.nama_gudang
         FROM zona_ongkir z JOIN gudang g ON g.id_gudang = z.id_gudang
         WHERE g.aktif = 1 ORDER BY z.id_zona'
    );
    while ($row = mysqli_fetch_assoc($res)) {
        $row['titik'] = [];
        $zona[(int)$row['id_zona']] = $row;
    }
    $res = mysqli_query($db, 'SELECT id_zona, latitude, longitude FROM titik_zona ORDER BY id_zona, urutan');
    while ($row = mysqli_fetch_assoc($res)) {
        $id = (int)$row['id_zona'];
        if (isset($zona[$id])) {
            $zona[$id]['titik'][] = ['latitude' => (float)$row['latitude'], 'longitude' => (float)$row['longitude']];
        }
    }
    return array_values($zona);
}

/** Zona (dan gudangnya) yang memuat titik tujuan, atau null jika di luar jangkauan. */
function cariZonaDariTitik(mysqli $db, float $lat, float $lng): ?array
{
    foreach (ambilSemuaZona($db) as $zona) {
        if (titikDalamPoligon($lat, $lng, $zona['titik'])) {
            return [
                'id_zona' => (int)$zona['id_zona'],
                'nama_zona' => $zona['nama_zona'],
                'id_gudang' => (int)$zona['id_gudang'],
                'nama_gudang' => $zona['nama_gudang'],
            ];
        }
    }
    return null;
}

/** Semua pilihan kurir aktif beserta tarifnya untuk sebuah zona. */
function ambilOpsiKurir(mysqli $db, int $idZona): array
{
    $stmt = mysqli_prepare(
        $db,
        'SELECT k.id_kurir, k.nama_kurir, k.estimasi, t.harga
         FROM tarif_ongkir t JOIN kurir k ON k.id_kurir = t.id_kurir
         WHERE t.id_zona = ? AND k.aktif = 1 ORDER BY t.harga ASC'
    );
    mysqli_stmt_bind_param($stmt, 'i', $idZona);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $opsi = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $opsi[] = [
            'id_kurir' => (int)$row['id_kurir'],
            'nama_kurir' => $row['nama_kurir'],
            'estimasi' => $row['estimasi'],
            'harga' => (float)$row['harga'],
            'harga_format' => 'Rp' . number_format((float)$row['harga'], 0, ',', '.'),
        ];
    }
    mysqli_stmt_close($stmt);
    return $opsi;
}

/**
 * Titik tujuan + kurir pilihan -> zona, gudang, dan tarif.
 * Dipakai ulang di server saat checkout agar harga dari browser tidak dipercaya.
 */
function hitungOngkir(mysqli $db, float $lat, float $lng, int $idKurir): ?array
{
    $zona = cariZonaDariTitik($db, $lat, $lng);
    if (!$zona) {
        return null;
    }
    foreach (ambilOpsiKurir($db, $zona['id_zona']) as $opsi) {
        if ($opsi['id_kurir'] === $idKurir) {
            return $zona + $opsi;
        }
    }
    return null;
}

/** Validasi koordinat dari form. Mengembalikan [lat, lng] atau null. */
function bacaKoordinat($latRaw, $lngRaw): ?array
{
    if (!is_numeric($latRaw) || !is_numeric($lngRaw)) {
        return null;
    }
    $lat = (float)$latRaw;
    $lng = (float)$lngRaw;
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        return null;
    }
    return [$lat, $lng];
}
