<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * FIX duplikasi: File ini sebelumnya melakukan Schema::create('invoices')
     * yang konflik dengan 2026_02_16_000002. Pada DB fresh, migrasi akan
     * crash dengan error "table already exists".
     *
     * Solusi: dibuat idempotent — Schema::create hanya dijalankan kalau
     * tabel benar-benar belum ada (edge case bila migrasi 02_16 sengaja
     * dilewat). Pada kasus normal, file ini jadi no-op. Penambahan kolom
     * finance dilakukan oleh migrasi 2026_02_19_185624.
     */
    public function up(): void
    {
        if (Schema::hasTable('invoices')) {
            // Tabel sudah dibuat oleh 2026_02_16_000002.
            // Kolom-kolom baru (invoice_no, billed_to, status, dst.)
            // akan ditambahkan oleh migrasi 2026_02_19_185624.
            return;
        }

        // Defensive: fresh DB tanpa 02_16 (seharusnya tidak terjadi)
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->unsignedBigInteger('manifest_id')->nullable()->index();
            $table->string('billed_to', 120);
            $table->string('status', 30)->default('BELUM_DITAGIH');
            $table->decimal('total', 15, 2)->default(0);
            $table->string('payment_proof_path')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Tidak drop di sini — drop ditangani migrasi 2026_02_16_000002
        // agar tidak terjadi double-drop.
    }
};