<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * FIX duplikasi: File ini sebelumnya melakukan Schema::create('invoice_items')
     * yang konflik dengan 2026_02_16_000003.
     *
     * Solusi: dibuat idempotent. Kolom-kolom invoice_items sudah ditangani
     * oleh migrasi 02_16 dan disempurnakan oleh 2026_02_20_000002
     * (yang mengganti kolom 'amount' jadi 'nilai').
     */
    public function up(): void
    {
        if (Schema::hasTable('invoice_items')) {
            // Sudah dibuat 02_16; struktur final ditangani 02_20.
            return;
        }

        // Defensive: fresh DB tanpa 02_16
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipment_id')->constrained();
            $table->string('no_nota')->nullable();
            $table->string('penerima')->nullable();
            $table->string('tujuan')->nullable();
            $table->decimal('nilai', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['invoice_id', 'shipment_id']);
        });
    }

    public function down(): void
    {
        // Drop ditangani 2026_02_16_000003.
    }
};