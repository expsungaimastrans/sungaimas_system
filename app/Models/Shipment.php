<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;
    // ✅ biar semua kolom bisa diupdate (termasuk status & manifest_id)
    // protected $guarded = [];


    protected $fillable = [
        'no_nota',
        'tanggal',
        'nama_pengirim',
        'telp_pengirim',
        'nama_penerima',
        'telp_penerima',
        'alamat_penerima',
        'tujuan',
        'nama_barang',
        'jumlah',
        'satuan',
        'harga_total',
        'keterangan',
        'admin_id',
        'status_pengiriman',
        'status_pembayaran',
        'manifest_id',
    ];

    public function items()
{
    return $this->hasMany(ShipmentItem::class);
}

public function manifest()
{
    return $this->belongsTo(\App\Models\Manifest::class, 'manifest_id');
}

public function manifestItem()
{
    return $this->hasOne(\App\Models\ManifestItem::class);
}

public function logs()
{
    return $this->hasMany(\App\Models\ShipmentLog::class)->orderBy('logged_at','desc');
}

public function invoiceItems()
{
    return $this->hasMany(\App\Models\InvoiceItem::class, 'shipment_id');
}


}