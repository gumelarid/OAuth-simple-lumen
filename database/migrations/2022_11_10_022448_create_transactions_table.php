<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('transaction_id');
            $table->string('refence_no')->nullable();
            $table->string('invoice');
            $table->string('payment_id');
            $table->string('email');
            $table->string('pricepoint_id');
            $table->string('game_id');
            $table->string('phone')->nullable();
            $table->integer('total_price');
            $table->string('transaction_status'); // UNPAID/PENDING/SUCCESS/FAILED
            $table->integer('code')->nullable();
            $table->string('paid_time')->nullable();
            $table->string('user_id')->nullable();
            $table->string('expired_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
