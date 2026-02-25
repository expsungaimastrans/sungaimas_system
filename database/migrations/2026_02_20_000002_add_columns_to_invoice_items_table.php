<?php
// FILE: database/migrations/2026_02_20_000002_add_columns_to_invoice_items_table.php
// Ganti SELURUH ISI file ini dengan versi di bawah

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_items', 'no_nota')) {
                $table->string('no_nota')->nullable()->after('shipment_id');
            }
            if (!Schema::hasColumn('invoice_items', 'penerima')) {
                $table->string('penerima')->nullable()->after('no_nota');
            }
            if (!Schema::hasColumn('invoice_items', 'tujuan')) {
                $table->string('tujuan')->nullable()->after('penerima');
            }
            if (!Schema::hasColumn('invoice_items', 'nilai')) {
                $table->decimal('nilai', 15, 2)->default(0)->after('tujuan');
            }
            // Hapus kolom amount jika masih ada
            if (Schema::hasColumn('invoice_items', 'amount')) {
                $table->dropColumn('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            foreach (['no_nota','penerima','tujuan','nilai'] as $col) {
                if (Schema::hasColumn('invoice_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};