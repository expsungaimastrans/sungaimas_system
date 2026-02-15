<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();

            $table->string('no_nota');
            $table->string('penerima')->nullable();
            $table->string('tujuan')->nullable();
            $table->decimal('nilai', 15, 2)->default(0);

            $table->timestamps();

            // cegah nota sama masuk 1 invoice 2 kali
            $table->unique(['invoice_id', 'shipment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
