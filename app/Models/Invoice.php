<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_no','manifest_id','billed_to','status','total','payment_proof_path','paid_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function manifest()
    {
        return $this->belongsTo(Manifest::class);
    }
}
