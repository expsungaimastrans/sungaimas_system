<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManifestBiayaOps extends Model
{
    protected $table = 'manifest_biaya_ops';

    protected $fillable = [
        'manifest_id',
        'lokasi',
        'jumlah',
        'keterangan',
    ];

    public function manifest()
    {
        return $this->belongsTo(Manifest::class);
    }
}