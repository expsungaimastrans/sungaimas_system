<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('manifest_items', function (Blueprint $table) {
            // pastikan shipment_id ada dulu
            if (!Schema::hasColumn('manifest_items', 'shipment_id')) {
                $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            }
            $table->unique('shipment_id'); // ✅ lock dobel
        });
    }

    public function down(): void
    {
        Schema::table('manifest_items', function (Blueprint $table) {
            $table->dropUnique(['shipment_id']);
        });
    }
};
