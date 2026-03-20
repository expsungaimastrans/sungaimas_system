<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manifest_biaya_ops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manifest_id')->constrained()->onDelete('cascade');
            $table->string('lokasi');         // Mbay, Labuan Bajo, Ende
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['manifest_id', 'lokasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manifest_biaya_ops');
    }
};