<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Gudang = lokasi asal barang (vendor). */
class Gudang extends Model
{
    protected $table = 'gudang';
    protected $primaryKey = 'id_gudang';
    public $timestamps = false;

    protected function casts(): array
    {
        return ['aktif' => 'boolean', 'latitude' => 'float', 'longitude' => 'float'];
    }

    public function pulau(): BelongsTo
    {
        return $this->belongsTo(Pulau::class, 'id_pulau', 'id_pulau');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'id_gudang', 'id_gudang');
    }
}
