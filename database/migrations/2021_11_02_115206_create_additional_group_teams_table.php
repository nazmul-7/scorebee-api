<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdditionalGroupTeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('additional_group_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('additional_group_id');
            $table->foreignId('team_id');
            $table->unsignedTinyInteger('match_plays')->default(0);
            $table->unsignedTinyInteger('match_wins')->default(0);
            $table->unsignedTinyInteger('match_losses')->default(0);
            $table->unsignedTinyInteger('match_ties')->default(0);
            $table->unsignedSmallInteger('total_runs')->default(0);
            $table->unsignedTinyInteger('group_points')->default(0);
            $table->Float('net_run_rate', 3, 2)->default(0);
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
        Schema::dropIfExists('additional_group_teams');
    }
}
