<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInningsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('innings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id');
            $table->foreignId('league_group_id');
            $table->foreignId('league_group_team_id');
            $table->foreignId('fixture_id');
            $table->foreignId('batting_team_id');
            $table->foreignId('bowling_team_id');
            $table->foreignId('home_team_id');
            $table->foreignId('away_team_id');
            $table->foreignId('initial_bowler_id');
            $table->foreignId('initial_striker_id');
            $table->foreignId('initial_non_striker_id');
            $table->unsignedSmallInteger('total_runs')->default(0);
            $table->boolean('is_first_innings')->default(0);
            $table->unsignedTinyInteger('total_wickets')->default(0);
            $table->unsignedFloat('total_overs', 3, 1)->default(0);
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
        Schema::dropIfExists('innings');
    }
}
