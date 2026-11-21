<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alamat tujuan, pesanan, rincian barang, dan pengiriman (satu per gudang asal).
     */
    public function up(): void
    {
        Schema::create('alamat_pengiriman', function (Blueprint $table) {
            $table->id('id_alamat');
            $table->foreignId('id_pengguna')->constrained('users');
            $table->foreignId('id_zona')->constrained('zona_ongkir', 'id_zona');
            $table->string('alamat', 255);
            $table->text('detail_alamat')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->foreignId('id_pengguna')->constrained('users');
            $table->string('customer_name', 120);
            $table->string('phone', 30);
            $table->text('address');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('ongkir', 12, 2)->default(0);   // jumlah ongkir semua pengiriman
            $table->decimal('total', 12, 2);
            $table->string('status', 30)->default('Menunggu pembayaran');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('id_gudang')->constrained('gudang', 'id_gudang');   // gudang asal saat barang dipesan
            $table->string('product_name', 120);
            $table->decimal('price', 12, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 12, 2);
        });

        // Satu pesanan bisa punya beberapa pengiriman: satu per gudang asal.
        Schema::create('pengiriman', function (Blueprint $table) {
            $table->id('id_pengiriman');
            $table->foreignId('id_pesanan')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('id_alamat')->constrained('alamat_pengiriman', 'id_alamat');
            $table->foreignId('id_pengguna')->constrained('users');
            $table->foreignId('id_gudang')->constrained('gudang', 'id_gudang');
            $table->foreignId('id_kurir')->constrained('kurir', 'id_kurir');
            $table->decimal('tarif_ongkir', 12, 2);
            $table->decimal('biaya_antar_pulau', 12, 2)->default(0);   // bagian dari tarif_ongkir
            $table->string('status', 30)->default('Menunggu');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['id_pesanan', 'id_gudang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengiriman');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('alamat_pengiriman');
    }
};
