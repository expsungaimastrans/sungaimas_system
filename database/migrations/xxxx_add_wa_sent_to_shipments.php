<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Kirim ke penerima
            $table->timestamp('wa_penerima_sent_at')->nullable()->after('keterangan');
            // Kirim ke pengirim
            $table->timestamp('wa_pengirim_sent_at')->nullable()->after('wa_penerima_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['wa_penerima_sent_at', 'wa_pengirim_sent_at']);
        });
    }
};