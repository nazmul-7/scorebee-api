<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSquadPlayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('squad_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_squad_id');
            $table->foreignId('player_id');
            $table->string('playing_role', 20)->nullable()->comment('Striker, Baller, All rounder');
            $table->boolean('is_captain')->default(0);
            $table->boolean('is_wicket_keeper')->default(0);
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
        Schema::dropIfExists('squad_players');
    }
}
