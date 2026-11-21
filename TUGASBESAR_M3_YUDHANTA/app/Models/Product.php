<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    public const UPDATED_AT = null;   // tabel hanya punya created_at

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'stock' => 'integer',
            'likes' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'id_gudang', 'id_gudang');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /** URL gambar produk di public/assets/products. */
    public function imageUrl(): string
    {
        return asset('assets/products/'.basename($this->image_path));
    }
}
