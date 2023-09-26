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
        Schema::create('ppi_lists', function (Blueprint $table) {
            $table->id();
            $table->uuid('country_id');
            $table->uuid('pricepoint_id');
            $table->uuid('payment_id');
            $table->string('game_id');
            $table->enum('is_active', [0, 1]);
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
        Schema::dropIfExists('ppi_lists');
    }
};
