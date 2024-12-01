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
        Schema::table('payments', function (Blueprint $table) {
            // Ubah kolom payment_status menjadi boolean
            $table->boolean('payment_status')->default(false)->change();
            
            // Tambahkan kolom proof
            $table->string('proof')->nullable()->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            // Kembalikan tipe kolom payment_status ke varchar
            $table->string('payment_status')->nullable()->change();
            
            // Hapus kolom proof
            $table->dropColumn('proof');
        });
    }
};
