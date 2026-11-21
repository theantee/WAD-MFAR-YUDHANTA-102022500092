<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['subtotal' => 'float', 'ongkir' => 'float', 'total' => 'float'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function pengiriman(): HasMany
    {
        return $this->hasMany(Pengiriman::class, 'id_pesanan', 'id')->orderBy('id_gudang');
    }
}
