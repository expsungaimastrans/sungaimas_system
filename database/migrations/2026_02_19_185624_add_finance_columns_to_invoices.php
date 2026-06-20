<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan kolom finance baru ke invoices + index/unique constraint.
     *
     * Perubahan dari versi sebelumnya:
     * - Sebelumnya invoice_no & manifest_id tidak diberi unique/index
     *   karena dianggap sudah ada dari migrasi 02_17. Tapi karena 02_17
     *   sekarang idempotent (no-op pada DB yang sudah ada), constraint
     *   tersebut tidak terbentuk. Ditambahkan di sini secara defensive.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'invoice_no')) {
                $table->string('invoice_no')->nullable()->after('id');
            }
            if (!Schema::hasColumn('invoices', 'billed_to')) {
                $table->string('billed_to', 120)->nullable()->after('invoice_no');
            }
            if (!Schema::hasColumn('invoices', 'manifest_id')) {
                $table->unsignedBigInteger('manifest_id')->nullable()->after('billed_to');
            }
            if (!Schema::hasColumn('invoices', 'status')) {
                $table->string('status', 30)->default('BELUM_DITAGIH')->after('total');
            }
            if (!Schema::hasColumn('invoices', 'payment_proof_path')) {
                $table->string('payment_proof_path')->nullable()->after('status');
            }
            if (!Schema::hasColumn('invoices', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_proof_path');
            }
        });

        // Migrasi data lama ke kolom baru
        DB::statement("UPDATE invoices SET invoice_no = no_invoice WHERE invoice_no IS NULL OR invoice_no = ''");
        DB::statement("UPDATE invoices SET billed_to = customer WHERE billed_to IS NULL OR billed_to = ''");
        DB::statement("UPDATE invoices SET status = 'BELUM_DITAGIH' WHERE status IS NULL OR status = ''");

        // Tambahkan constraint & index (try-catch karena mungkin sudah ada
        // dari migrasi 02_17 versi sebelumnya yang sempat jalan)
        Schema::table('invoices', function (Blueprint $table) {
            try {
                $table->unique('invoice_no', 'invoices_invoice_no_unique');
            } catch (\Throwable $e) {
                // Sudah ada — abaikan
            }

            try {
                $table->index('manifest_id', 'invoices_manifest_id_index');
            } catch (\Throwable $e) {
                // Sudah ada — abaikan
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            try { $table->dropUnique('invoices_invoice_no_unique'); } catch (\Throwable $e) {}
            try { $table->dropIndex('invoices_manifest_id_index'); } catch (\Throwable $e) {}

            foreach (['invoice_no','billed_to','manifest_id','status','payment_proof_path','paid_at'] as $col) {
                if (Schema::hasColumn('invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};