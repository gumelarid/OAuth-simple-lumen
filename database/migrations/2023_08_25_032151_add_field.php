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
        Schema::table('game_lists', function (Blueprint $table) {
            $table->string('color', 25)->after('tooltips');
            $table->string('category', 50)->after('color');
            $table->string('thumbnail', 50)->after('category');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_lists', function (Blueprint $table) {
            $table->dropColumn('color');
            $table->dropColumn('category');
            $table->dropColumn('thumbnail');
        });
    }
};
