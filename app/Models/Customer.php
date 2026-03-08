<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'tipe',
        'no_telp',
        'tujuan',
        'alamat',
        'catatan',
    ];

    // Relasi ke shipments sebagai penerima
    public function shipmentsAsPenerima()
    {
        return $this->hasMany(Shipment::class, 'nama_penerima', 'nama');
    }

    // Relasi ke shipments sebagai pengirim
    public function shipmentsAsPengirim()
    {
        return $this->hasMany(Shipment::class, 'nama_pengirim', 'nama');
    }

    // Scope untuk pencarian
    public function scopeSearch($query, $keyword)
    {
        return $query->where('nama', 'like', "%{$keyword}%")
                     ->orWhere('tujuan', 'like', "%{$keyword}%")
                     ->orWhere('no_telp', 'like', "%{$keyword}%");
    }
}