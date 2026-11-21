<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Kelompok pulau (Jawa, Sumatera, ...). */
class Pulau extends Model
{
    protected $table = 'pulau';
    protected $primaryKey = 'id_pulau';
    public $timestamps = false;
}
