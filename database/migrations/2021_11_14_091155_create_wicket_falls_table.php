<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWicketFallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wicket_falls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id');
            $table->foreignId('batter_id');
            $table->foreignId('team_id');
            $table->foreignId('league_group_id')->default(0);
            $table->foreignId('league_group_team_id')->default(0);
            $table->foreignId('fixture_id');
            $table->foreignId('inning_id');
            $table->unsignedFloat('in_which_over', 3, 1)->default(0);
            $table->string('score_when_fall', 191)->nullable();
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
        Schema::dropIfExists('wicket_falls');
    }
}
