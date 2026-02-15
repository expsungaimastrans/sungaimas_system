<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id','shipment_id','no_nota','penerima','tujuan','nilai'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}
