<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Memuat data referensi pengiriman dari database/data/*.json.
 * Aman dijalankan ulang (memakai insertOrIgnore, tidak menggandakan data).
 */
class ReferensiPengirimanSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('pulau')->insertOrIgnore($this->baca('pulau.json'));
        DB::table('gudang')->insertOrIgnore($this->baca('gudang.json'));
        DB::table('kurir')->insertOrIgnore($this->baca('kurir.json'));
        DB::table('zona_ongkir')->insertOrIgnore($this->baca('zona.json'));

        // Poligon zona: file menyimpan daftar [lat, lng] per zona -> dijadikan baris titik_zona.
        $titik = [];
        foreach ($this->baca('titik_zona.json') as $zona) {
            foreach ($zona['titik'] as $i => [$lat, $lng]) {
                $titik[] = ['id_zona' => $zona['id_zona'], 'urutan' => $i + 1, 'latitude' => $lat, 'longitude' => $lng];
            }
        }
        foreach (array_chunk($titik, 500) as $potongan) {
            DB::table('titik_zona')->insertOrIgnore($potongan);
        }

        foreach (array_chunk($this->baca('tarif.json'), 500) as $potongan) {
            DB::table('tarif_ongkir')->insertOrIgnore($potongan);
        }
    }

    private function baca(string $file): array
    {
        return json_decode(file_get_contents(database_path('data/'.$file)), true, flags: JSON_THROW_ON_ERROR);
    }
}
