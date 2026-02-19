<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
        });

        // Migrasi data lama: salin amount -> nilai
        DB::statement("UPDATE invoice_items SET nilai = amount WHERE nilai = 0 OR nilai IS NULL");

        // Isi no_nota, penerima, tujuan dari tabel shipments
        DB::statement("
            UPDATE invoice_items ii
            JOIN shipments s ON s.id = ii.shipment_id
            SET
                ii.no_nota   = s.no_nota,
                ii.penerima  = s.nama_penerima,
                ii.tujuan    = s.tujuan
            WHERE ii.no_nota IS NULL OR ii.no_nota = ''
        ");
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