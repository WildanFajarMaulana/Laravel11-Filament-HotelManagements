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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId( 'user_id')->constrained()->cascadeOnDelete();
            $table->foreignId( 'room_id')->constrained()->cascadeOnDelete();
            $table->date("check_in_date");
            $table->date("check_out_date");
            $table->string("reservation_code");
            $table->unsignedBigInteger("total_price");
            $table->string("reservation_status");
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
