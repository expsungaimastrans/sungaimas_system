<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentLog extends Model
{
    protected $guarded = [];
    protected $casts = [
        'meta' => 'array',
        'logged_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}

