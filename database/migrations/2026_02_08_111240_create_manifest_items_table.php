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
        Schema::create('manifest_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manifest_id')->constrained()->onDelete('cascade');
    
            // sesuai kolom pada contoh manifest
            $table->string('kode')->nullable();           // kode/resi
            $table->integer('koli')->default(0);          // Koli (Ø)
            $table->string('jenis_barang');               // ringkas isi
            $table->string('pengirim')->nullable();
            $table->decimal('kg', 10, 2)->default(0);     // Kg
            $table->string('penerima')->nullable();
            $table->string('tipe')->nullable();           // COD/COT
            $table->string('tujuan')->nullable();
            $table->decimal('harga', 15, 2)->default(0);
            $table->string('keterangan')->nullable();
    
            // opsional: link ke shipment (nota) jika mau tarik otomatis dari nota
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
    
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manifest_items');
    }
};
