<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manifests', function (Blueprint $table) {
            $table->string('status')->default('PERSIAPAN')->after('keberangkatan');
            // PERSIAPAN | DALAM_PERJALANAN | SELESAI
        });

        // Manifest yang sudah ada dan punya shipment DALAM_PENGIRIMAN → DALAM_PERJALANAN
        DB::statement("
            UPDATE manifests m
            SET m.status = 'DALAM_PERJALANAN'
            WHERE EXISTS (
                SELECT 1 FROM manifest_items mi
                JOIN shipments s ON s.id = mi.shipment_id
                WHERE mi.manifest_id = m.id
                AND s.status_pengiriman = 'DALAM_PENGIRIMAN'
            )
        ");
    }

    public function down(): void
    {
        Schema::table('manifests', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};