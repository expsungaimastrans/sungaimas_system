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
    Schema::create('shipment_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
        $table->string('nama_barang');
        $table->integer('jumlah');
        $table->string('satuan');
        $table->integer('harga_satuan');
        $table->integer('subtotal');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
    }
};
