<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInningBatterResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inning_batter_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batter_id');
            $table->foreignId('team_id');
            $table->foreignId('tournament_id');
            $table->foreignId('league_group_id');
            $table->foreignId('league_group_team_id');
            $table->foreignId('fixture_id');
            $table->foreignId('inning_id');
            $table->unsignedFloat('overs_faced', 3, 1)->default(0);
            $table->unsignedTinyInteger('balls_faced')->default(0);
            $table->unsignedSmallInteger('runs_achieved')->default(0);
            $table->unsignedTinyInteger('fours')->default(0);
            $table->unsignedTinyInteger('sixes')->default(0);
            $table->string('match_type',20)->nullable();
            $table->boolean('is_out')->default(0);
            $table->boolean('is_on_strike')->default(0);
            $table->unsignedSmallInteger('position')->default(1);
            $table->string('wicket_type', 20)->nullable()->comment('BOWLED, CAUGHT, HIT, RUN_OUT, STUMPED');
            $table->foreignId('wicket_by')->nullable();
            $table->foreignId('assist_by')->nullable();
            
            $table->foreignId('caught_by')->nullable();
            $table->foreignId('stumped_by')->nullable();
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
        Schema::dropIfExists('inning_batter_results');
    }
}
