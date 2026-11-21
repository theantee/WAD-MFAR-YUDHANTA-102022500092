<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['price' => 'float', 'quantity' => 'integer', 'subtotal' => 'float'];
    }
}
