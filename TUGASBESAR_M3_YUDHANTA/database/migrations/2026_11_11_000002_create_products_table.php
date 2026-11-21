<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Katalog barang. Setiap barang milik satu gudang (id_gudang).
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->text('description');
            $table->decimal('price', 12, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->string('category', 100)->default('Lainnya');
            $table->string('condition_label', 50)->default('Baik');
            $table->string('size_label', 20)->default('-');
            $table->integer('likes')->default(0);
            $table->string('image_path', 160)->default('product.svg');
            $table->string('emoji', 20)->default('🛍️');
            $table->boolean('active')->default(true);
            $table->foreignId('id_gudang')->constrained('gudang', 'id_gudang');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
