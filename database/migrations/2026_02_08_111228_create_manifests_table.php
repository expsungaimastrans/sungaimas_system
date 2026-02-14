<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('manifests', function (Blueprint $table) {
        $table->id();
        $table->string('no_manifest')->unique(); // contoh: 2025120105
        $table->integer('manifest_ke')->index(); // contoh: 105 (untuk judul "MANIFEST 105")
        $table->string('sopir')->nullable();
        $table->string('nopol')->nullable();
        $table->date('tanggal_muat');
        $table->string('nama_kapal')->nullable();
        $table->dateTime('keberangkatan')->nullable();
        $table->timestamps();
    });
}

};
