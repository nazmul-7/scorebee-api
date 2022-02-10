<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamPlayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_players', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('team_id')
                ->constrained('teams', 'id')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table
                ->foreignId('player_id')
                ->constrained('club_players', 'player_id')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table
                ->string('squad_type', ['MAIN', 'EXTRA', 'BENCH'])
                ->default('BENCH');
            $table->unique(['team_id', 'player_id']);
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
        Schema::dropIfExists('team_players');
    }
}
