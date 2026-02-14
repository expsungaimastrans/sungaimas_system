<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('shipments', function (Blueprint $table) {
        $table->id();
        $table->string('no_nota')->unique();
        $table->date('tanggal');
        $table->string('nama_pengirim');
        $table->string('telp_pengirim')->nullable();
        $table->string('nama_penerima');
        $table->string('telp_penerima');
        $table->string('tujuan');
        $table->string('nama_barang');
        $table->integer('jumlah');
        $table->string('satuan');
        $table->decimal('harga_total', 15, 2);
        $table->text('keterangan')->nullable();
        $table->unsignedBigInteger('admin_id')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
