<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data referensi pengiriman: pulau, gudang (vendor), kurir, zona tujuan (kota) + poligonnya, dan tarif.
     */
    public function up(): void
    {
        // Kelompok pulau. Pengiriman antar kelompok pulau dikenai biaya antar pulau.
        Schema::create('pulau', function (Blueprint $table) {
            $table->id('id_pulau');
            $table->string('nama_pulau', 60)->unique();
        });

        // Gudang = lokasi asal barang (seperti vendor di marketplace).
        Schema::create('gudang', function (Blueprint $table) {
            $table->id('id_gudang');
            $table->string('nama_gudang', 100);
            $table->foreignId('id_pulau')->constrained('pulau', 'id_pulau');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->boolean('aktif')->default(true);
        });

        Schema::create('kurir', function (Blueprint $table) {
            $table->id('id_kurir');
            $table->string('nama_kurir', 80);
            $table->string('estimasi', 60);
            $table->boolean('aktif')->default(true);
        });

        // Zona = satu kota TUJUAN pembeli. Poligonnya disimpan di titik_zona.
        Schema::create('zona_ongkir', function (Blueprint $table) {
            $table->id('id_zona');
            $table->string('nama_zona', 100)->unique();
            $table->string('provinsi', 80);
            $table->foreignId('id_pulau')->constrained('pulau', 'id_pulau');
        });

        Schema::create('titik_zona', function (Blueprint $table) {
            $table->id('id_titik');
            $table->foreignId('id_zona')->constrained('zona_ongkir', 'id_zona')->cascadeOnDelete();
            $table->unsignedInteger('urutan');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unique(['id_zona', 'urutan']);
        });

        // Tarif = (gudang asal, zona tujuan, kurir) beserta rincian: harga = dasar + jarak + antar pulau.
        Schema::create('tarif_ongkir', function (Blueprint $table) {
            $table->id('id_tarif');
            $table->foreignId('id_gudang')->constrained('gudang', 'id_gudang');
            $table->foreignId('id_zona')->constrained('zona_ongkir', 'id_zona')->cascadeOnDelete();
            $table->foreignId('id_kurir')->constrained('kurir', 'id_kurir');
            $table->unsignedInteger('jarak_km')->default(0);
            $table->decimal('biaya_dasar', 12, 2)->default(0);
            $table->decimal('biaya_jarak', 12, 2)->default(0);
            $table->decimal('biaya_antar_pulau', 12, 2)->default(0);
            $table->decimal('harga', 12, 2);
            $table->unique(['id_gudang', 'id_zona', 'id_kurir']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarif_ongkir');
        Schema::dropIfExists('titik_zona');
        Schema::dropIfExists('zona_ongkir');
        Schema::dropIfExists('kurir');
        Schema::dropIfExists('gudang');
        Schema::dropIfExists('pulau');
    }
};
