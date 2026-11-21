<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Zona tujuan = satu kota. Poligonnya ada di tabel titik_zona. */
class ZonaOngkir extends Model
{
    protected $table = 'zona_ongkir';
    protected $primaryKey = 'id_zona';
    public $timestamps = false;

    public function pulau(): BelongsTo
    {
        return $this->belongsTo(Pulau::class, 'id_pulau', 'id_pulau');
    }

    public function titik(): HasMany
    {
        return $this->hasMany(TitikZona::class, 'id_zona', 'id_zona')->orderBy('urutan');
    }
}
