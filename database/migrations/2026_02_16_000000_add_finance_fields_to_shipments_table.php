<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // aman: hanya add kalau belum ada
            if (!Schema::hasColumn('shipments', 'tipe_bayar')) {
                $table->string('tipe_bayar', 20)->nullable()->after('status_pembayaran'); // COD / COT
            }

            if (!Schema::hasColumn('shipments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('tipe_bayar');
            }

            if (!Schema::hasColumn('shipments', 'bukti_bayar')) {
                $table->string('bukti_bayar', 255)->nullable()->after('paid_at'); // path file
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'bukti_bayar')) {
                $table->dropColumn('bukti_bayar');
            }
            if (Schema::hasColumn('shipments', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
            if (Schema::hasColumn('shipments', 'tipe_bayar')) {
                $table->dropColumn('tipe_bayar');
            }
        });
    }
};

