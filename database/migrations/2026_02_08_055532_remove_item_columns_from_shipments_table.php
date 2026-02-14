<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('nama_barang');
            $table->dropColumn('jumlah');
            $table->dropColumn('satuan');
        });
    }
    
    public function down()
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('nama_barang');
            $table->integer('jumlah');
            $table->string('satuan');
        });
    }
    
    
};
