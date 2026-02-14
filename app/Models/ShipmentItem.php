<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentItem extends Model
{
    protected $fillable = [
        'nama_barang',
        'koli',
        'berat_kg',
        'kubikasi_m3',
        'satuan',         // satuan barang (Dus/Karung) kalau masih dipakai
        'satuan_tarif',   // kg/kubik/unit
        'harga_satuan',
        'subtotal',
      ];
      

    public function shipment()
{
    return $this->belongsTo(Shipment::class);
}

}

