<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'tipe_bayar')) {
                // COD / COT
                $table->string('tipe_bayar', 10)->default('COD')->after('status_pembayaran');
            }
            if (!Schema::hasColumn('shipments', 'bukti_bayar_path')) {
                $table->string('bukti_bayar_path')->nullable()->after('tipe_bayar');
            }
            if (!Schema::hasColumn('shipments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('bukti_bayar_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'paid_at')) $table->dropColumn('paid_at');
            if (Schema::hasColumn('shipments', 'bukti_bayar_path')) $table->dropColumn('bukti_bayar_path');
            if (Schema::hasColumn('shipments', 'tipe_bayar')) $table->dropColumn('tipe_bayar');
        });
    }
};
