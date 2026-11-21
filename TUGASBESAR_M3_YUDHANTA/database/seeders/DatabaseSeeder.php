<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ReferensiPengirimanSeeder::class,   // pulau, gudang, kurir, 98 zona kota + poligon, 1.176 tarif
            ProdukSeeder::class,                // 15 barang contoh, masing-masing milik satu gudang
        ]);
    }
}