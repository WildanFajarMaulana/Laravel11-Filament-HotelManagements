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
        Schema::create('money_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId( 'reservation_id')->constrained()->cascadeOnDelete();
            $table->string("payment_method");
            $table->unsignedBigInteger("amount");
            $table->date("payment_status");
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('money_payments');
    }
};