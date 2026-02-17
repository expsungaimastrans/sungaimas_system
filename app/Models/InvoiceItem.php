<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = ['invoice_id','shipment_id','amount'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function shipment()
{
    return $this->belongsTo(\App\Models\Shipment::class, 'shipment_id');
}

    

}
