<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Barang contoh. Kolom id_gudang menentukan gudang asal tiap barang. Menjalankan ulang memperbarui data. */
class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        $produk = json_decode(file_get_contents(database_path('data/produk.json')), true, flags: JSON_THROW_ON_ERROR);

        DB::table('products')->upsert(
            $produk,
            ['id'],
            ['name', 'description', 'price', 'stock', 'category', 'condition_label', 'size_label', 'likes', 'image_path', 'emoji', 'active', 'id_gudang'],
        );
    }
}
