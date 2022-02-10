<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('match_type', 20)->comment('TEST, LIMITED OVERS');
            $table->foreignId('tournament_id');
            $table->foreignId('fixture_id');
            $table->foreignId('inning_id');
            $table->foreignId('over_id');
            $table->foreignId('bowler_id');
            $table->foreignId('batter_id');
            $table->foreignId('non_striker_id');
            $table->unsignedTinyInteger('extras')->default(0);
            $table->unsignedTinyInteger('runs')->default(0);
            $table->string('ball_type', 20)->nullable()->comment('NULL if ball is legal otherwise WD, NB, DB, IB');
            $table->string('run_type', 20)->nullable()->comment('B, LB');
            $table->unsignedFloat('shot_x', 6, 2)->nullable();
            $table->unsignedFloat('shot_y', 6, 2)->nullable();
            $table->string('boundary_type', 20)->nullable()->comment('SIX or FOUR');
            $table->string('wicket_type', 20)->nullable()->comment('BOWLED, CAUGHT, HIT, RUN_OUT, STUMPED');
            $table->foreignId('wicket_by')->nullable();
            $table->foreignId('assist_by')->nullable();
            $table->foreignId('caught_by')->nullable();
            $table->foreignId('stumped_by')->nullable();
            $table->foreignId('run_out_by')->nullable();
            $table->foreignId('catch_dropped_by')->nullable();
            $table->foreignId('runs_missed_by')->nullable();
            $table->foreignId('runs_saved_by')->nullable();
            $table->unsignedTinyInteger('saved_runs')->nullable();
            $table->unsignedTinyInteger('missed_runs')->nullable();
            $table->text('commentary')->nullable();
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
        Schema::dropIfExists('deliveries');
    }
}
