<?php

namespace App\Services;

use App\Models\TarifOngkir;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Zona tujuan (kota di seluruh Indonesia) dan tarif ongkir.
 *
 * Tiap barang punya gudang asal. Pembeli menandai titik tujuan di peta -> dicari zona (kota) yang
 * memuat titik itu. Ongkir dihitung per gudang asal dari tabel tarif_ongkir:
 *   harga = biaya_dasar + biaya_jarak + biaya_antar_pulau   (antar pulau hanya bila beda kelompok pulau)
 */
class ShippingService
{
    /** @var array<int,array>|null */
    private ?array $zonas = null;

    /**
     * Semua zona beserta poligon dan kotak pembatasnya (dipakai untuk peta dan pencarian zona).
     *
     * @return array<int,array{id_zona:int,nama_zona:string,provinsi:string,id_pulau:int,nama_pulau:string,titik:array,bbox:array}>
     */
    public function zonas(): array
    {
        if ($this->zonas !== null) {
            return $this->zonas;
        }

        $zonas = [];
        $rows = DB::table('zona_ongkir as z')
            ->join('pulau as p', 'p.id_pulau', '=', 'z.id_pulau')
            ->orderBy('z.id_zona')
            ->get(['z.id_zona', 'z.nama_zona', 'z.provinsi', 'z.id_pulau', 'p.nama_pulau']);

        foreach ($rows as $r) {
            $zonas[(int) $r->id_zona] = [
                'id_zona' => (int) $r->id_zona,
                'nama_zona' => $r->nama_zona,
                'provinsi' => $r->provinsi,
                'id_pulau' => (int) $r->id_pulau,
                'nama_pulau' => $r->nama_pulau,
                'titik' => [],
                'bbox' => [INF, INF, -INF, -INF],   // minLat, minLng, maxLat, maxLng
            ];
        }

        $points = DB::table('titik_zona')->orderBy('id_zona')->orderBy('urutan')->get(['id_zona', 'latitude', 'longitude']);
        foreach ($points as $t) {
            $id = (int) $t->id_zona;
            if (! isset($zonas[$id])) {
                continue;
            }
            $lat = (float) $t->latitude;
            $lng = (float) $t->longitude;
            $zonas[$id]['titik'][] = ['latitude' => $lat, 'longitude' => $lng];
            $b = &$zonas[$id]['bbox'];
            $b = [min($b[0], $lat), min($b[1], $lng), max($b[2], $lat), max($b[3], $lng)];
            unset($b);
        }

        return $this->zonas = array_values($zonas);
    }

    /** Zona yang memuat titik tujuan, atau null jika di luar jangkauan layanan. */
    public function findZona(float $lat, float $lng): ?array
    {
        foreach ($this->zonas() as $zona) {
            [$minLat, $minLng, $maxLat, $maxLng] = $zona['bbox'];
            if ($lat < $minLat || $lat > $maxLat || $lng < $minLng || $lng > $maxLng) {
                continue;   // di luar kotak pembatas, tidak perlu uji poligon
            }
            if ($this->pointInPolygon($lat, $lng, $zona['titik'])) {
                return [
                    'id_zona' => $zona['id_zona'],
                    'nama_zona' => $zona['nama_zona'],
                    'provinsi' => $zona['provinsi'],
                    'id_pulau' => $zona['id_pulau'],
                    'nama_pulau' => $zona['nama_pulau'],
                ];
            }
        }

        return null;
    }

    /** Titik-dalam-poligon (ray casting). Titik tepat di garis batas dianggap di luar. */
    public function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $n = count($polygon);
        if ($n < 3) {
            return false;
        }

        $inside = false;
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $latI = $polygon[$i]['latitude'];
            $lngI = $polygon[$i]['longitude'];
            $latJ = $polygon[$j]['latitude'];
            $lngJ = $polygon[$j]['longitude'];

            if ((($latI > $lat) !== ($latJ > $lat))
                && ($lng < ($lngJ - $lngI) * ($lat - $latI) / ($latJ - $latI) + $lngI)) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Pilihan kurir aktif beserta tarifnya untuk satu pasangan (gudang asal, zona tujuan), termurah dulu.
     *
     * @return Collection<int,TarifOngkir>
     */
    public function options(int $idGudang, int $idZona): Collection
    {
        return TarifOngkir::query()
            ->join('kurir', 'kurir.id_kurir', '=', 'tarif_ongkir.id_kurir')
            ->join('gudang', 'gudang.id_gudang', '=', 'tarif_ongkir.id_gudang')
            ->where('tarif_ongkir.id_gudang', $idGudang)
            ->where('tarif_ongkir.id_zona', $idZona)
            ->where('kurir.aktif', true)
            ->where('gudang.aktif', true)
            ->orderBy('tarif_ongkir.harga')
            ->get(['tarif_ongkir.*', 'kurir.nama_kurir', 'kurir.estimasi']);
    }

    /** Tarif satu kurir tertentu, atau null bila tidak tersedia untuk pasangan itu. */
    public function quote(int $idGudang, int $idZona, int $idKurir): ?TarifOngkir
    {
        return $this->options($idGudang, $idZona)->firstWhere('id_kurir', $idKurir);
    }

    /** Bentuk array untuk JSON ke browser. */
    public function optionToArray(TarifOngkir $t): array
    {
        return [
            'id_kurir' => (int) $t->id_kurir,
            'nama_kurir' => $t->nama_kurir,
            'estimasi' => $t->estimasi,
            'harga' => $t->harga,
            'harga_format' => rupiah($t->harga),
            'jarak_km' => $t->jarak_km,
            'biaya_dasar' => $t->biaya_dasar,
            'biaya_jarak' => $t->biaya_jarak,
            'biaya_antar_pulau' => $t->biaya_antar_pulau,
        ];
    }
}
