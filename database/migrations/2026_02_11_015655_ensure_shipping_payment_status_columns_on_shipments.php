<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {

            if (!Schema::hasColumn('shipments', 'status_pengiriman')) {
                $table->string('status_pengiriman', 20)->default('DITERIMA')->after('keterangan');
                $table->index('status_pengiriman');
            }

            if (!Schema::hasColumn('shipments', 'status_pembayaran')) {
                // taruh setelah status_pengiriman kalau ada, kalau tidak ya tetap jalan
                $after = Schema::hasColumn('shipments', 'status_pengiriman') ? 'status_pengiriman' : 'keterangan';
                $table->string('status_pembayaran', 20)->default('BELUM_BAYAR')->after($after);
                $table->index('status_pembayaran');
            }

            // manifest_id biasanya sudah ada, jadi kita cek dulu
            if (!Schema::hasColumn('shipments', 'manifest_id')) {
                $table->unsignedBigInteger('manifest_id')->nullable()->after('status_pembayaran');
                $table->index('manifest_id');
            }

            if (!Schema::hasColumn('shipments', 'manifested_at')) {
                $table->timestamp('manifested_at')->nullable()->after('manifest_id');
            }
        });
    }

    public function down(): void
    {
        // Down dibuat aman juga: hanya drop kalau ada
        Schema::table('shipments', function (Blueprint $table) {

            if (Schema::hasColumn('shipments', 'status_pengiriman')) {
                // drop index jika ada
                try { $table->dropIndex(['status_pengiriman']); } catch (\Throwable $e) {}
                $table->dropColumn('status_pengiriman');
            }

            if (Schema::hasColumn('shipments', 'status_pembayaran')) {
                try { $table->dropIndex(['status_pembayaran']); } catch (\Throwable $e) {}
                $table->dropColumn('status_pembayaran');
            }

            if (Schema::hasColumn('shipments', 'manifested_at')) {
                $table->dropColumn('manifested_at');
            }

            // manifest_id jangan di-drop kalau kamu sudah pakai sebelumnya,
            // tapi kalau mau, uncomment:
            /*
            if (Schema::hasColumn('shipments', 'manifest_id')) {
                try { $table->dropIndex(['manifest_id']); } catch (\Throwable $e) {}
                $table->dropColumn('manifest_id');
            }
            */
        });
    }
};
