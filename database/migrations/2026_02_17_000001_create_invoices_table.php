<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->unsignedBigInteger('manifest_id')->nullable()->index();
            $table->string('billed_to', 120);
            $table->enum('status', ['BELUM_DITAGIH','MENUNGGU_PEMBAYARAN','LUNAS'])->default('BELUM_DITAGIH');
            $table->decimal('total', 15, 2)->default(0);
            $table->string('payment_proof_path')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
