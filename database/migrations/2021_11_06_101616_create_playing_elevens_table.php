<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlayingElevensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('playing_elevens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id');
            $table->foreignId('team_id');
            $table->foreignId('team_squad_id');
            $table->foreignId('player_id');
            $table->string('playing_role', 20)->nullable()->comment('BATSMAN, BOWLER, ALL ROUNDER');
            $table->string('type', 20)->nullable()->comment('MAIN XI, SUBSTITUTE');
            $table->string('match_type', 20)->default('LIMITED OVERS')->comment('LIMITED OVERS, TEST MATCH');
            $table->boolean('is_captain')->default(0);
            $table->boolean('is_wicket_keeper')->default(0);
            $table->boolean('is_played')->default(0);
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
        Schema::dropIfExists('playing_elevens');
    }
}
