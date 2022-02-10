<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInningBowlerResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inning_bowler_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bowler_id');
            $table->foreignId('team_id');
            $table->foreignId('tournament_id');
            $table->foreignId('league_group_id');
            $table->foreignId('league_group_team_id');
            $table->foreignId('fixture_id');
            $table->foreignId('inning_id');
            $table->string('match_type',20)->default('LIMITED OVERS')->comment('LIMITED OVERS, TEST MATCH');
            $table->unsignedFloat('overs_bowled', 3, 1)->default(0);
            $table->unsignedTinyInteger('maiden_overs')->default(0);
            $table->unsignedTinyInteger('balls_bowled')->default(0);
            $table->unsignedSmallInteger('runs_gave')->default(0);
            $table->unsignedTinyInteger('wide_balls')->default(0);
            $table->unsignedTinyInteger('no_balls')->default(0);
            $table->unsignedTinyInteger('wickets')->default(0);
            $table->boolean('is_on_strike')->default(0);
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
        Schema::dropIfExists('inning_bowler_results');
    }
}
