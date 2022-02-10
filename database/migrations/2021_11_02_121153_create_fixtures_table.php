<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFixturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fixtures', function (Blueprint $table) {
            $table->id();

            $table->unsignedTinyInteger('match_no')->nullable();
            $table->foreignId('tournament_id');
            $table->foreignId('league_group_id')->nullable();
            $table->foreignId('additional_group_id')->nullable();
            $table->foreignId('home_team_id')->nullable();
            $table->foreignId('away_team_id')->nullable();
            $table->foreignId('ground_id')->nullable();

            $table->enum('fixture_type', ['GROUP', 'KNOCKOUT']);
            $table->unsignedTinyInteger('group_round')->nullable();
            $table->unsignedTinyInteger('knockout_round')->nullable();

            $table->string('match_type', 20)->comment('TEST, LIMITED OVERS')->nullable();
            $table->string('round_type', 20)->comment('LEAGUE MATCH, SUPER LEAGUE, SUPER 10, SUPER 8, SUPER 6, SUPER 4, SUPER 2')->nullable();
            $table->unsignedTinyInteger('match_overs')->default(0);
            $table->longText('power_play')->default(0);
            $table->unsignedTinyInteger('overs_per_bowler')->default(0);
            $table->string('ball_type', 20)->nullable()->comment('LEATHER, TENNIS, OTHER');

            $table->date('match_date')->nullable();
            $table->time('start_time')->nullable();

            $table->foreignId('toss_winner_team_id')->nullable();
            $table->string('team_elected_to')->nullable()->comment('BAT, BOWL');

            $table->boolean('is_match_start')->default(0);
            $table->boolean('is_match_finished')->default(0);
            $table->boolean('is_match_draw')->default(0);
            $table->boolean('is_match_no_result')->default(0);

            $table->foreignId('match_winner_team_id')->nullable();
            $table->foreignId('match_loser_team_id')->nullable();
            $table->foreignId('player_of_the_match')->nullable();
            $table->string('match_final_result', 191)->nullable();

            $table->unsignedFloat('home_team_overs', 3, 1)->default(0);
            $table->unsignedFloat('away_team_overs', 3, 1)->default(0);

            $table->unsignedSmallInteger('home_team_runs')->default(0);
            $table->unsignedSmallInteger('away_team_runs')->default(0);

            $table->unsignedTinyInteger('home_team_wickets')->default(0);
            $table->unsignedTinyInteger('away_team_wickets')->default(0);

            $table->string('temp_team_one', 191)->nullable();
            $table->string('temp_team_two', 191)->nullable();
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
        Schema::dropIfExists('fixtures');
    }
}
