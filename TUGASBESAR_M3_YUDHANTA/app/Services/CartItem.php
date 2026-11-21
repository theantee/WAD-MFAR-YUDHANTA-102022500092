<?php

namespace App\Services;

use App\Models\Product;

/** Satu baris keranjang: produk + jumlah. Harga selalu dibaca dari database. */
final class CartItem
{
    public function __construct(
        public readonly Product $product,
        public readonly int $quantity,
    ) {
    }

    public function subtotal(): float
    {
        return $this->product->price * $this->quantity;
    }
}
