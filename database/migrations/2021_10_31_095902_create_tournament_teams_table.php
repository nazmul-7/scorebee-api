<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTournamentTeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tournament_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id');
            $table->foreignId('tournament_id');
            $table->foreignId('tournament_owner_id');
            $table->string('requested_by', 15)->comment('TOURNAMENT, TEAM');
            $table->string('status', 10)->comment('PENDING, ACCEPTED')->default('PENDING');
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
        Schema::dropIfExists('tournament_teams');
    }
}
