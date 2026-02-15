<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'tipe_bayar')) {
                $table->string('tipe_bayar', 20)->nullable()->after('status_pembayaran');
            }
            if (!Schema::hasColumn('shipments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('tipe_bayar');
            }
            if (!Schema::hasColumn('shipments', 'bukti_bayar')) {
                $table->string('bukti_bayar')->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void {}
};