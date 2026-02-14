<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->text('alamat_penerima')->after('telp_penerima');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('alamat_penerima');
        });
    }





};
