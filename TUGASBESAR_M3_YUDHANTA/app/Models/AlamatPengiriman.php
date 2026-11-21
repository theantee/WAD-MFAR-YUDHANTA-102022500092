<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlamatPengiriman extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'alamat_pengiriman';
    protected $primaryKey = 'id_alamat';
    protected $guarded = [];

    public function zona(): BelongsTo
    {
        return $this->belongsTo(ZonaOngkir::class, 'id_zona', 'id_zona');
    }
}
