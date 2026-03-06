<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manifest extends Model
{
    protected $fillable = [
        'no_manifest','manifest_ke','sopir','nopol',
        'tanggal_muat','nama_kapal','keberangkatan',
        'status', // PERSIAPAN | DALAM_PERJALANAN | SELESAI
    ];

    public function items()
    {
        return $this->hasMany(ManifestItem::class);
    }
}
