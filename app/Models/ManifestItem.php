<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManifestItem extends Model
{
    protected $fillable = [
        'manifest_id','shipment_id','kode','koli','jenis_barang','pengirim','kg',
        'penerima','tipe','tujuan','harga','keterangan'
    ];

    public function manifest()
    {
        return $this->belongsTo(Manifest::class);
    }
    public function shipment()
{
    return $this->belongsTo(\App\Models\Shipment::class);
}

}

