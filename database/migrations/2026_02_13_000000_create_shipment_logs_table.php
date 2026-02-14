<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->string('action', 50);          // CREATED, UPDATED, PAYMENT_UPDATED, MANIFEST_ADDED, MANIFEST_REMOVED, etc
            $table->text('description')->nullable();
            $table->json('meta')->nullable();      // data tambahan (opsional)
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();

            $table->index(['shipment_id', 'logged_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_logs');
    }
};

