<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('wa_sent_to')->nullable()->after('payment_proof_path');
            $table->timestamp('wa_sent_at')->nullable()->after('wa_sent_to');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['wa_sent_to', 'wa_sent_at']);
        });
    }
};