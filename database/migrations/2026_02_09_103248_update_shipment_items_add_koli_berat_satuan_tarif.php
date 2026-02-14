<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            // rename kolom jumlah -> koli (kalau ada)
            if (Schema::hasColumn('shipment_items', 'jumlah')) {
                $table->renameColumn('jumlah', 'koli');
            }

            // satuan barang tetap (dus, karung, dll) - opsional, kalau masih kamu pakai
            // kalau tidak perlu, boleh dihapus belakangan

            // kolom baru untuk tarif
            if (!Schema::hasColumn('shipment_items', 'satuan_tarif')) {
                $table->enum('satuan_tarif', ['kg','kubik','unit'])->default('unit')->after('satuan');
            }

            if (!Schema::hasColumn('shipment_items', 'berat_kg')) {
                $table->decimal('berat_kg', 10, 2)->default(0)->after('satuan_tarif');
            }

            if (!Schema::hasColumn('shipment_items', 'kubikasi_m3')) {
                $table->decimal('kubikasi_m3', 10, 3)->default(0)->after('berat_kg');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            if (Schema::hasColumn('shipment_items', 'kubikasi_m3')) $table->dropColumn('kubikasi_m3');
            if (Schema::hasColumn('shipment_items', 'berat_kg')) $table->dropColumn('berat_kg');
            if (Schema::hasColumn('shipment_items', 'satuan_tarif')) $table->dropColumn('satuan_tarif');

            if (Schema::hasColumn('shipment_items', 'koli')) {
                $table->renameColumn('koli', 'jumlah');
            }
        });
    }
};
