<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu pengiriman = satu gudang asal dalam satu pesanan. */
class Pengiriman extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'pengiriman';
    protected $primaryKey = 'id_pengiriman';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['tarif_ongkir' => 'float', 'biaya_antar_pulau' => 'float'];
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'id_gudang', 'id_gudang');
    }

    public function kurir(): BelongsTo
    {
        return $this->belongsTo(Kurir::class, 'id_kurir', 'id_kurir');
    }

    public function alamat(): BelongsTo
    {
        return $this->belongsTo(AlamatPengiriman::class, 'id_alamat', 'id_alamat');
    }
}
