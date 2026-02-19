<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
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
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            foreach (['invoice_no','billed_to','manifest_id','status','payment_proof_path','paid_at'] as $col) {
                if (Schema::hasColumn('invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};