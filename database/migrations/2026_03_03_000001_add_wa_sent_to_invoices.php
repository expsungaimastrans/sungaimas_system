<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom WhatsApp tracking ke invoices.
     *
     * Perubahan dari versi sebelumnya:
     * - Ditambahkan hasColumn check supaya idempotent. Sebelumnya kalau
     *   migrasi dijalankan ulang (atau kolom sudah ada dari sumber lain),
     *   akan crash "Duplicate column name".
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'wa_sent_to')) {
                $table->string('wa_sent_to')->nullable()->after('payment_proof_path');
            }
            if (!Schema::hasColumn('invoices', 'wa_sent_at')) {
                $table->timestamp('wa_sent_at')->nullable()->after('wa_sent_to');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            foreach (['wa_sent_to', 'wa_sent_at'] as $col) {
                if (Schema::hasColumn('invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};