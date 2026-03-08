<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->enum('tipe', ['PENGIRIM', 'PENERIMA']); // terpisah
            $table->string('no_telp')->nullable();
            $table->string('tujuan')->nullable();           // untuk PENERIMA
            $table->text('alamat')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['nama', 'tipe']);
            $table->index('tujuan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};