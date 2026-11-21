<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kurir extends Model
{
    protected $table = 'kurir';
    protected $primaryKey = 'id_kurir';
    public $timestamps = false;

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }
}
