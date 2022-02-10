<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMatchRanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('match_ranks', function (Blueprint $table) {
            $table->id();
            $table->string('tournament_type', 20)->comment('Round Robin, Knockout');
            $table->foreignId('tournament_id');
            $table->foreignId('league_group_id');
            $table->foreignId('league_group_team_id');
            $table->foreignId('team_id');
            $table->foreignId('fixture_id');
            $table->boolean('matchPlayed')->default(0);
            $table->boolean('won')->default(0);
            $table->boolean('loss')->default(0);
            $table->boolean('draw')->default(0);
            $table->unsignedTinyInteger('points')->default(0);
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
        Schema::dropIfExists('match_ranks');
    }
}
