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
        Schema::create('game_lists', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('game_id', 30);
            $table->string('slug_game', 30);
            $table->string('game_title', 100);
            $table->string('cover', 100);
            $table->string('banner', 100);
            $table->string('tooltips', 100);
            $table->longText('description');
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
        Schema::dropIfExists('game_lists');
    }
};
