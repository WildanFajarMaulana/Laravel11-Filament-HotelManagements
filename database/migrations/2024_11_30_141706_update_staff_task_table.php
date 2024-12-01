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
        Schema::table('staff_tasks', function (Blueprint $table) {
            $table->date('completed_at')->nullable()->change(); // Mengubah kolom 'completed_at' menjadi nullable
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('staff_tasks', function (Blueprint $table) {
            $table->date('completed_at')->nullable(false)->change(); // Mengembalikan ke keadaan sebelumnya
        });
    }
};
