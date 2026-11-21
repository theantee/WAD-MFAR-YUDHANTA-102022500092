<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tarif satu pasangan (gudang asal, zona tujuan, kurir) beserta rinciannya. */
class TarifOngkir extends Model
{
    protected $table = 'tarif_ongkir';
    protected $primaryKey = 'id_tarif';
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'jarak_km' => 'integer',
            'biaya_dasar' => 'float',
            'biaya_jarak' => 'float',
            'biaya_antar_pulau' => 'float',
            'harga' => 'float',
        ];
    }

    public function kurir(): BelongsTo
    {
        return $this->belongsTo(Kurir::class, 'id_kurir', 'id_kurir');
    }
}
