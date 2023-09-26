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
        Schema::create('price_points', function (Blueprint $table) {
            $table->uuid('pricepoint_id');
            $table->uuid('country_id');
            $table->uuid('game_id');
            $table->string('img', 100);
            $table->string('ppi', 30);
            $table->integer('price');
            $table->integer('amount');
            $table->string('name_currency', 30);
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
        Schema::dropIfExists('price_points');
    }
};
