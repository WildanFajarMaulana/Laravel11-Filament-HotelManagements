<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Menghapus kolom 'amount' dari tabel 'reviews'
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Menambahkan kembali kolom 'amount' ke tabel 'reviews' jika rollback
        Schema::table('reviews', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->nullable();  // Atur tipe data sesuai kebutuhan
        });
    }
};
